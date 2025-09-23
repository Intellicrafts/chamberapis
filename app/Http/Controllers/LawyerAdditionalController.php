<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LawyerAdditional;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LawyerAdditionalController extends Controller
{
    /**
     * Save additional lawyer details.
     */
    public function saveAdditionalDetails(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Check if it's just updating user_type
            if ($request->has('user_type') && count($request->all()) === 1) {
                return $this->updateUserType($request, $user);
            }

            // Validate request for lawyer details
            $validator = Validator::make($request->all(), [
                'enrollment_no' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('lawyer_additional_details')->ignore($user->id, 'user_id')
                ],
                'experience_years' => 'required|integer|min:0|max:60',
                'consultation_fee' => 'required|numeric|min:0|max:999999.99',
                'practice_areas' => 'required|string', // JSON string from frontend
                'court_practice' => 'required|string', // JSON string from frontend
                'languages_spoken' => 'required|string', // JSON string from frontend
                'professional_bio' => 'required|string|min:50|max:2000',
                'enrollment_certificate' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB
                'cop_certificate' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB
                'address_proof' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB
                'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048', // 2MB
            ], [
                'enrollment_no.required' => 'Enrollment number is required',
                'enrollment_no.unique' => 'This enrollment number is already registered',
                'experience_years.required' => 'Years of experience is required',
                'experience_years.min' => 'Experience years cannot be negative',
                'experience_years.max' => 'Experience years seems too high',
                'consultation_fee.required' => 'Consultation fee is required',
                'consultation_fee.min' => 'Consultation fee cannot be negative',
                'practice_areas.required' => 'At least one practice area is required',
                'court_practice.required' => 'At least one court of practice is required',
                'languages_spoken.required' => 'At least one language is required',
                'professional_bio.required' => 'Professional bio is required',
                'professional_bio.min' => 'Professional bio must be at least 50 characters',
                'enrollment_certificate.required' => 'Enrollment certificate is required',
                'enrollment_certificate.mimes' => 'Enrollment certificate must be PDF, JPG, JPEG, or PNG',
                'enrollment_certificate.max' => 'Enrollment certificate size cannot exceed 5MB',
                'cop_certificate.required' => 'Certificate of Practice is required',
                'cop_certificate.mimes' => 'Certificate of Practice must be PDF, JPG, JPEG, or PNG',
                'cop_certificate.max' => 'Certificate of Practice size cannot exceed 5MB',
                'profile_photo.image' => 'Profile photo must be an image',
                'profile_photo.max' => 'Profile photo size cannot exceed 2MB',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Parse JSON strings
            $practiceAreas = json_decode($request->practice_areas, true);
            $courtPractice = json_decode($request->court_practice, true);
            $languagesSpoken = json_decode($request->languages_spoken, true);

            if (!$practiceAreas || !$courtPractice || !$languagesSpoken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid data format for practice areas, courts, or languages'
                ], 422);
            }

            // Handle file uploads
            $filePaths = $this->handleFileUploads($request, $user->id);

            // Create or update lawyer details
            $lawyerDetails = LawyerAdditional::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'enrollment_no' => $request->enrollment_no,
                    'experience_years' => $request->experience_years,
                    'consultation_fee' => $request->consultation_fee,
                    'practice_areas' => $practiceAreas,
                    'court_practice' => $courtPractice,
                    'languages_spoken' => $languagesSpoken,
                    'professional_bio' => $request->professional_bio,
                    'enrollment_certificate' => $filePaths['enrollment_certificate'],
                    'cop_certificate' => $filePaths['cop_certificate'],
                    'address_proof' => $filePaths['address_proof'],
                    'profile_photo' => $filePaths['profile_photo'],
                    'verification_status' => 'pending',
                    'is_active' => true,
                ]
            );

            // Update user type to lawyer if not already set
            if ($user->user_type !== 2) {
                $user->update(['user_type' => 2]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Lawyer details saved successfully. Your profile is under review.',
                'data' => [
                    'lawyer_details' => $lawyerDetails->load('user'),
                    'user' => $user->fresh(),
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error saving lawyer details: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving details. Please try again.'
            ], 500);
        }
    }

    /**
     * Update user type only.
     */
    private function updateUserType(Request $request, User $user): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_type' => 'required|integer|in:1,2'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid user type',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update(['user_type' => $request->user_type]);

        return response()->json([
            'success' => true,
            'message' => 'User type updated successfully',
            'data' => [
                'user' => $user->fresh()
            ]
        ], 200);
    }

    /**
     * Handle file uploads and return file paths.
     */
    private function handleFileUploads(Request $request, int $userId): array
    {
        $filePaths = [
            'enrollment_certificate' => null,
            'cop_certificate' => null,
            'address_proof' => null,
            'profile_photo' => null,
        ];

        $uploadPath = "lawyer_documents/user_{$userId}";

        // Upload enrollment certificate
        if ($request->hasFile('enrollment_certificate')) {
            $filePaths['enrollment_certificate'] = $request->file('enrollment_certificate')
                ->store($uploadPath, 'public');
        }

        // Upload CoP certificate
        if ($request->hasFile('cop_certificate')) {
            $filePaths['cop_certificate'] = $request->file('cop_certificate')
                ->store($uploadPath, 'public');
        }

        // Upload address proof (optional)
        if ($request->hasFile('address_proof')) {
            $filePaths['address_proof'] = $request->file('address_proof')
                ->store($uploadPath, 'public');
        }

        // Upload profile photo (optional)
        if ($request->hasFile('profile_photo')) {
            $profilePath = "lawyer_profiles/user_{$userId}";
            $filePaths['profile_photo'] = $request->file('profile_photo')
                ->store($profilePath, 'public');
        }

        return $filePaths;
    }

    /**
     * Get lawyer details.
     */
    public function getLawyerDetails(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $lawyerDetails = LawyerAdditional::where('user_id', $user->id)
                ->with('user')
                ->first();

            if (!$lawyerDetails) {
                return response()->json([
                    'success' => false,
                    'message' => 'No lawyer details found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $lawyerDetails
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error fetching lawyer details: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching details'
            ], 500);
        }
    }

    /**
     * Get public lawyer profile.
     */
    public function getPublicLawyerProfile(int $lawyerId): JsonResponse
    {
        try {
            $lawyerDetails = LawyerAdditional::where('user_id', $lawyerId)
                ->with('user:id,name,email,created_at')
                ->verified()
                ->active()
                ->first();

            if (!$lawyerDetails) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lawyer profile not found or not verified'
                ], 404);
            }

            // Hide sensitive information for public view
            $publicData = $lawyerDetails->makeHidden([
                'enrollment_certificate',
                'cop_certificate', 
                'address_proof',
                'verification_notes'
            ]);

            return response()->json([
                'success' => true,
                'data' => $publicData
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error fetching public lawyer profile: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching profile'
            ], 500);
        }
    }

    /**
     * Search lawyers with filters.
     */
    public function searchLawyers(Request $request): JsonResponse
    {
        try {
            $query = LawyerAdditional::with('user:id,name,email')
                ->verified()
                ->active();

            // Apply filters
            if ($request->filled('practice_area')) {
                $query->byPracticeArea($request->practice_area);
            }

            if ($request->filled('court')) {
                $query->byCourtPractice($request->court);
            }

            if ($request->filled('language')) {
                $query->byLanguage($request->language);
            }

            if ($request->filled('min_experience')) {
                $query->byExperience($request->min_experience, $request->max_experience);
            }

            if ($request->filled('min_fee')) {
                $query->byConsultationFee($request->min_fee, $request->max_fee);
            }

            // Apply sorting
            switch ($request->get('sort', 'rating')) {
                case 'rating':
                    $query->orderByRating();
                    break;
                case 'experience':
                    $query->orderByExperience();
                    break;
                case 'fee_low':
                    $query->orderBy('consultation_fee', 'asc');
                    break;
                case 'fee_high':
                    $query->orderBy('consultation_fee', 'desc');
                    break;
                default:
                    $query->orderByRating();
            }

            $lawyers = $query->paginate($request->get('per_page', 15));

            // Hide sensitive information
            $lawyers->getCollection()->transform(function ($lawyer) {
                return $lawyer->makeHidden([
                    'enrollment_certificate',
                    'cop_certificate',
                    'address_proof',
                    'verification_notes'
                ]);
            });

            return response()->json([
                'success' => true,
                'data' => $lawyers
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error searching lawyers: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while searching lawyers'
            ], 500);
        }
    }
}