<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Lawyer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'full_name' => 'required|string|max:255',
                'email' => 'required|email|unique:lawyers,email',
                'phone_number' => 'nullable|string|max:20',
                'password' => 'required|string|min:8',
                'license_number' => 'required|string|unique:lawyers,license_number',
                'bar_association' => 'nullable|string|max:255',
                'specialization' => 'nullable|string|max:255',
                'years_of_experience' => 'nullable|integer|min:0',
                'bio' => 'nullable|string',
                'consultation_fee' => 'nullable|numeric|min:0',
                'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            // Handle profile picture upload
            if ($request->hasFile('profile_picture')) {
                $validated['profile_picture_url'] = $request->file('profile_picture')
                    ->store('lawyers', 'public');
            }

            // Hash password
            $validated['password_hash'] = Hash::make($validated['password']);
            unset($validated['password']);

            $lawyer = Lawyer::create($validated);

            return response()->json([
                'success' => true,
                'data' => $lawyer,
                'message' => 'Lawyer created successfully'
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
                'message' => 'Error creating lawyer: ' . $e->getMessage()
            ], 500);
        }
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
     */
    public function update(Request $request, Lawyer $lawyer): JsonResponse
    {
        try {
            $validated = $request->validate([
                'full_name' => 'required|string|max:255',
                'email' => 'required|email|unique:lawyers,email,' . $lawyer->id,
                'phone_number' => 'nullable|string|max:20',
                'license_number' => 'required|string|unique:lawyers,license_number,' . $lawyer->id,
                'bar_association' => 'nullable|string|max:255',
                'specialization' => 'nullable|string|max:255',
                'years_of_experience' => 'nullable|integer|min:0',
                'bio' => 'nullable|string',
                'consultation_fee' => 'nullable|numeric|min:0',
                'active' => 'boolean',
                'is_verified' => 'boolean',
                'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            // Handle profile picture upload
            if ($request->hasFile('profile_picture')) {
                // Delete old picture
                if ($lawyer->profile_picture_url) {
                    Storage::disk('public')->delete($lawyer->profile_picture_url);
                }
                
                $validated['profile_picture_url'] = $request->file('profile_picture')
                    ->store('lawyers', 'public');
            }

            $lawyer->update($validated);

            return response()->json([
                'success' => true,
                'data' => $lawyer,
                'message' => 'Lawyer updated successfully'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating lawyer: ' . $e->getMessage()
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

    


}