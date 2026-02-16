<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Log;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::all());
    }

    /**
     * Get a specific user by ID
     * 
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(User $user)
    {
        // Load relationships if needed
        $user->load([
            'appointments' => function($query) {
                $query->latest()->take(5);
            },
            'legalQueries' => function($query) {
                $query->latest()->take(5);
            },
            'reviews' => function($query) {
                $query->latest()->take(5);
            }
        ]);
        
        return response()->json([
            'status' => 'success',
            'data' => $user,
            'message' => 'User retrieved successfully'
        ]);
    }

    /**
     * Get the currently authenticated user's profile
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
 public function profile(Request $request)
{
    $user = $request->user();
    $userData = [];

    // Load common relationships with nested lawyer info
    $user->load([
        'appointments' => function ($query) {
            $query->with([
                'lawyer:id,full_name,email,phone_number,specialization,profile_picture_url'
            ])
            ->orderBy('appointment_time', 'desc')
            ->take(5);
        },
        'legalQueries' => function ($query) {
            $query->latest()->take(5);
        },
        'reviews' => function ($query) {
            $query->latest()->take(5);
        }
    ]);

    // Calculate appointment status counts
    $query = \App\Models\Appointment::query();
    
    if ($user->isLawyer()) {
        $lawyer = $user->lawyer;
        if ($lawyer) {
            $query->where('lawyer_id', $lawyer->id);
        } else {
            $query->where('user_id', $user->id);
        }
    } else {
        $query->where('user_id', $user->id);
    }

    $appointmentCounts = $query->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN status = "scheduled" THEN 1 ELSE 0 END) as scheduled,
            SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled,
            SUM(CASE WHEN status = "no-show" THEN 1 ELSE 0 END) as no_show,
            SUM(CASE WHEN status = "in-progress" THEN 1 ELSE 0 END) as in_progress
        ')
        ->first();

    // Basic user info
    $userData = [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'phone' => $user->phone,
        'address' => $user->address,
        'city' => $user->city,
        'state' => $user->state,
        'country' => $user->country,
        'zip_code' => $user->zip_code,
        'full_address' => $user->full_address,
        'active' => $user->active,
        'is_verified' => $user->is_verified,
        'avatar' => $user->avatar,
        'avatar_url' => asset('storage/'.$user->avatar),
        'user_type' => $user->user_type,
        'user_type_name' => $this->getUserTypeName($user->user_type),
        'email_verified_at' => $user->email_verified_at,
        'created_at' => $user->created_at,
        'updated_at' => $user->updated_at,
    ];

    // Lawyer specific data
    if ($user->isLawyer()) {
        $lawyer = \App\Models\Lawyer::where('email', $user->email)->first();
        if ($lawyer) {
            $userData['lawyer_data'] = [
                'license_number' => $lawyer->license_number,
                'bar_association' => $lawyer->bar_association,
                'specialization' => $lawyer->specialization,
                'years_of_experience' => $lawyer->years_of_experience,
                'bio' => $lawyer->bio,
                'profile_picture_url' => $lawyer->profile_picture,
                'consultation_fee' => $lawyer->consultation_fee,
                'average_rating' => $lawyer->average_rating,
                'total_reviews' => $lawyer->total_reviews,
            ];
        }
    }

    // For lawyer, appointments should be those where they are the lawyer
    $appointments = $user->appointments;
    if ($user->isLawyer()) {
        $lawyer = $user->lawyer;
        if ($lawyer) {
            $appointments = \App\Models\Appointment::where('lawyer_id', $lawyer->id)
                ->with(['user:id,name,email,phone'])
                ->orderBy('appointment_time', 'desc')
                ->take(10)
                ->get();
        }
    }

    // Add recent activity + appointment stats
    $userData['recent_activity'] = [
        'appointments' => $appointments,
        'legal_queries' => $user->legalQueries,
        'reviews' => $user->reviews,
        'appointment_summary' => [
            'total' => $appointmentCounts->total ?? 0,
            'scheduled' => $appointmentCounts->scheduled ?? 0,
            'completed' => $appointmentCounts->completed ?? 0,
            'cancelled' => $appointmentCounts->cancelled ?? 0,
            'no_show' => $appointmentCounts->no_show ?? 0,
            'in_progress' => $appointmentCounts->in_progress ?? 0,
        ],
    ];

    return response()->json([
        'status' => 'success',
        'data' => $userData,
        'message' => 'User profile retrieved successfully'
    ]);
}

    
    /**
     * Get user type name based on user_type value
     *
     * @param int $userType
     * @return string
     */
    private function getUserTypeName(int $userType): string
    {
        switch ($userType) {
            case User::USER_TYPE_CLIENT:
                return 'Client';
            case User::USER_TYPE_LAWYER:
                return 'Lawyer';
            case User::USER_TYPE_ADMIN:
                return 'Admin';
            default:
                return 'Unknown';
        }
    }
    
    /**
     * Update the authenticated user's profile
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        // Validate basic user data
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'country' => 'nullable|string',
            'zip_code' => 'nullable|string',
            // 'avatar' => 'nullable|string',
            'current_password' => 'sometimes|required_with:password|string',
            'password' => 'sometimes|nullable|string|min:8|confirmed',
        ]);
        
        // Verify current password if trying to update password
        if (!empty($validated['current_password']) && !empty($validated['password'])) {
            if (!Hash::check($validated['current_password'], $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Current password is incorrect'
                ], 422);
            }
            
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }
        
        // Remove current_password from validated data as it's not a column in the users table
        unset($validated['current_password']);
        
        // Update user data
        $user->update($validated);
        
        // Handle lawyer-specific data if the user is a lawyer
        if ($user->isLawyer() && $request->has('lawyer_data')) {
            $lawyerData = $request->validate([
                'lawyer_data.bio' => 'nullable|string',
                'lawyer_data.specialization' => 'nullable|string',
                'lawyer_data.bar_association' => 'nullable|string',
                'lawyer_data.consultation_fee' => 'nullable|numeric',
            ]);
            
            if (!empty($lawyerData['lawyer_data'])) {
                $lawyer = \App\Models\Lawyer::where('email', $user->email)->first();
                if ($lawyer) {
                    $lawyer->update($lawyerData['lawyer_data']);
                }
            }
        }
        
        // Reload user with fresh data
        $user = $user->fresh();
        
        // Format response data similar to profile method
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => $user->address,
            'city' => $user->city,
            'state' => $user->state,
            'country' => $user->country,
            'zip_code' => $user->zip_code,
            'full_address' => $user->full_address,
            'active' => $user->active,
            'is_verified' => $user->is_verified,
            'avatar' => $user->avatar,
            'avatar_url' => $user->avatar_url,
            'user_type' => $user->user_type,
            'user_type_name' => $this->getUserTypeName($user->user_type),
            'email_verified_at' => $user->email_verified_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
        
        return response()->json([
            'status' => 'success',
            'data' => $userData,
            'message' => 'Profile updated successfully'
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|string|min:6',
            'phone'      => 'nullable|string',
            'user_type'  => 'required|integer|in:1,2,3', // 1=client, 2=lawyer, 3=admin
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        return response()->json($user, 201);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'      => 'sometimes|string|max:255',
            'email'     => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'password'  => 'sometimes|nullable|string|min:6',
            'phone'     => 'nullable|string',
            'user_type' => 'sometimes|integer|in:1,2,3',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json($user);
    }

    public function destroy(User $user)
    {
        $user->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }
    
    /**
     * Update user avatar
     *
     * @param Request $request
     * @param User $user (optional)
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateAvatar(Request $request, User $user = null)
    {
        // If no user is provided, use the authenticated user
        if (!$user) {
            $user = auth()->user();
        }
        
        // Validate the request
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        
        // Handle file upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists and is not a URL
            if ($user->avatar && !str_starts_with($user->avatar, 'http')) {
                // Check if it's not the default avatar
                if ($user->avatar !== 'default-avatar.png') {
                    // Try both storage paths for compatibility
                    $oldPath = 'public/' . $user->avatar;
                    $oldPathAvatars = 'public/avatars/' . $user->avatar;
                    
                    if (Storage::exists($oldPath)) {
                        Storage::delete($oldPath);
                    } elseif (Storage::exists($oldPathAvatars)) {
                        Storage::delete($oldPathAvatars);
                    }
                }
            }

            // Ensure the directory exists
            $path = $request->file('avatar')->store('avatars', 'public');
            Log::info('Avatar stored at: ' . $path);
            $user->avatar = $path;
            $user->save();
            
            // Get the base URL without any api prefix
            $baseUrl = url('/');
            $storageUrl = $baseUrl . '/storage/' . $path;
            
            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'Avatar updated successfully',
                'data' => [
                    'avatar' => $user->avatar,
                    'avatar_url' => $storageUrl,
                    'user' => $user
                ]
            ]);
        }
        
        return response()->json([
            'success' => false,
            'status' => 'error',
            'message' => 'No avatar file provided'
        ], 400);
    }
    
    /**
     * Legacy method for backward compatibility
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadAvatar(Request $request)
    {
        return $this->updateAvatar($request);
    }
    
    /**
     * Change user password
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);
        
        $user = $request->user();
        
        // Verify current password
        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Current password is incorrect'
            ], 422);
        }
        
        // Update password
        $user->password = Hash::make($validated['password']);
        $user->save();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Password changed successfully'
        ]);
    }
}
