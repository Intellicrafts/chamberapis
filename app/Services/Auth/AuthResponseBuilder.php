<?php

namespace App\Services\Auth;

use App\Models\Lawyer;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * AuthResponseBuilder
 * -----------------------------------------------------------------------------
 * Single source of truth for the JSON shape returned by EVERY successful
 * authentication call (register, login, OTP verify, Google login, password
 * reset). The frontend's `authService.extractAuthPayload()` is built against
 * THIS shape — keep them in sync.
 *
 * Shape:
 *   {
 *     "message":       "Hi <name>, welcome back",
 *     "access_token":  "1|abc...",     // Sanctum plain-text token
 *     "token_type":    "Bearer",
 *     "user": {
 *       "id":          14,
 *       "name":        "Jane Doe",
 *       "email":       "jane@example.com",
 *       "phone":       "+91 9...",
 *       "user_type":   1 | 2,
 *       "role":        "user" | "lawyer",
 *       "avatar":      null | "https://...",
 *       "created_at":  "2026-05-11T12:34:56Z"
 *     },
 *     "lawyer": {                      // only when user is a lawyer
 *       "uuid":         11,
 *       "full_name":    "...",
 *       "email":        "...",
 *       "enrollment_no":"...",
 *       "specialization": "...",
 *       "is_verified":  true | false,
 *       "status":       "0"
 *     }
 *   }
 * -----------------------------------------------------------------------------
 */
class AuthResponseBuilder
{
    /**
     * Build a "logged in / registered" JSON response.
     *
     * @param User   $user
     * @param string $token   Plain-text Sanctum token returned by createToken().
     * @param string $message Greeting shown by the frontend toast.
     * @param int    $status  HTTP status code (200 for login, 201 for register).
     */
    public static function success(User $user, string $token, string $message = 'Authenticated', int $status = 200): JsonResponse
    {
        $payload = [
            'message'      => $message,
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => self::shapeUser($user),
        ];

        // If the user is a lawyer, attach the lawyer profile block.
        $lawyer = self::lawyerFor($user);
        if ($lawyer) {
            $payload['lawyer'] = self::shapeLawyer($lawyer);
        }

        return response()->json($payload, $status);
    }

    /**
     * Build the user sub-object. Keeping this private + DRY means the frontend
     * only ever sees ONE shape regardless of which auth endpoint produced it.
     */
    public static function shapeUser(User $user): array
    {
        return [
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'phone'      => $user->phone ?? null,
            'user_type'  => $user->user_type,
            'role'       => $user->role,
            'avatar'     => $user->avatar ?? null,
            'created_at' => $user->created_at,
        ];
    }

    /**
     * Build the lawyer sub-object (only included when applicable).
     */
    public static function shapeLawyer(Lawyer $lawyer): array
    {
        return [
            'uuid'           => $lawyer->id,
            'full_name'      => $lawyer->full_name,
            'email'          => $lawyer->email,
            'enrollment_no'  => $lawyer->enrollment_no,
            'specialization' => $lawyer->specialization,
            'is_verified'    => $lawyer->is_verified,
            'status'         => $lawyer->status,
        ];
    }

    /**
     * Fetch the matching Lawyer record for a user, or null if they aren't one.
     * Handles both integer (2) and legacy string ('business' / 'lawyer') values
     * in `user_type` defensively.
     */
    private static function lawyerFor(User $user): ?Lawyer
    {
        $isLawyer = in_array($user->user_type, [2, '2', 'business', 'lawyer'], true);
        if (! $isLawyer) {
            return null;
        }
        return Lawyer::where('email', $user->email)->first();
    }
}
