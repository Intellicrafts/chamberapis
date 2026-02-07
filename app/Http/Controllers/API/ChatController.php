<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ChatSession;
use App\Models\ChatEvent;
use App\Http\Resources\ChatSessionResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    /**
     * Display a listing of the user's chat sessions.
     */
    public function index(Request $request): JsonResponse
    {
        $sessions = ChatSession::where('user_id', Auth::id())
            ->orderBy('last_message_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => ChatSessionResource::collection($sessions)->response()->getData(true),
            'message' => 'User chat sessions retrieved successfully'
        ]);
    }

    /**
     * Display the specified chat session with its events.
     */
    public function show($id): JsonResponse
    {
        $session = ChatSession::with(['events', 'user'])
            ->where('id', $id)
            ->firstOrFail();

        // Check if user owns the session or is admin
        if ($session->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this session'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new ChatSessionResource($session),
            'message' => 'Chat history retrieved successfully'
        ]);
    }

    /**
     * Display a listing of ALL chat sessions for ADMIN.
     */
    public function adminDashboard(Request $request): JsonResponse
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Admin privileges required'
            ], 403);
        }

        $query = ChatSession::with('user');

        // Optional filters
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $sessions = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => ChatSessionResource::collection($sessions)->response()->getData(true),
            'message' => 'Admin chat dashboard data retrieved'
        ]);
    }

    /**
     * Create a new chat session (useful for testing and actual chatbot start).
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
        ]);

        $session = ChatSession::create([
            'user_id' => Auth::id(),
            'title' => $request->title ?? 'New Conversation',
            'status' => 'active',
            'last_message_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => new ChatSessionResource($session),
            'message' => 'Chat session started'
        ], 201);
    }

    /**
     * Add an event/message to a session.
     */
    public function addEvent(Request $request, $sessionId): JsonResponse
    {
        $session = ChatSession::where('id', $sessionId)->firstOrFail();

        if ($session->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'sender' => 'required|in:user,bot,system',
            'message' => 'required|string',
            'event_type' => 'sometimes|string',
            'metadata' => 'sometimes|array',
        ]);

        $event = $session->events()->create([
            'sender' => $request->sender,
            'message' => $request->message,
            'event_type' => $request->event_type ?? 'message',
            'metadata' => $request->metadata,
        ]);

        $session->update(['last_message_at' => now()]);

        return response()->json([
            'success' => true,
            'data' => $event,
            'message' => 'Message added to session'
        ]);
    }
}
