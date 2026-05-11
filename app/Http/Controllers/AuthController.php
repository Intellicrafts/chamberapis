<?php

namespace App\Http\Controllers;

use App\Events\UserRegistered;
use App\Models\Lawyer;
use App\Models\LawyerEnrollmentStatusLog;
use App\Models\User;
use App\Services\Auth\AuthResponseBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

/**
 * AuthController
 * -----------------------------------------------------------------------------
 * Email + password registration and login.
 *
 * Login methods supported by the wider API surface (all return the same
 * JSON shape via AuthResponseBuilder):
 *   - Email + Password .........  this controller, POST /api/login
 *   - Email OTP ................  LoginOtpController, POST /api/login/{send,verify}-otp
 *   - Google OAuth + One Tap ...  GoogleAuthController, POST /api/auth/google
 *
 * Cross-cutting concerns handled here:
 *   - Rate limiting per IP (5/minute) on both register and login.
 *   - Strict input validation (Laravel Validator).
 *   - DB transaction around registration (atomic user + lawyer create).
 *   - `UserRegistered` event fired AFTER commit (WhatsApp welcome message etc).
 * -----------------------------------------------------------------------------
 */
class AuthController extends Controller
{
    /** Maximum register/login attempts per IP per minute (RateLimiter). */
    private const ATTEMPT_LIMIT = 5;

    /* ────────────────────────────── REGISTER ─────────────────────────────── */

    /**
     * POST /api/register
     *
     * Creates a new user. If `account_type` is "business"/2, also creates the
     * matching Lawyer record inside a DB transaction.
     *
     * Request body:
     *   - name (required)            Letters, spaces, hyphens, apostrophes, dots.
     *   - email (required, unique)
     *   - phone (required)
     *   - password (required, complex)
     *   - account_type ("personal"|"business"|1|2)
     *   - enrollment_no (required if lawyer)
     *   - specialization (required if lawyer)
     */
    public function register(Request $request): JsonResponse
    {
        $rateKey = 'register:' . $request->ip();
        if ($this->isRateLimited($rateKey)) {
            return $this->tooManyAttempts('Too many registration attempts. Please try again later.');
        }

        Log::info('Registration attempt', [
            'email'        => $request->email,
            'ip'           => $request->ip(),
            'account_type' => $request->account_type,
        ]);

        $validator = $this->buildRegisterValidator($request);
        if ($validator->fails()) {
            RateLimiter::hit($rateKey);
            return $this->validationFailed($validator);
        }

        try {
            // Wrap user + lawyer + audit-log writes in ONE transaction so a
            // mid-way failure doesn't leave a half-created account behind.
            [$user, $lawyer, $token] = DB::transaction(function () use ($request) {
                $userTypeInt = $this->normaliseAccountType($request->account_type);
                $user = $this->createUserRecord($request, $userTypeInt);

                $lawyer = null;
                if ($userTypeInt === 2) {
                    $lawyer = $this->createLawyerRecord($request, $user);
                    LawyerEnrollmentStatusLog::create([
                        'user_id' => $user->id,
                        'status'  => '0',
                    ]);
                }

                // Issue a Sanctum personal access token for the new user.
                $token = $user->createToken('auth_token')->plainTextToken;
                return [$user, $lawyer, $token];
            });

            RateLimiter::clear($rateKey);
            Log::info('Registration successful', ['user_id' => $user->id, 'email' => $user->email]);

            // Fire AFTER commit so async listeners (WhatsApp welcome message,
            // referral bonus, etc.) see a committed row.
            try {
                event(new UserRegistered($user, $lawyer));
            } catch (\Throwable $e) {
                Log::warning('UserRegistered event failed', ['error' => $e->getMessage()]);
            }

            return AuthResponseBuilder::success(
                $user,
                $token,
                'User registered successfully',
                201
            );

        } catch (\Throwable $e) {
            RateLimiter::hit($rateKey);
            Log::error('Registration error', [
                'email'   => $request->email,
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            return response()->json([
                'message' => 'Registration failed',
                'error'   => config('app.debug') ? $e->getMessage() : 'An error occurred during registration',
            ], 500);
        }
    }

    /* ─────────────────────────────── LOGIN ──────────────────────────────── */

    /**
     * POST /api/login
     *
     * Email + password authentication. Issues a fresh Sanctum token on success.
     * NOTE: existing tokens are NOT revoked here — users frequently sign in on
     * multiple devices. Adjust to `$user->tokens()->delete()` if you want
     * single-session behaviour.
     */
    public function login(Request $request): JsonResponse
    {
        $rateKey = 'login:' . $request->ip();
        if ($this->isRateLimited($rateKey)) {
            return $this->tooManyAttempts('Too many login attempts. Please try again later.');
        }

        Log::info('Login attempt', ['email' => $request->email, 'ip' => $request->ip()]);

        $validator = Validator::make($request->all(), [
            'email'    => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
        ]);
        if ($validator->fails()) {
            RateLimiter::hit($rateKey);
            return $this->validationFailed($validator);
        }

        try {
            $email = strtolower(trim($request->email));

            // Attempt::auth handles the bcrypt comparison + active-account check.
            if (! Auth::attempt(['email' => $email, 'password' => $request->password])) {
                RateLimiter::hit($rateKey);
                Log::warning('Login failed: invalid credentials', ['email' => $email]);
                return response()->json(['message' => 'Invalid login credentials'], 401);
            }

            /** @var User $user */
            $user = User::where('email', $email)->firstOrFail();
            $token = $user->createToken('auth_token')->plainTextToken;

            RateLimiter::clear($rateKey);
            Log::info('Login successful', ['user_id' => $user->id, 'email' => $user->email]);

            return AuthResponseBuilder::success(
                $user,
                $token,
                'Hi ' . $user->name . ', welcome back'
            );

        } catch (\Throwable $e) {
            RateLimiter::hit($rateKey);
            Log::error('Login error', ['email' => $request->email, 'message' => $e->getMessage()]);
            return response()->json([
                'message' => 'Login failed',
                'error'   => config('app.debug') ? $e->getMessage() : 'An error occurred during login',
            ], 500);
        }
    }

    /* ────────────────────────────── LOGOUT ──────────────────────────────── */

    /**
     * POST /api/logout (auth:sanctum)
     *
     * Revokes ONLY the current token (not all of the user's tokens) so other
     * active sessions (mobile app, second browser, etc.) keep working.
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (! $user) {
                return response()->json(['message' => 'No authenticated user found'], 401);
            }

            $token = $user->currentAccessToken();
            if ($token) $token->delete();

            Log::info('Logout successful', ['user_id' => $user->id]);
            return response()->json(['message' => 'Successfully logged out']);

        } catch (\Throwable $e) {
            Log::error('Logout error', ['message' => $e->getMessage()]);
            return response()->json([
                'message' => 'Logout failed',
                'error'   => config('app.debug') ? $e->getMessage() : 'An error occurred during logout',
            ], 500);
        }
    }

    /* ═════════════════════════════════════════════════════════════════════
     * Private helpers (kept small + focused so each is trivially testable)
     * ═════════════════════════════════════════════════════════════════════ */

    /** True when the rate limiter has rejected too many recent attempts. */
    private function isRateLimited(string $key): bool
    {
        return RateLimiter::tooManyAttempts($key, self::ATTEMPT_LIMIT);
    }

    private function tooManyAttempts(string $message): JsonResponse
    {
        return response()->json(['message' => $message], 429);
    }

    private function validationFailed($validator): JsonResponse
    {
        return response()->json([
            'message' => 'Validation failed',
            'errors'  => $validator->errors(),
        ], 422);
    }

    /**
     * Build the Validator instance for /register. Pulled into its own method
     * to keep the controller action readable.
     *
     * Name regex `/^[\p{L}\s.'-]+$/u` accepts unicode letters, spaces and the
     * three common punctuation marks that show up in real names (apostrophe,
     * hyphen, full stop). The original `/^[a-zA-Z\s]+$/` rejected perfectly
     * valid names like "Mary-Jane" and "O'Brien" — fixed here.
     */
    private function buildRegisterValidator(Request $request)
    {
        return Validator::make($request->all(), [
            'name'                  => ['required', 'string', 'max:255', 'regex:/^[\p{L}\s.\'\-]+$/u'],
            'email'                 => 'required|string|email|max:255|unique:users',
            // 8+ chars, at least 1 lower, 1 upper, 1 digit, 1 special.
            'password'              => 'required|string|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]+$/',
            'phone'                 => 'required|string|max:20',
            'account_type'          => 'nullable',
            'enrollment_no'         => 'required_if:account_type,business,lawyer,2|string|max:50',
            'specialization'        => 'nullable|string|max:255',
            'years_of_experience'   => 'nullable|integer|min:0|max:50',
            'consultation_fee'      => 'nullable|numeric|min:0',
        ], [
            'password.regex' => 'Password must include uppercase, lowercase, number, and a special character (8+ chars total).',
            'name.regex'     => "Name may only contain letters, spaces, hyphens, apostrophes and dots.",
        ]);
    }

    /** Normalise the polymorphic `account_type` input to an integer (1 or 2). */
    private function normaliseAccountType($raw): int
    {
        return in_array($raw, ['business', 'lawyer', 2, '2'], true) ? 2 : 1;
    }

    /** Create the User row inside the registration transaction. */
    private function createUserRecord(Request $request, int $userTypeInt): User
    {
        return User::create([
            'name'      => trim(strip_tags($request->name)),
            'email'     => strtolower(trim($request->email)),
            'password'  => Hash::make($request->password),
            'phone'     => $request->phone ? trim(strip_tags($request->phone)) : null,
            'user_type' => $userTypeInt,
            'role'      => $userTypeInt === 2 ? 'lawyer' : 'user',
        ]);
    }

    /**
     * Create the Lawyer row inside the registration transaction. Throws on
     * duplicate enrollment_no so the surrounding transaction rolls back.
     */
    private function createLawyerRecord(Request $request, User $user): Lawyer
    {
        $enrollmentNo = $request->enrollment_no;
        if (Lawyer::where('enrollment_no', $enrollmentNo)->exists()) {
            throw new \RuntimeException('Enrollment number already exists');
        }

        return Lawyer::create([
            'user_id'             => $user->id,
            'full_name'           => $user->name,
            'email'               => $user->email,
            'password_hash'       => $user->password, // already bcrypt'd above
            'active'              => true,
            'is_verified'         => false,
            'enrollment_no'       => $enrollmentNo,
            'specialization'      => $request->specialization,
            'years_of_experience' => $request->years_of_experience ?? 0,
            'bio'                 => $request->bio ? trim(strip_tags($request->bio)) : null,
            'consultation_fee'    => $request->consultation_fee ?? 0.00,
            'status'              => '0',
        ]);
    }
}
