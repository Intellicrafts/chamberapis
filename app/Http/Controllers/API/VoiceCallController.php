<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\ConsultationSession;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;
use Twilio\Jwt\AccessToken;
use Twilio\Jwt\Grants\VoiceGrant;
use Twilio\TwiML\VoiceResponse;

/**
 * VoiceCallController
 *
 * Manages browser-based voice calls between clients and lawyers
 * using Twilio Voice SDK (browser-to-browser via TwiML App).
 *
 * Flow:
 *   1. Frontend calls GET /voice/token       → backend returns Twilio Access Token
 *   2. Frontend uses twilio-voice SDK to call {callee_identity}
 *   3. Twilio hits POST /voice/twiml          → backend returns TwiML to route call
 *   4. Frontend can POST /voice/call/{token}  → initiate server-side call
 */
class VoiceCallController extends Controller
{
    private string $accountSid;
    private string $authToken;
    private string $apiKeySid;
    private string $apiKeySecret;
    private string $appSid;
    private string $outgoingCallerId;

    public function __construct()
    {
        $this->accountSid       = config('services.twilio.sid');
        $this->authToken        = config('services.twilio.token');
        $this->apiKeySid        = config('services.twilio.api_key_sid');
        $this->apiKeySecret     = config('services.twilio.api_key_secret');
        $this->appSid           = config('services.twilio.twiml_app_sid');
        $this->outgoingCallerId = config('services.twilio.whatsapp_from') ?? '+14155238886';
    }

    /**
     * Generate a Twilio Access Token for the Twilio Voice JS SDK.
     *
     * GET /api/voice/token?session_token={consultationSessionToken}
     */
    public function generateToken(Request $request): JsonResponse
    {
        try {
            $user           = Auth::user();
            $sessionToken   = $request->query('session_token');

            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            // Identity used in the Twilio SDK — unique per session-participant
            // Format: role_userId_sessionShort
            $identity = $this->buildIdentity($user, $sessionToken);

            // Build Twilio Access Token (expires in 1 hour)
            $token = new AccessToken(
                $this->accountSid,
                $this->apiKeySid,
                $this->apiKeySecret,
                3600,
                $identity
            );

            // Attach a Voice Grant
            $voiceGrant = new VoiceGrant();
            $voiceGrant->setOutgoingApplicationSid($this->appSid);
            $voiceGrant->setIncomingAllow(true);

            $token->addGrant($voiceGrant);

            Log::info('Voice token generated.', [
                'user_id'  => $user->id,
                'identity' => $identity,
            ]);

            return response()->json([
                'success'  => true,
                'token'    => $token->toJWT(),
                'identity' => $identity,
            ]);
        } catch (\Throwable $e) {
            Log::error('Voice token generation failed.', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to generate voice token: ' . $e->getMessage()], 500);
        }
    }

    /**
     * TwiML Webhook — called by Twilio when a call is initiated.
     * Routes the call to the correct callee identity via <Dial><Client>.
     *
     * POST /api/voice/twiml   (public, verified via Twilio signature)
     */
    public function twiml(Request $request)
    {
        $response = new VoiceResponse();

        $to = $request->input('To');

        if (!$to) {
            $response->say('Sorry, we could not connect your call. Please try again.');
            return response($response->asXML(), 200)
                ->header('Content-Type', 'text/xml');
        }

        // Route to browser client by identity
        if (str_starts_with($to, 'client_') || str_starts_with($to, 'lawyer_')) {
            $dial = $response->dial();
            $dial->client($to);
        } else {
            // Fallback — treat as phone number
            $dial = $response->dial('', ['callerId' => $this->outgoingCallerId]);
            $dial->number($to);
        }

        Log::info('TwiML call routed.', [
            'to'   => $to,
            'from' => $request->input('From'),
        ]);

        return response($response->asXML(), 200)
            ->header('Content-Type', 'text/xml');
    }

    /**
     * Get the callee identity for a consultation session.
     * Caller uses this to know WHO to call via the JS SDK.
     *
     * GET /api/voice/callee/{sessionToken}
     */
    public function calleeInfo(Request $request, string $sessionToken): JsonResponse
    {
        try {
            $user    = Auth::user();
            $session = ConsultationSession::where('session_token', $sessionToken)
                ->with(['appointment.user', 'appointment.lawyer'])
                ->firstOrFail();

            // Verify this user is a participant
            if (!$session->isParticipant($user->id)) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $appointment  = $session->appointment;
            $isClient     = ($user->id == $appointment->user_id);

            // Caller = current user; Callee = the other party
            $callerIdentity = $this->buildIdentity($user, $sessionToken);
            $calleeUserId   = $isClient ? $appointment->lawyer?->user_id ?? $appointment->lawyer_id : $appointment->user_id;
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
        } catch (\Throwable $e) {
            Log::error('calleeInfo failed.', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Build a deterministic Twilio identity for a user in a session.
     * Format: role_userId_sessionPrefix
     */
    private function buildIdentity($user, ?string $sessionToken): string
    {
        $role    = ($user->user_type == 2) ? 'lawyer' : 'client';
        $prefix  = $sessionToken ? substr($sessionToken, 0, 8) : 'nosession';
        return "{$role}_{$user->id}_{$prefix}";
    }
}
