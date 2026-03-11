<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Lawyer;
use App\Services\Mail\AppMailService;
use Illuminate\Support\Facades\RateLimiter;

class LoginOtpController extends Controller
{
    /**
     * Send OTP for login.
     */
    public function sendOtp(Request $request, AppMailService $mailService)
    {
        // Rate limiting
        $key = 'login-otp-send:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'message' => 'Too many attempts. Please try again later.'
            ], 429);
        }

        $request->validate(['email' => 'required|email']);
        
        $email = strtolower(trim($request->email));

        $user = User::where('email', $email)->first();

        if (!$user) {
            RateLimiter::hit($key);
            return response()->json(['message' => 'Account not found for this email'], 404);
        }

        // Generate OTP
        $otp = rand(100000, 999999);

        // Store OTP. We can reuse password_resets table or a generic one. We'll use password_resets for simplicity as it behaves identically (email -> token -> timestamp), but realistically Laravel Cache is better here to avoid DB clutter for simple logins. Using Cache is safer for login attempts:
        \Illuminate\Support\Facades\Cache::put('login_otp_' . $email, [
            'otp' => (string) $otp,
            'created_at' => Carbon::now()
        ], now()->addMinutes(5));

        // Send Email
        $mailService->sendLoginOtp($email, (string) $otp, $user->name);

        return response()->json([
            'success' => true,
            'message' => 'Login OTP sent to your email',
            'user_name' => $user->name
        ]);
    }

    /**
     * Verify OTP and return standard Login token
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required',
        ]);
        
        $email = strtolower(trim($request->email));

        $cacheData = \Illuminate\Support\Facades\Cache::get('login_otp_' . $email);

        if (!$cacheData || $cacheData['otp'] !== $request->otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP code.'
            ], 400);
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json(['message' => 'Account not found'], 404);
        }

        // Check lawyer profile if applicable
        $lawyer = null;
        if (in_array($user->user_type, ['business', 'lawyer', 2, '2'])) {
            $lawyer = Lawyer::where('email', $user->email)->first();
            if ($lawyer && !$lawyer->active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account is inactive. Please contact administrator.'
                ], 403);
            }
        }

        // Clear the OTP
        \Illuminate\Support\Facades\Cache::forget('login_otp_' . $email);

        // Create a new token
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = [
            'message' => 'Hi '.$user->name.', welcome back',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'user_type' => $user->user_type,
                'created_at' => $user->created_at,
            ]
        ];

        // Add lawyer data if available
        if ($lawyer) {
            $response['lawyer'] = [
                'uuid' => $lawyer->id,
                'full_name' => $lawyer->full_name,
                'email' => $lawyer->email,
                'enrollment_no' => $lawyer->enrollment_no,
                'specialization' => $lawyer->specialization,
                'is_verified' => $lawyer->is_verified,
                'status' => $lawyer->status,
            ];
        }

        return response()->json($response);
    }
}
