<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
            
            // Store the new avatar
            $path = $request->file('avatar')->store('avatars', 'public');
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
                    'avatar_url' => asset('storage/' . $user->avatar),
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
