<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\OtpVerification;

use App\Http\Controllers\Auth\UserController;
use App\Services\Mail\AppMailService;
use App\Traits\JsonResponseTrait;


class OtpController extends Controller
{
    use JsonResponseTrait;
    public function sendOtp(Request $request, AppMailService $mailService)
    {
        $request->validate([
            'email' => 'required|email',
            'label' => 'required|in:reset_password,verify_email'
        ]);

        $isEmailExists = User::where('email', $request->email)->exists();
        if($request->label === 'reset_password'){
            if(!$isEmailExists){
                return $this->errorResponse([], "User not found for this email", 400);
            }
        }

        $otp = rand(100000, 999999);
        $expiryTimestamp = now()->addMinutes(10)->timestamp; // <- this is a UNIX timestamp (seconds)

        $data = [
            'email' => $request->email,
            'otp' => $otp,
            'valid_on' => $expiryTimestamp,
        ];

        if ($isEmailExists) {
            $data['is_registered'] = 1;
        }

        $otpModal = OtpVerification::create($data);

        try {
            $title = $request->label === 'reset_password' ? 'Password Reset OTP' : 'Email Verification OTP';
            $mailService->sendOtp($request->email, (string) $otp, $title);
        } catch (\Exception $e) {
            $otpModal->delete();
            return $this->exceptionHandler($e, 'Failed to send OTP email. Please try again later.', 500);
        }

        return $this->successResponse([], 'OTP sent successfully!', 200);
    }


    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|numeric|digits:6',
            'label' => 'required|in:verify_email,reset_password'
        ]);

        $otpModal = OtpVerification::where([
            ['email', '=', $request->email],
            ['otp', '=', $request->otp],
            ['is_verified', '=', 0],
        ])->first();

        if (!$otpModal) {
            return $this->errorResponse([], 'Invalid OTP or Email.', 400);
        }

        // Check if OTP expired
        if (time() > $otpModal->valid_on) { // using PHP time()
            return $this->errorResponse([], 'OTP has expired.', 400);
        }

        if($request->label === 'verify_email'){
            $userModal = User::where('email', $request->email)->update([
                'email_verified_at' => now()
            ]);
        }

        if($request->label === 'reset_password'){
            //
        }

        $otpModal->update([
            'is_verified' => 1,
            'is_registered' => 1
            ]);

        $user = User::where('email', $request->email)->first();
        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'user' => $user,
            'token' => $token
        ], 'OTP verified successfully!', 200);
    }

    
}
