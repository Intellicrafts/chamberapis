<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\LegalQuery;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class LegalQueryController extends Controller
{
    /**
     * Display a listing of legal queries.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = LegalQuery::with('user:id,name');

            // Filter by user
            if ($request->has('user_id')) {
                $query->forUser($request->user_id);
            }

            // Filter by response status
            if ($request->has('has_response')) {
                if ($request->boolean('has_response')) {
                    $query->withResponse();
                } else {
                    $query->withoutResponse();
                }
            }

            // Filter by date range
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->createdBetween($request->start_date, $request->end_date);
            }

            // Filter by search term
            if ($request->has('search')) {
                $query->search($request->search);
            }

            // Filter by recent
            if ($request->has('recent_days')) {
                $query->recent($request->recent_days);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDir = $request->get('sort_dir', 'desc');
            $query->orderBy($sortBy, $sortDir);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $queries = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $queries,
                'message' => 'Legal queries retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving legal queries: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created legal query.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|uuid|exists:users,id',
                'question_text' => 'required|string|min:10',
                'ai_response' => 'nullable|string',
            ]);

            $query = LegalQuery::create($validated);
            $query->load('user:id,name');

            return response()->json([
                'success' => true,
                'data' => $query,
                'message' => 'Legal query created successfully'
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
                'message' => 'Error creating legal query: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified legal query.
     */
    public function show(LegalQuery $legalQuery): JsonResponse
    {
        try {
            $legalQuery->load('user:id,name');

            return response()->json([
                'success' => true,
                'data' => $legalQuery,
                'message' => 'Legal query retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving legal query: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified legal query.
     */
    public function update(Request $request, LegalQuery $legalQuery): JsonResponse
    {
        try {
            $validated = $request->validate([
                'question_text' => 'sometimes|string|min:10',
                'ai_response' => 'nullable|string',
            ]);

            $legalQuery->update($validated);
            $legalQuery->load('user:id,name');

            return response()->json([
                'success' => true,
                'data' => $legalQuery,
                'message' => 'Legal query updated successfully'
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
                'message' => 'Error updating legal query: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified legal query.
     */
    public function destroy(LegalQuery $legalQuery): JsonResponse
    {
        try {
            $legalQuery->delete();

            return response()->json([
                'success' => true,
                'message' => 'Legal query deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting legal query: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate AI response for a legal query.
     */
    public function generateAiResponse(LegalQuery $legalQuery): JsonResponse
    {
        try {
            // This would typically integrate with an AI service
            // For now, generate a placeholder response
            $response = "This is a placeholder AI response for the query: '{$legalQuery->question_text}'";
            
            $legalQuery->setAiResponse($response);
            $legalQuery->load('user:id,name');

            return response()->json([
                'success' => true,
                'data' => $legalQuery,
                'message' => 'AI response generated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating AI response: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear AI response for a legal query.
     */
    public function clearAiResponse(LegalQuery $legalQuery): JsonResponse
    {
        try {
            $legalQuery->clearAiResponse();
            $legalQuery->load('user:id,name');

            return response()->json([
                'success' => true,
                'data' => $legalQuery,
                'message' => 'AI response cleared successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error clearing AI response: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Regenerate AI response for a legal query.
     */
    public function regenerateAiResponse(LegalQuery $legalQuery): JsonResponse
    {
        try {
            // This would typically integrate with an AI service
            // For now, generate a placeholder response
            $response = "This is a regenerated AI response for the query: '{$legalQuery->question_text}'";
            
            $legalQuery->setAiResponse($response);
            $legalQuery->load('user:id,name');

            return response()->json([
                'success' => true,
                'data' => $legalQuery,
                'message' => 'AI response regenerated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error regenerating AI response: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get legal queries for a specific user.
     */
    public function getUserQueries(string $userId): JsonResponse
    {
        try {
            $queries = LegalQuery::forUser($userId)
                ->with('user:id,name')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $queries,
                'message' => 'User queries retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving user queries: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search legal queries by text.
     */
    public function searchQueries(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'search_term' => 'required|string|min:3',
                'search_in' => 'sometimes|string|in:question,response,both',
            ]);

            $searchIn = $request->get('search_in', 'both');
            $query = LegalQuery::with('user:id,name');

            if ($searchIn === 'question') {
                $query->searchInQuestion($validated['search_term']);
            } elseif ($searchIn === 'response') {
                $query->searchInResponse($validated['search_term']);
            } else {
                $query->search($validated['search_term']);
            }

            $queries = $query->orderBy('created_at', 'desc')->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $queries,
                'message' => 'Search results retrieved successfully'
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
                'message' => 'Error searching queries: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get similar queries to a specific query.
     */
    public function getSimilarQueries(LegalQuery $legalQuery): JsonResponse
    {
        try {
            $similarQueries = $legalQuery->getSimilarQueries(5);
            
            return response()->json([
                'success' => true,
                'data' => $similarQueries,
                'message' => 'Similar queries retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving similar queries: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get analytics for legal queries.
     */
    public function getAnalytics(): JsonResponse
    {
        try {
            $totalQueries = LegalQuery::count();
            $queriesWithResponse = LegalQuery::withResponse()->count();
            $queriesWithoutResponse = LegalQuery::withoutResponse()->count();
            $recentQueries = LegalQuery::recent(30)->count();
            
            $dailyStats = LegalQuery::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->limit(30)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_queries' => $totalQueries,
                    'queries_with_response' => $queriesWithResponse,
                    'queries_without_response' => $queriesWithoutResponse,
                    'response_rate' => $totalQueries > 0 ? ($queriesWithResponse / $totalQueries) * 100 : 0,
                    'recent_queries' => $recentQueries,
                    'daily_stats' => $dailyStats
                ],
                'message' => 'Analytics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export user queries.
     */
    public function exportUserQueries(string $userId): JsonResponse
    {
        try {
            $queries = LegalQuery::forUser($userId)
                ->with('user:id,name')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($query) {
                    return $query->toAnalyticsArray();
                });

            return response()->json([
                'success' => true,
                'data' => $queries,
                'message' => 'User queries exported successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting user queries: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk generate AI responses for multiple queries.
     */
    public function bulkGenerateResponses(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'query_ids' => 'required|array',
                'query_ids.*' => 'required|uuid|exists:legal_queries,id',
            ]);

            $queries = LegalQuery::whereIn('id', $validated['query_ids'])
                ->withoutResponse()
                ->get();

            foreach ($queries as $query) {
                // This would typically integrate with an AI service
                // For now, generate a placeholder response
                $response = "This is a bulk-generated AI response for the query: '{$query->question_text}'";
                $query->setAiResponse($response);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'processed_count' => $queries->count(),
                    'queries' => $queries
                ],
                'message' => 'Bulk AI responses generated successfully'
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
                'message' => 'Error generating bulk AI responses: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete legal queries.
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'query_ids' => 'required|array',
                'query_ids.*' => 'required|uuid|exists:legal_queries,id',
            ]);

            $deletedCount = LegalQuery::whereIn('id', $validated['query_ids'])->delete();

            return response()->json([
                'success' => true,
                'data' => [
                    'deleted_count' => $deletedCount
                ],
                'message' => 'Queries deleted successfully'
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
                'message' => 'Error deleting queries: ' . $e->getMessage()
            ], 500);
        }
    }
}