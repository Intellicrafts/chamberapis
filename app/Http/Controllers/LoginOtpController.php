<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Auth\AuthResponseBuilder;
use App\Services\Mail\AppMailService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * LoginOtpController
 * -----------------------------------------------------------------------------
 * Passwordless e-mail OTP login.
 *
 *   POST /api/login/send-otp     →  generate + cache + email a 6-digit OTP
 *   POST /api/login/verify-otp   →  exchange the OTP for a Sanctum token
 *
 * Storage: the OTP lives in Laravel cache (5-minute TTL) keyed by email. This
 * is faster than DB writes for a transient code, survives mailer failures
 * (the OTP is generated BEFORE the mail attempt, so we can recover in dev),
 * and is automatically evicted.
 *
 * Mailer safety: SMTP errors are CAUGHT here and surface as 502 to the client
 * with `mail_error: true`. The OTP itself is still written to the cache so the
 * user can paste a code we manually inspected (useful for local dev where
 * MAIL_USERNAME/PASSWORD are unset).
 * -----------------------------------------------------------------------------
 */
class LoginOtpController extends Controller
{
    private const OTP_TTL_MINUTES = 5;
    private const RATE_LIMIT_PER_MINUTE = 5;
    private const CACHE_PREFIX = 'login_otp_';

    /* ─────────────────────────── SEND OTP ────────────────────────────────── */

    /**
     * POST /api/login/send-otp
     */
    public function sendOtp(Request $request, AppMailService $mailService): JsonResponse
    {
        $key = 'login-otp-send:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, self::RATE_LIMIT_PER_MINUTE)) {
            return response()->json(['message' => 'Too many attempts. Please try again later.'], 429);
        }

        $request->validate(['email' => 'required|email']);
        $email = strtolower(trim($request->email));

        $user = User::where('email', $email)->first();
        if (! $user) {
            RateLimiter::hit($key);
            return response()->json(['message' => 'Account not found for this email'], 404);
        }

        // 1. Generate the OTP and put it in the cache FIRST. This way, even if
        //    the mailer blows up below, the OTP is still recoverable by an
        //    admin and the user can be helped manually.
        $otp = (string) random_int(100000, 999999);
        Cache::put(
            self::CACHE_PREFIX . $email,
            ['otp' => $otp, 'created_at' => Carbon::now()],
            now()->addMinutes(self::OTP_TTL_MINUTES)
        );

        // 2. Try to e-mail it. SMTP failures are non-fatal — surface them as
        //    502 so the FE can show a clean "OTP service unavailable" message
        //    instead of a raw 500 stack trace.
        try {
            $mailService->sendLoginOtp($email, $otp, $user->name);
        } catch (\Throwable $e) {
            Log::error('OTP mail send failed', ['email' => $email, 'error' => $e->getMessage()]);
            return response()->json([
                'success'    => false,
                'mail_error' => true,
                'message'    => 'We generated your code but could not e-mail it. Please try again or contact support.',
            ], 502);
        }

        return response()->json([
            'success'   => true,
            'message'   => 'Login OTP sent to your email',
            'user_name' => $user->name,
        ]);
    }

    /* ───────────────────────── VERIFY OTP ────────────────────────────────── */

    /**
     * POST /api/login/verify-otp
     *
     * On success: returns the standard AuthResponseBuilder JSON shape
     * (same as /api/login). The OTP is then evicted from cache.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|digits:6',
        ]);

        $email = strtolower(trim($request->email));

        // 1. Look up the cached OTP and compare in constant time.
        $cached = Cache::get(self::CACHE_PREFIX . $email);
        if (! $cached || ! hash_equals((string) $cached['otp'], (string) $request->otp)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP code.',
            ], 400);
        }

        // 2. Resolve the user (it might have been deleted between send + verify).
        $user = User::where('email', $email)->first();
        if (! $user) {
            return response()->json(['message' => 'Account not found'], 404);
        }

        // 3. Lawyers with a deactivated profile must not be allowed in.
        $isLawyer = in_array($user->user_type, [2, '2', 'business', 'lawyer'], true);
        if ($isLawyer) {
            $lawyer = \App\Models\Lawyer::where('email', $user->email)->first();
            if ($lawyer && ! $lawyer->active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account is inactive. Please contact administrator.',
                ], 403);
            }
        }

        // 4. Consume the OTP so it can't be replayed.
        Cache::forget(self::CACHE_PREFIX . $email);

        // 5. Issue a fresh Sanctum token + return the standard shape.
        $token = $user->createToken('auth_token')->plainTextToken;

        return AuthResponseBuilder::success(
            $user,
            $token,
            'Hi ' . $user->name . ', welcome back'
        );
    }
}
