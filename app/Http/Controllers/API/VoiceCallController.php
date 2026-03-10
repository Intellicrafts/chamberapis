<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ConsultationSession;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Twilio\TwiML\VoiceResponse;

/**
 * VoiceCallController
 *
 * Browser-to-browser voice calls via Twilio Voice SDK (TwiML App).
 *
 * Flow:
 *   1. GET  /voice/token           → returns Twilio Access Token (JWT)
 *   2. Frontend uses twilio-voice SDK to connect({To: callee_identity})
 *   3. POST /voice/twiml           → Twilio webhook, returns TwiML to route call
 *   4. GET  /voice/callee/{token}  → returns callee identity & name
 */
class VoiceCallController extends Controller
{
    private string $accountSid;
    private string $apiKeySid;
    private string $apiKeySecret;
    private string $appSid;
    private string $outgoingCallerId;
    private bool   $configured = false;

    public function __construct()
    {
        $this->accountSid       = config('services.twilio.sid', '');
        $this->apiKeySid        = config('services.twilio.api_key_sid', '');
        $this->apiKeySecret     = config('services.twilio.api_key_secret', '');
        $this->appSid           = config('services.twilio.twiml_app_sid', '');
        $this->outgoingCallerId = config('services.twilio.whatsapp_from') ?? '+14155238886';

        $this->configured = !empty($this->accountSid)
            && !empty($this->apiKeySid)
            && !empty($this->apiKeySecret)
            && !empty($this->appSid);

        if (!$this->configured) {
            Log::warning('[Voice] Twilio Voice SDK credentials are missing — calls will not work.', [
                'sid_set'    => !empty($this->accountSid),
                'key_set'    => !empty($this->apiKeySid),
                'secret_set' => !empty($this->apiKeySecret),
                'app_set'    => !empty($this->appSid),
            ]);
        }
    }

    /**
     * GET /api/voice/token?session_token={consultationSessionToken}
     *
     * Returns a Twilio Access Token with a VoiceGrant for the JS SDK.
     */
    public function generateToken(Request $request): JsonResponse
    {
        try {
            if (!$this->configured) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Voice calling is not configured on this server.',
                ], 503);
            }

            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $sessionToken = $request->query('session_token');
            $identity     = $this->buildIdentity($user, $sessionToken);

            $token = new \Twilio\Jwt\AccessToken(
                $this->accountSid,
                $this->apiKeySid,
                $this->apiKeySecret,
                3600,      // TTL 1 hour
                $identity
            );

            $grant = new \Twilio\Jwt\Grants\VoiceGrant();
            $grant->setOutgoingApplicationSid($this->appSid);
            $grant->setIncomingAllow(true);
            $token->addGrant($grant);

            Log::info('[Voice] Token generated.', [
                'user_id'  => $user->id,
                'identity' => $identity,
            ]);

            return response()->json([
                'success'  => true,
                'token'    => $token->toJWT(),
                'identity' => $identity,
            ]);
        } catch (\Throwable $e) {
            Log::error('[Voice] Token generation failed.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'error'   => 'Failed to generate voice token.',
            ], 500);
        }
    }

    /**
     * POST /api/voice/twiml — Public webhook hit by Twilio.
     *
     * Returns TwiML to route the call to a browser client identified by {To}.
     */
    public function twiml(Request $request)
    {
        $response = new VoiceResponse();
        $to       = $request->input('To');

        if (!$to) {
            $response->say('Sorry, we could not connect your call. Please try again.');
            return response($response->asXML(), 200)->header('Content-Type', 'text/xml');
        }

        // Route to browser client
        if (str_starts_with($to, 'client_') || str_starts_with($to, 'lawyer_')) {
            $dial = $response->dial();
            $dial->client($to);
        } else {
            // Fallback — phone number
            $dial = $response->dial('', ['callerId' => $this->outgoingCallerId]);
            $dial->number($to);
        }

        Log::info('[Voice] TwiML call routed.', [
            'to'   => $to,
            'from' => $request->input('From'),
        ]);

        return response($response->asXML(), 200)->header('Content-Type', 'text/xml');
    }

    /**
     * GET /api/voice/callee/{sessionToken}
     *
     * Resolves the callee identity + name so the caller knows whom to dial.
     */
    public function calleeInfo(Request $request, string $sessionToken): JsonResponse
    {
        try {
            $user    = Auth::user();
            $session = ConsultationSession::where('session_token', $sessionToken)
                ->with(['appointment.user', 'appointment.lawyer'])
                ->firstOrFail();

            // Verify participant
            if (!$session->isParticipant($user->id)) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $appointment = $session->appointment;
            $isClient    = ($user->id == $appointment->user_id);

            $callerIdentity = $this->buildIdentity($user, $sessionToken);

            $calleeUserId = $isClient
                ? ($appointment->lawyer?->user_id ?? $appointment->lawyer_id)
                : $appointment->user_id;

            $calleeIdentity = $isClient
                ? 'lawyer_' . $calleeUserId . '_' . substr($sessionToken, 0, 8)
                : 'client_' . $calleeUserId . '_' . substr($sessionToken, 0, 8);

            $calleeName = $isClient
                ? ($appointment->lawyer?->full_name ?? 'Advocate')
                : ($appointment->user?->name ?? 'Client');

            return response()->json([
                'success'         => true,
                'caller_identity' => $callerIdentity,
                'callee_identity' => $calleeIdentity,
                'callee_name'     => $calleeName,
                'callee_role'     => $isClient ? 'lawyer' : 'client',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Session not found'], 404);
        } catch (\Throwable $e) {
            Log::error('[Voice] calleeInfo failed.', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Could not resolve callee info.'], 500);
        }
    }

    /**
     * Deterministic Twilio identity: role_userId_sessionPrefix
     */
    private function buildIdentity($user, ?string $sessionToken): string
    {
        $role   = ($user->user_type == 2) ? 'lawyer' : 'client';
        $prefix = $sessionToken ? substr($sessionToken, 0, 8) : 'nosession';
        return "{$role}_{$user->id}_{$prefix}";
    }
}
