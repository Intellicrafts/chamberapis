<?php

namespace App\Http\Controllers;

use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class GoogleAuthController extends Controller
{
    use JsonResponseTrait;

    public function googleLogin(Request $request)
    {
        $idToken = $request->credential;

        try {
            // Verify token with Socialite
            $googleUser = Socialite::driver('google')->stateless()->userFromToken($idToken);

            $user = User::firstOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'password' => bcrypt(str()->random(16)) // random password
                ]
            );

            $token = $user->createToken('authToken')->plainTextToken;

            return $this->successResponse([
                'user' => $user,
                'token' => $token
            ], "user logged in successfully!", 200);

        } catch (\Exception $e) {
            return $this->errorResponse("Something went wrong.", 500);
        }
    }
}
