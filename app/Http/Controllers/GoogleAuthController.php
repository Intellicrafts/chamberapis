<?php

namespace App\Http\Controllers;

use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use App\Models\LawyerAdditional;
use Illuminate\Support\Facades\Auth;

class GoogleAuthController extends Controller
{
    use JsonResponseTrait;
    public function googleLogin(Request $request)
    {
        $idToken = $request->token;

        if (!$idToken) {
            return $this->errorResponse("Token is required.", 400);
        }

        try {
            $googleUser = null;

            // Check if this is an ID token (JWT format is typically larger and contains dots)
            if (substr_count($idToken, '.') === 2) {
                // Determine if it's an ID Token (from One Tap)
                $response = \Illuminate\Support\Facades\Http::get('https://oauth2.googleapis.com/tokeninfo', [
                    'id_token' => $idToken,
                ]);

                if ($response->successful()) {
                    $googleUser = $response->json();
                    
                    // Normalize OneTap variables to match standard payload
                    if (!isset($googleUser['id'])) {
                         $googleUser['id'] = $googleUser['sub'] ?? null;
                    }
                }
            } 
            
            // If it's not a JWT or One Tap failed, fall back to testing it as an access token
            if (!$googleUser) {
                $response = \Illuminate\Support\Facades\Http::withToken($idToken)
                    ->get('https://www.googleapis.com/oauth2/v3/userinfo');

                if ($response->successful()) {
                    $googleUser = $response->json();
                    
                     if (!isset($googleUser['id'])) {
                         $googleUser['id'] = $googleUser['sub'] ?? null;
                    }
                }
            }

            if (!$googleUser || !isset($googleUser['email'])) {
                return $this->errorResponse("Invalid Google token provided.", 401);
            }

            $user = User::firstOrCreate(
                ['email' => $googleUser['email']],
                [
                    'name' => $googleUser['name'] ?? explode('@', $googleUser['email'])[0],
                    'google_id' => $googleUser['id'] ?? null,
                    'avatar' => $googleUser['picture'] ?? null,
                    'password' => bcrypt(str()->random(16)) // random password
                ]
            );

            // Update user details if they log in again but their avatar/name changed
            if ($user->google_id === null && isset($googleUser['id'])) {
                 $user->update([
                     'google_id' => $googleUser['id'],
                     'name' => $googleUser['name'] ?? $user->name,
                     'avatar' => $googleUser['picture'] ?? $user->avatar,
                 ]);
            }

            $token = $user->createToken('authToken')->plainTextToken;

            return $this->successResponse([
                'user' => $user,
                'token' => $token
            ], "user logged in successfully!", 200);

        } catch (\Exception $e) {
            \Log::error('Google Login Error: ' . $e->getMessage());
            return $this->errorResponse("Authentication failed.", 500);
        }
    }

    public function saveAdditionalInfo(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return $this->errorResponse(null, "Unauthorized", 401);
        }

        $requestData = $request->all();

        $user->update([
            'user_type' => $requestData['user_type'] ?? $user->user_type,
        ]);

        if(isset($requestData['enrollment_no'])){
            // Handle file uploads
            $profilePhoto = null;
            $enrollmentCertificate = null;
            $copCertificate = null;
            
            if ($request->hasFile('profile_photo')) {
                $profilePhoto = $request->file('profile_photo')->store('lawyer_profiles', 'public');
            }
            
            if ($request->hasFile('enrollment_certificate')) {
                $enrollmentCertificate = $request->file('enrollment_certificate')->store('lawyer_certificates', 'public');
            }
            
            if ($request->hasFile('cop_certificate')) {
                $copCertificate = $request->file('cop_certificate')->store('lawyer_certificates', 'public');
            }

            // Prepare lawyer additional data
            $lawyerData = [
                'user_id' => $user->id,
                'enrollment_no' => $requestData['enrollment_no'] ?? null,
                'experience_years' => $requestData['experience_years'] ?? null,
                'consultation_fee' => $requestData['consultation_fee'] ?? null,
                'practice_areas' => $requestData['practice_areas'] ?? [],
                'court_practice' => $requestData['court_practice'] ?? [],
                'languages_spoken' => $requestData['languages_spoken'] ?? [],
                'professional_bio' => $requestData['professional_bio'] ?? null,
                'profile_photo' => $profilePhoto,
                'enrollment_certificate' => $enrollmentCertificate,
                'cop_certificate' => $copCertificate,
                'verification_status' => 'pending', // Default status
                'is_active' => true, // Default active
            ];

            // Remove null values
            $lawyerData = array_filter($lawyerData, function($value) {
                return $value !== null && $value !== [];
            });

            // Create or update lawyer additional details
            $user->lawyerDetails()->updateOrCreate(
                ['user_id' => $user->id],
                $lawyerData
            );
        }


        return $this->successResponse([
            'user' => $user
        ], "user type save successfully!", 200);
    }
}
