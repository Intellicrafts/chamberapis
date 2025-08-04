<?php

namespace App\Http\Controllers;

use App\Models\LawyerCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class LawyerCategoryController extends Controller
{
    /**
     * Display a listing of lawyer categories.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = LawyerCategory::query();

            // Search functionality
            if ($request->has('search')) {
                $query->search($request->search);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $categories = $query->with('lawyers')
                               ->orderBy('category_name')
                               ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $categories,
                'message' => 'Categories retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving categories: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'category_name' => 'required|string|max:255|unique:lawyer_categories,category_name',
                'lawyer_id' => 'nullable|uuid|exists:lawyers,id'
            ]);

            $category = LawyerCategory::create($validated);

            return response()->json([
                'success' => true,
                'data' => $category->load('lawyer'),
                'message' => 'Category created successfully'
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
                'message' => 'Error creating category: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified category.
     */
    public function show(LawyerCategory $lawyerCategory): JsonResponse
    {
        try {
            $category = $lawyerCategory->load(['lawyers', 'lawyer']);

            return response()->json([
                'success' => true,
                'data' => $category,
                'message' => 'Category retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving category: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, LawyerCategory $lawyerCategory): JsonResponse
    {
        try {
            $validated = $request->validate([
                'category_name' => 'required|string|max:255|unique:lawyer_categories,category_name,' . $lawyerCategory->id,
                'lawyer_id' => 'nullable|uuid|exists:lawyers,id'
            ]);

            $lawyerCategory->update($validated);

            return response()->json([
                'success' => true,
                'data' => $lawyerCategory->load('lawyer'),
                'message' => 'Category updated successfully'
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
                'message' => 'Error updating category: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified category.
     */
    public function destroy(LawyerCategory $lawyerCategory): JsonResponse
    {
        try {
            $lawyerCategory->delete();

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting category: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all categories for dropdown/select options
     */
    public function options(): JsonResponse
    {
        try {
            $categories = LawyerCategory::select('id', 'category_name')
                                      ->orderBy('category_name')
                                      ->get();

            return response()->json([
                'success' => true,
                'data' => $categories,
                'message' => 'Category options retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving category options: ' . $e->getMessage()
            ], 500);
        }
    }
}