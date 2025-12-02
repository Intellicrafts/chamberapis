<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ChatSession;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Collection;

class ConversationController extends Controller
{
    /**
     * Fetch all user conversations grouped by session with events as values
     * 
     * This method retrieves AI chatbot conversation data for a specific user,
     * organized by chat sessions with all events (messages) within each session.
     * 
     * @param Request $request HTTP request containing user_id
     * @return JsonResponse Formatted conversation data or error response
     */
    public function getUserConversations(Request $request): JsonResponse
    {
        try {
            $userId = $request->get('user_id');

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'user_id parameter is required'
                ], 422);
            }

            $user = User::find($userId);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $conversations = $this->formatConversations($userId);

            return response()->json([
                'success' => true,
                'data' => $conversations,
                'message' => 'Conversations retrieved successfully',
                'total_sessions' => count($conversations),
                'total_events' => collect($conversations)->sum(fn($session) => count($session['events']))
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving conversations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch a single session with all its events
     * 
     * Returns detailed information about a specific chat session including
     * all chat events (messages) ordered chronologically.
     * 
     * @param Request $request HTTP request containing session_id
     * @return JsonResponse Session details with events or error response
     */
    public function getSessionConversation(Request $request): JsonResponse
    {
        try {
            $sessionId = $request->get('session_id');

            if (!$sessionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'session_id parameter is required'
                ], 422);
            }

            $session = ChatSession::find($sessionId);
            if (!$session) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session not found'
                ], 404);
            }

            $conversation = $this->formatSessionData($session);

            return response()->json([
                'success' => true,
                'data' => $conversation,
                'message' => 'Session conversation retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving session conversation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch conversations with pagination support
     * 
     * Retrieves user conversations with optional filtering and pagination.
     * Useful for paginated display in React components.
     * 
     * @param Request $request HTTP request with user_id and optional pagination params
     * @return JsonResponse Paginated conversation data or error response
     */
    public function getUserConversationsPaginated(Request $request): JsonResponse
    {
        try {
            $userId = $request->get('user_id');
            $perPage = $request->get('per_page', 10);
            $sortBy = $request->get('sort_by', 'last_activity');
            $sortOrder = $request->get('sort_order', 'desc');

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'user_id parameter is required'
                ], 422);
            }

            $user = User::find($userId);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $sessions = ChatSession::forUser($userId)
                                   ->withConversations()
                                   ->with(['chatEvents' => function ($query) {
                                       $query->orderBy('occurred_at', 'asc');
                                   }])
                                   ->orderBy($sortBy, $sortOrder)
                                   ->paginate($perPage);

            $formattedSessions = $sessions->map(fn($session) => $this->formatSessionData($session));

            return response()->json([
                'success' => true,
                'data' => $formattedSessions,
                'pagination' => [
                    'total' => $sessions->total(),
                    'per_page' => $sessions->per_page(),
                    'current_page' => $sessions->current_page(),
                    'last_page' => $sessions->last_page(),
                    'from' => $sessions->from(),
                    'to' => $sessions->to(),
                ],
                'message' => 'Paginated conversations retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving paginated conversations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format all conversations for a user
     * 
     * Transforms raw database records into a structured format suitable for
     * React consumption: sessions as keys with events as nested arrays.
     * 
     * @param int $userId The ID of the user
     * @return array Formatted conversation data grouped by session
     */
    private function formatConversations(int $userId): array
    {
        $sessions = ChatSession::forUser($userId)
                               ->withConversations()
                               ->with(['chatEvents' => function ($query) {
                                   $query->orderBy('occurred_at', 'asc');
                               }])
                               ->orderBy('last_activity', 'desc')
                               ->get();

        return $sessions->map(fn($session) => $this->formatSessionData($session))->toArray();
    }

    /**
     * Format individual session data
     * 
     * Structures a single session with all its events for easy frontend consumption.
     * Returns session metadata and chronologically ordered chat events.
     * 
     * @param ChatSession $session The session to format
     * @return array Formatted session data with events
     */
    private function formatSessionData(ChatSession $session): array
    {
        $events = $session->chatEvents;

        return [
            'session' => [
                'id' => $session->id,
                'user_id' => $session->user_id,
                'ip_address' => $session->ip_address,
                'user_agent' => $session->user_agent,
                'last_activity' => \Carbon\Carbon::createFromTimestamp($session->last_activity)->toIso8601String(),
                'created_at' => \Carbon\Carbon::createFromTimestamp($session->last_activity)->toIso8601String(),
            ],
            'events' => $events->map(fn($event) => [
                'id' => $event->id,
                'event_type' => $event->event_type,
                'event_name' => $event->event_name,
                'data' => $event->event_data ?? [],
                'occurred_at' => $event->occurred_at->toIso8601String(),
                'created_at' => $event->created_at->toIso8601String(),
            ])->toArray(),
            'event_count' => $events->count(),
        ];
    }

    /**
     * Export user conversations as JSON
     * 
     * Provides a downloadable export of all user conversations in JSON format
     * for record keeping or analysis purposes.
     * 
     * @param Request $request HTTP request containing user_id
     * @return JsonResponse Export data or error response
     */
    public function exportUserConversations(Request $request): JsonResponse
    {
        try {
            $userId = $request->get('user_id');

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'user_id parameter is required'
                ], 422);
            }

            $user = User::find($userId);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $conversations = $this->formatConversations($userId);

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                    'exported_at' => now()->toIso8601String(),
                    'conversations' => $conversations,
                ],
                'message' => 'Conversations exported successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting conversations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get conversation statistics for a user
     * 
     * Provides analytics about user's chatbot interactions including
     * total sessions, messages, event types breakdown, etc.
     * 
     * @param Request $request HTTP request containing user_id
     * @return JsonResponse Statistics data or error response
     */
    public function getConversationStats(Request $request): JsonResponse
    {
        try {
            $userId = $request->get('user_id');

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'user_id parameter is required'
                ], 422);
            }

            $user = User::find($userId);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $totalSessions = ChatSession::forUser($userId)
                                        ->withConversations()
                                        ->count();

            $totalEvents = Event::where('user_id', $userId)
                               ->where('event_type', 'chat')
                               ->count();

            $eventTypesBreakdown = Event::where('user_id', $userId)
                                       ->where('event_type', 'chat')
                                       ->selectRaw('event_name, COUNT(*) as count')
                                       ->groupBy('event_name')
                                       ->pluck('count', 'event_name')
                                       ->toArray();

            $oldestEvent = Event::where('user_id', $userId)
                               ->where('event_type', 'chat')
                               ->orderBy('occurred_at', 'asc')
                               ->first();

            $newestEvent = Event::where('user_id', $userId)
                               ->where('event_type', 'chat')
                               ->orderBy('occurred_at', 'desc')
                               ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_sessions' => $totalSessions,
                    'total_events' => $totalEvents,
                    'event_types' => $eventTypesBreakdown,
                    'oldest_interaction' => $oldestEvent?->occurred_at->toIso8601String(),
                    'newest_interaction' => $newestEvent?->occurred_at->toIso8601String(),
                ],
                'message' => 'Conversation statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving conversation statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}
