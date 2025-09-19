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
        $idToken = $request->token;

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

    public function saveAdditionalInfo(Request $request)
    {
        $user = $request->user();
        $requestData = $request->all();

        if (!$user) {
            return $this->errorResponse(null, "Unauthorized", 401);
        }
        $user->update([
            'user_type' => $requestData['user_type'] ?? $user->user_type,
        ]);

        if($user->user_type == 2){
            // save additional info for lawyer
            // $user->update([
            //     'bar_registration_number' => $requestData['bar_registration_number'] ?? $user->bar_registration_number,
            //     'specialization' => $requestData['specialization'] ?? $user->specialization,
            //     'years_of_experience' => $requestData['years_of_experience'] ?? $user->years_of_experience,
            //     'contact_number' => $requestData['contact_number'] ?? $user->lawyerProfile->contact_number,
            //     'address' => $requestData['address'] ?? $user->address,
            //     'city' => $requestData['city'] ?? $user->city,
            //     'state' => $requestData['state'] ?? $user->state,
            //     'zip_code' => $requestData['zip_code'] ?? $user->zip_code,
            //     'country' => $requestData['country'] ?? $user->country,
            //     'profile_picture' => $requestData['profile_picture'] ?? $user->profile_picture,
            //     'bio' => $requestData['bio'] ?? $user->bio,
            //     'education' => $requestData['education'] ?? $user->education,
            // ]);
        }

        return $this->successResponse([
            'user' => $user
        ], "user type save successfully!", 200);
    }
}
