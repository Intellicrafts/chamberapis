<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Auth\AuthResponseBuilder;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GoogleAuthController
 * -----------------------------------------------------------------------------
 * Accepts the token Google returned to the frontend and issues OUR own
 * Sanctum personal access token.
 *
 * Two token types are accepted — the controller AUTO-DETECTS which one was
 * sent and validates it with the correct Google endpoint:
 *
 *   1. ID Token (JWT, ~3 segments separated by dots)
 *        → From "Google One Tap" (`useGoogleOneTapLogin`).
 *        → Validated via https://oauth2.googleapis.com/tokeninfo?id_token=...
 *
 *   2. access_token (opaque string)
 *        → From the popup flow (`useGoogleLogin`).
 *        → Validated via https://www.googleapis.com/oauth2/v3/userinfo (Bearer).
 *
 * If the e-mail Google returns is brand-new, we CREATE the user automatically
 * (Google sign-in doubles as Google sign-up). If the user already exists we
 * just log them in. user_type is left null on first Google login so the FE
 * can route them to `/profile-setup/type-selection`.
 * -----------------------------------------------------------------------------
 */
class GoogleAuthController extends Controller
{
    use JsonResponseTrait;

    /** Endpoints we hit to validate the Google token. */
    private const TOKENINFO_ENDPOINT = 'https://oauth2.googleapis.com/tokeninfo';
    private const USERINFO_ENDPOINT  = 'https://www.googleapis.com/oauth2/v3/userinfo';

    /**
     * POST /api/auth/google
     *
     * Body: { "token": "<id_token OR access_token>" }
     */
    public function googleLogin(Request $request): JsonResponse
    {
        $request->validate(['token' => 'required|string']);
        $rawToken = $request->input('token');

        try {
            $googleUser = $this->resolveGoogleUser($rawToken);

            if (! $googleUser || empty($googleUser['email'])) {
                return $this->errorResponse(null, 'Invalid Google token provided.', 401);
            }

            // Upsert: create on first sign-in, return existing user thereafter.
            $user = $this->findOrCreateUser($googleUser);

            // Backfill google_id / avatar if we didn't have them before.
            $this->backfillProfileFromGoogle($user, $googleUser);

            // Issue our own Sanctum token.
            $token = $user->createToken('authToken')->plainTextToken;

            return AuthResponseBuilder::success(
                $user,
                $token,
                'Signed in with Google'
            );

        } catch (\Throwable $e) {
            Log::error('Google Login Error', ['message' => $e->getMessage()]);
            return $this->errorResponse(null, 'Authentication failed.', 500);
        }
    }

    /**
     * POST /api/auth/save/additional (auth:sanctum)
     *
     * Called from the profile-setup wizard right after Google sign-up so we
     * can capture the user_type (client/lawyer) and — for lawyers — their
     * enrollment + practice details.
     */
    public function saveAdditionalInfo(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return $this->errorResponse(null, 'Unauthorized', 401);
        }

        $data = $request->all();

        $user->update([
            'user_type' => $data['user_type'] ?? $user->user_type,
        ]);

        if (! empty($data['enrollment_no'])) {
            $this->upsertLawyerDetails($request, $user, $data);
        }

        return $this->successResponse(
            ['user' => AuthResponseBuilder::shapeUser($user->fresh())],
            'Additional profile information saved.'
        );
    }

    /* ═════════════════════════════════════════════════════════════════════
     * Internals
     * ═════════════════════════════════════════════════════════════════════ */

    /**
     * Detect token type and resolve to a Google user payload.
     *
     * @return array|null Associative array with at least `email`, optionally `name`,
     *                    `picture`, `sub`/`id`. Returns null if validation fails.
     */
    private function resolveGoogleUser(string $token): ?array
    {
        // JWT id_tokens have exactly two dots (header.payload.signature).
        if (substr_count($token, '.') === 2) {
            $response = Http::get(self::TOKENINFO_ENDPOINT, ['id_token' => $token]);
            if ($response->successful()) {
                $payload = $response->json();
                // tokeninfo returns `sub` instead of `id`; normalise so the
                // rest of the code doesn't have to care.
                $payload['id'] = $payload['id'] ?? ($payload['sub'] ?? null);
                return $payload;
            }
        }

        // Fall through: treat as an OAuth access_token and hit userinfo.
        $response = Http::withToken($token)->get(self::USERINFO_ENDPOINT);
        if ($response->successful()) {
            $payload = $response->json();
            $payload['id'] = $payload['id'] ?? ($payload['sub'] ?? null);
            return $payload;
        }

        return null;
    }

    /**
     * Find the user by email, or create them with a random password (they can
     * keep using Google forever; if they ever want password login they'd hit
     * "forgot password" to set one).
     */
    private function findOrCreateUser(array $googleUser): User
    {
        return User::firstOrCreate(
            ['email' => $googleUser['email']],
            [
                'name'      => $googleUser['name'] ?? explode('@', $googleUser['email'])[0],
                'google_id' => $googleUser['id'] ?? null,
                'avatar'    => $googleUser['picture'] ?? null,
                'password'  => bcrypt(str()->random(32)), // dummy: never used
            ]
        );
    }

    /** If we didn't have google_id/name/avatar saved yet, save them now. */
    private function backfillProfileFromGoogle(User $user, array $googleUser): void
    {
        if ($user->google_id === null && ! empty($googleUser['id'])) {
            $user->update([
                'google_id' => $googleUser['id'],
                'name'      => $googleUser['name'] ?? $user->name,
                'avatar'    => $googleUser['picture'] ?? $user->avatar,
            ]);
        }
    }

    /** Lawyer profile creation/update used by /auth/save/additional. */
    private function upsertLawyerDetails(Request $request, User $user, array $data): void
    {
        // Handle optional file uploads — saved to the public disk so the FE
        // can render them via /storage/<path>.
        $files = [];
        foreach (['profile_photo' => 'lawyer_profiles', 'enrollment_certificate' => 'lawyer_certificates', 'cop_certificate' => 'lawyer_certificates'] as $field => $dir) {
            if ($request->hasFile($field)) {
                $files[$field] = $request->file($field)->store($dir, 'public');
            }
        }

        $lawyerData = array_filter([
            'user_id'                => $user->id,
            'enrollment_no'          => $data['enrollment_no'] ?? null,
            'experience_years'       => $data['experience_years'] ?? null,
            'consultation_fee'       => $data['consultation_fee'] ?? null,
            'practice_areas'         => $data['practice_areas'] ?? null,
            'court_practice'         => $data['court_practice'] ?? null,
            'languages_spoken'       => $data['languages_spoken'] ?? null,
            'professional_bio'       => $data['professional_bio'] ?? null,
            'profile_photo'          => $files['profile_photo'] ?? null,
            'enrollment_certificate' => $files['enrollment_certificate'] ?? null,
            'cop_certificate'        => $files['cop_certificate'] ?? null,
            'verification_status'    => 'pending',
            'is_active'              => true,
        ], fn ($v) => $v !== null && $v !== []);

        $user->lawyerDetails()->updateOrCreate(['user_id' => $user->id], $lawyerData);
    }
}
