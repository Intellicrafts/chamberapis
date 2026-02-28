<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Lawyer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LawyerController extends Controller
{
    /**
     * Display a listing of lawyers.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Lawyer::query();

            // Filters
            if ($request->has('active')) {
                $query->where('active', $request->boolean('active'));
            }

            if ($request->has('verified')) {
                $query->where('is_verified', $request->boolean('verified'));
            }

            if ($request->has('specialization')) {
                $query->bySpecialization($request->specialization);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('full_name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%")
                      ->orWhere('specialization', 'LIKE', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $lawyers = $query->with(['reviews', 'availabilitySlots'])
                            ->withCount(['reviews', 'appointments'])
                            ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $lawyers,
                'message' => 'Lawyers retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving lawyers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created lawyer.
     * Links to an existing User if email matches, or creates a new User if not.
     */
    /**
     * Store a newly created lawyer.
     * Ensures both User and Lawyer records are synchronized.
     */
    public function store(Request $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            try {
                $validated = $request->validate([
                    'full_name'           => 'required|string|max:255',
                    'email'               => 'required|email|unique:lawyers,email',
                    'phone_number'        => 'nullable|string|max:20',
                    'password'            => 'required|string|min:8',
                    'enrollment_no'       => 'required|string|unique:lawyers,enrollment_no',
                    'bar_association'     => 'nullable|string|max:255',
                    'specialization'      => 'nullable|string|max:255',
                    'years_of_experience' => 'nullable|integer|min:0',
                    'bio'                 => 'nullable|string',
                    'consultation_fee'    => 'nullable|numeric|min:0',
                    'profile_picture'     => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
                ]);

                // STEP 1: COLLECT USER INFORMATION
                // We must ensure an entry exists in the 'users' table before proceeding.
                $user = \App\Models\User::where('email', $validated['email'])->first();

                if (!$user) {
                    // Scenario A: Completely new user. We create the user account first.
                    // Pre-check for email uniqueness in users table to prevent duplicate errors
                    if (\App\Models\User::where('email', $validated['email'])->exists()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Email already exists in our system. Please try logging in.'
                        ], 422);
                    }

                    $user = \App\Models\User::create([
                        'name'        => $validated['full_name'],
                        'email'       => $validated['email'],
                        'password'    => Hash::make($request->password),
                        'phone'       => $validated['phone_number'],
                        'user_type'   => 2, // 2 = Lawyer
                        'role'        => null, // Nullable to bypass enum restrictions if 'lawyer' is missing
                        'active'      => true,
                        'is_verified' => false,
                    ]);
                    
                    \Log::info("New user account created successfully for lawyer: {$user->email}");
                } else {
                    // Scenario B: User exists (e.g. registered as client). We map to them.
                    // We update their user_type to 2 (Lawyer) to ensure they have correct access.
                    if ($user->user_type != 2) {
                        $user->update(['user_type' => 2]);
                        \Log::info("Existing user [ID: {$user->id}] updated to Lawyer type.");
                    }
                }

                // STEP 2: OBTAIN USER_ID AND PREPARE LAWYER DATA
                $userId = $user->id; // This is the mandatory mapping key
                
                $lawyerData = $validated;
                $lawyerData['user_id'] = $userId; // Map the lawyer to the user
                $lawyerData['status']  = '0';     // Default status (Pending)
                
                // Handle Profile Picture
                if ($request->hasFile('profile_picture')) {
                    $lawyerData['profile_picture_url'] = $request->file('profile_picture')
                        ->store('lawyers', 'public');
                }

                // Store hash for lawyer table specifically (if required by schema)
                $lawyerData['password_hash'] = Hash::make($request->password);
                unset($lawyerData['password']);

                // STEP 3: REGISTER LAWYER ENTRY
                // The entry is now created with a guaranteed user_id mapping.
                $lawyer = Lawyer::create($lawyerData);

                \Log::info("Lawyer profile [ID: {$lawyer->id}] registered and mapped to User ID: {$userId}");

                return response()->json([
                    'success' => true,
                    'data' => [
                        'lawyer_profile' => $lawyer,
                        'user_account'   => [
                            'id'        => $user->id,
                            'name'      => $user->name,
                            'email'     => $user->email,
                            'user_type' => $user->user_type
                        ],
                        'mapping' => [
                            'user_id' => $userId,
                            'status'  => 'mapped'
                        ]
                    ],
                    'message' => 'Lawyer registered and synchronized with user account successfully.'
                ], 201);

            } catch (ValidationException $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating lawyer/user: ' . $e->getMessage()
                ], 500);
            }
        });
    }

    /**
     * Display the specified lawyer.
     */
    public function show(Lawyer $lawyer): JsonResponse
    {
        try {
            $lawyer->load([
                'reviews.user:id,name',
                'availabilitySlots' => function($query) {
                    $query->available()->future()->orderBy('start_time');
                }
            ]);

            // Add computed attributes
            $lawyer->average_rating = $lawyer->average_rating;
            $lawyer->total_reviews = $lawyer->total_reviews;
            // UUID is automatically included via the appends property in the model

            return response()->json([
                'success' => true,
                'data' => $lawyer,
                'message' => 'Lawyer retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving lawyer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified lawyer.
     * Supports PUT, PATCH (partial), and POST (form-data) methods.
     */
    public function update(Request $request, Lawyer $lawyer): JsonResponse
    {
        try {
            // Use 'sometimes' so fields NOT present in the request are simply skipped —
            // this prevents passing null into NOT NULL database columns.
            $validated = $request->validate([
                'full_name'           => 'sometimes|required|string|max:255',
                'email'               => 'sometimes|required|email|unique:lawyers,email,' . $lawyer->id,
                'phone_number'        => 'sometimes|nullable|string|max:20',
                'enrollment_no'       => 'sometimes|required|string|unique:lawyers,enrollment_no,' . $lawyer->id,
                'bar_association'     => 'sometimes|nullable|string|max:255',
                'specialization'      => 'sometimes|nullable|string|max:255',
                'years_of_experience' => 'sometimes|nullable|integer|min:0',
                'bio'                 => 'sometimes|nullable|string',
                'consultation_fee'    => 'sometimes|nullable|numeric|min:0',
                'active'              => 'sometimes|boolean',
                'is_verified'         => 'sometimes|boolean',
                'profile_picture'     => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // Handle profile picture upload
            if ($request->hasFile('profile_picture')) {
                // Delete old picture if it exists
                if ($lawyer->profile_picture_url) {
                    Storage::disk('public')->delete($lawyer->profile_picture_url);
                }
                $validated['profile_picture_url'] = $request->file('profile_picture')
                    ->store('lawyers', 'public');
            }

            // For NOT NULL columns that the client did not send, preserve the existing DB value.
            // This guards against clients that omit years_of_experience on a partial update.
            $notNullDefaults = [
                'years_of_experience' => $lawyer->years_of_experience,
            ];
            foreach ($notNullDefaults as $col => $existingValue) {
                if (array_key_exists($col, $validated) && is_null($validated[$col])) {
                    $validated[$col] = $existingValue;
                }
            }

            // Only write fields that were actually sent
            $lawyer->update($validated);
            $lawyer->refresh();

            return response()->json([
                'success' => true,
                'data'    => $lawyer,
                'message' => 'Lawyer updated successfully',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating lawyer: ' . $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Remove the specified lawyer.
     */
    public function destroy(Lawyer $lawyer): JsonResponse
    {
        try {
            // Delete profile picture
            if ($lawyer->profile_picture_url) {
                Storage::disk('public')->delete($lawyer->profile_picture_url);
            }

            $lawyer->delete();

            return response()->json([
                'success' => true,
                'message' => 'Lawyer deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting lawyer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get lawyer's available slots
     */
    public function availableSlots(Request $request, Lawyer $lawyer): JsonResponse
    {
        try {
            $date = $request->get('date', now()->toDateString());
            
            $slots = $lawyer->availabilitySlots()
                           ->available()
                           ->forDate($date)
                           ->orderBy('start_time')
                           ->get();

            return response()->json([
                'success' => true,
                'data' => $slots,
                'message' => 'Available slots retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving available slots: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify a lawyer
     */
    public function verify(Lawyer $lawyer): JsonResponse
    {
        try {
            $lawyer->update(['is_verified' => true]);

            return response()->json([
                'success' => true,
                'data' => $lawyer,
                'message' => 'Lawyer verified successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error verifying lawyer: ' . $e->getMessage()
            ], 500);
        }
    }


    public function lawyer_with_details(Request $request): JsonResponse
    {
        try {
            $query = Lawyer::query()
                ->select('lawyers.*')
                // Join with lawyer_categories table
                ->leftJoin('lawyer_categories', 'lawyers.id', '=', 'lawyer_categories.lawyer_id')
                // Join with reviews table
                ->leftJoin('reviews', 'lawyers.id', '=', 'reviews.lawyer_id')
                ->with(['reviews', 'availabilitySlots', 'categories']) // Eager load related models
                ->withCount(['reviews', 'appointments']) // Count relations
                ->groupBy('lawyers.id'); // Avoid duplicate lawyers due to joins

            // Filters
            if ($request->has('active')) {
                $query->where('lawyers.active', $request->boolean('active'));
            }

            if ($request->has('verified')) {
                $query->where('lawyers.is_verified', $request->boolean('verified'));
            }

            if ($request->has('specialization')) {
                $query->where('lawyer_categories.specialization', $request->specialization);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('lawyers.full_name', 'LIKE', "%{$search}%")
                        ->orWhere('lawyers.email', 'LIKE', "%{$search}%")
                        ->orWhere('lawyer_categories.specialization', 'LIKE', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'lawyers.created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $lawyers = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $lawyers,
                'message' => 'Lawyers retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving lawyers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get appointments for a specific lawyer, potentially using user ID
     */
    public function appointments(\Illuminate\Http\Request $request, $id): \Illuminate\Http\JsonResponse
    {
        try {
            $lawyer = null;
            if ($id === 'me') {
                $user = auth()->user();
                if ($user) {
                    $lawyer = $user->lawyer;
                }
            } else {
                // Check if $id refers to a User ID or a Lawyer ID
                $lawyer = \App\Models\Lawyer::find($id);
                
                // If not found, check if it's a User ID that has a lawyer profile
                if (!$lawyer) {
                    $user = \App\Models\User::find($id);
                    if ($user) {
                        $lawyer = $user->lawyer;
                    }
                }
            }

            if (!$lawyer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lawyer profile not found'
                ], 404);
            }

            $appointments = \App\Models\Appointment::where('lawyer_id', $lawyer->id)
                ->with(['user:id,name,email,phone'])
                ->orderBy('appointment_time', 'desc')
                ->get()
                ->map(function($apt) {
                    $apt->client_name = $apt->user ? $apt->user->name : 'Client';
                    return $apt;
                });

            return response()->json([
                'success' => true,
                'data' => $appointments,
                'message' => 'Appointments retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving appointments: ' . $e->getMessage()
            ], 500);
        }
    }

    


}