<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\User;

class PasswordResetController extends Controller
{
    // Send OTP
    public function sendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $otp = rand(100000, 999999);

        DB::table('password_resets')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => $otp,
                'created_at' => Carbon::now()
            ]
        );

        // Send OTP via email
        Mail::raw("Your OTP is: $otp", function ($message) use ($request) {
            $message->to($request->email)
                    ->subject('Password Reset OTP');
        });

        return response()->json(['message' => 'OTP sent to your email']);
    }

    // Reset Password
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required',
            'password' => 'required|confirmed|min:8',
        ]);

        $record = DB::table('password_resets')
                    ->where('email', $request->email)
                    ->where('token', $request->otp)
                    ->first();

        if (!$record) {
            return response()->json(['message' => 'Invalid OTP'], 400);
        }

        if (Carbon::parse($record->created_at)->addMinutes(10)->isPast()) {
            return response()->json(['message' => 'OTP expired'], 400);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // Delete the OTP record
        DB::table('password_resets')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Password has been reset successfully']);
    }
}































// <?php

// namespace App\Http\Controllers;

// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Mail;
// use Illuminate\Support\Facades\Hash;
// use Illuminate\Support\Facades\Validator;
// use Carbon\Carbon;
// use App\Models\User;

// class PasswordResetController extends Controller
// {
//     // Send OTP
//     public function sendOtp(Request $request)
//     {
//         // Validate email
//         $validator = Validator::make($request->all(), [
//             'email' => 'required|email|exists:users,email'
//         ]);

//         if ($validator->fails()) {
//             return response()->json([
//                 'message' => 'Please enter a valid email address that exists in our system.',
//                 'errors' => $validator->errors()
//             ], 422);
//         }

//         $email = $request->email;

//         // Check if user is currently blocked
//         $existingRecord = DB::table('password_resets')
//             ->where('email', $email)
//             ->first();

//         if ($existingRecord && $existingRecord->blocked_until) {
//             $blockedUntil = Carbon::parse($existingRecord->blocked_until);
//             if ($blockedUntil->isFuture()) {
//                 $remainingMinutes = $blockedUntil->diffInMinutes(Carbon::now());
//                 return response()->json([
//                     'message' => "Too many failed attempts. Please try again in {$remainingMinutes} minutes.",
//                     'blocked_until' => $blockedUntil->toISOString(),
//                     'remaining_minutes' => $remainingMinutes
//                 ], 429);
//             }
//         }

//         // Generate 6-digit OTP
//         $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

//         // Store or update OTP in database
//         DB::table('password_resets')->updateOrInsert(
//             ['email' => $email],
//             [
//                 'token' => $otp,
//                 'created_at' => Carbon::now(),
//                 'attempts' => 0, // Reset attempts when new OTP is sent
//                 'blocked_until' => null // Clear any existing block
//             ]
//         );

//         try {
//             // Send OTP via email
//             Mail::raw("Your password reset OTP is: {$otp}\n\nThis OTP will expire in 10 minutes.\n\nIf you didn't request this, please ignore this email.", function ($message) use ($email) {
//                 $message->to($email)
//                         ->subject('Password Reset OTP - MeraBakil');
//             });

//             return response()->json([
//                 'message' => 'OTP sent to your email successfully!',
//                 'expires_in' => 600 // 10 minutes in seconds
//             ]);

//         } catch (\Exception $e) {
//             // Log the error for debugging
//             \Log::error('Failed to send password reset OTP: ' . $e->getMessage());
            
//             return response()->json([
//                 'message' => 'Failed to send OTP. Please try again later.'
//             ], 500);
//         }
//     }

//     // Verify OTP (optional endpoint for frontend validation)
//     public function verifyOtp(Request $request)
//     {
//         $validator = Validator::make($request->all(), [
//             'email' => 'required|email',
//             'otp' => 'required|string|size:6'
//         ]);

//         if ($validator->fails()) {
//             return response()->json([
//                 'message' => 'Please provide valid email and 6-digit OTP.',
//                 'errors' => $validator->errors()
//             ], 422);
//         }

//         $email = $request->email;
//         $otp = $request->otp;

//         $record = DB::table('password_resets')
//             ->where('email', $email)
//             ->first();

//         if (!$record) {
//             return response()->json([
//                 'message' => 'No OTP request found for this email.'
//             ], 404);
//         }

//         // Check if user is blocked
//         if ($record->blocked_until && Carbon::parse($record->blocked_until)->isFuture()) {
//             $remainingMinutes = Carbon::parse($record->blocked_until)->diffInMinutes(Carbon::now());
//             return response()->json([
//                 'message' => "Too many failed attempts. Please try again in {$remainingMinutes} minutes."
//             ], 429);
//         }

//         // Check if OTP is expired (10 minutes)
//         if (Carbon::parse($record->created_at)->addMinutes(10)->isPast()) {
//             return response()->json([
//                 'message' => 'OTP has expired. Please request a new one.'
//             ], 400);
//         }

//         // Check if OTP matches
//         if ($record->token !== $otp) {
//             // Increment attempts
//             $newAttempts = $record->attempts + 1;
//             $updateData = ['attempts' => $newAttempts];

//             // Block user after 4 failed attempts
//             if ($newAttempts >= 4) {
//                 $updateData['blocked_until'] = Carbon::now()->addMinutes(20);
//                 DB::table('password_resets')
//                     ->where('email', $email)
//                     ->update($updateData);

//                 return response()->json([
//                     'message' => 'Too many failed attempts. Please wait 20 minutes before trying again.',
//                     'blocked_until' => Carbon::now()->addMinutes(20)->toISOString()
//                 ], 429);
//             }

//             DB::table('password_resets')
//                 ->where('email', $email)
//                 ->update($updateData);

//             $remainingAttempts = 4 - $newAttempts;
//             return response()->json([
//                 'message' => "Invalid OTP. {$remainingAttempts} attempts remaining.",
//                 'remaining_attempts' => $remainingAttempts
//             ], 400);
//         }

//         return response()->json([
//             'message' => 'OTP verified successfully!'
//         ]);
//     }

//     // Reset Password
//     public function resetPassword(Request $request)
//     {
//         $validator = Validator::make($request->all(), [
//             'email' => 'required|email',
//             'otp' => 'required|string|size:6',
//             'password' => [
//                 'required',
//                 'confirmed',
//                 'min:8',
//                 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/'
//             ],
//         ], [
//             'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.'
//         ]);

//         if ($validator->fails()) {
//             return response()->json([
//                 'message' => 'Please check your input and try again.',
//                 'errors' => $validator->errors()
//             ], 422);
//         }

//         $email = $request->email;
//         $otp = $request->otp;

//         $record = DB::table('password_resets')
//             ->where('email', $email)
//             ->first();

//         if (!$record) {
//             return response()->json([
//                 'message' => 'No password reset request found for this email.'
//             ], 404);
//         }

//         // Check if user is blocked
//         if ($record->blocked_until && Carbon::parse($record->blocked_until)->isFuture()) {
//             $remainingMinutes = Carbon::parse($record->blocked_until)->diffInMinutes(Carbon::now());
//             return response()->json([
//                 'message' => "Too many failed attempts. Please try again in {$remainingMinutes} minutes."
//             ], 429);
//         }

//         // Check if OTP is expired (10 minutes)
//         if (Carbon::parse($record->created_at)->addMinutes(10)->isPast()) {
//             return response()->json([
//                 'message' => 'OTP has expired. Please request a new one.'
//             ], 400);
//         }

//         // Check if OTP matches
//         if ($record->token !== $otp) {
//             // Increment attempts
//             $newAttempts = $record->attempts + 1;
//             $updateData = ['attempts' => $newAttempts];

//             // Block user after 4 failed attempts
//             if ($newAttempts >= 4) {
//                 $updateData['blocked_until'] = Carbon::now()->addMinutes(20);
//                 DB::table('password_resets')
//                     ->where('email', $email)
//                     ->update($updateData);

//                 return response()->json([
//                     'message' => 'Too many failed attempts. Please wait 20 minutes before trying again.'
//                 ], 429);
//             }

//             DB::table('password_resets')
//                 ->where('email', $email)
//                 ->update($updateData);

//             $remainingAttempts = 4 - $newAttempts;
//             return response()->json([
//                 'message' => "Invalid OTP. {$remainingAttempts} attempts remaining.",
//                 'remaining_attempts' => $remainingAttempts
//             ], 400);
//         }

//         // Find user and update password
//         $user = User::where('email', $email)->first();

//         if (!$user) {
//             return response()->json([
//                 'message' => 'User account not found.'
//             ], 404);
//         }

//         // Update password
//         $user->password = Hash::make($request->password);
//         $user->save();

//         // Delete the OTP record
//         DB::table('password_resets')->where('email', $email)->delete();

//         return response()->json([
//             'message' => 'Password has been reset successfully! You can now login with your new password.'
//         ]);
//     }

//     // Clean up expired OTPs (optional - can be run via scheduled task)
//     public function cleanupExpiredOtps()
//     {
//         $deleted = DB::table('password_resets')
//             ->where('created_at', '<', Carbon::now()->subHours(24))
//             ->delete();

//         return response()->json([
//             'message' => "Cleaned up {$deleted} expired OTP records."
//         ]);
//     }
// }