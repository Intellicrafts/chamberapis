<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ConsultationSession;
use App\Models\ConsultationMessage;
use App\Events\ConsultationMessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ConsultationMessageController extends Controller
{
    /**
     * Get all messages for a session
     */
    public function index(Request $request, $sessionToken)
    {
        $session = ConsultationSession::where('session_token', $sessionToken)->firstOrFail();

        // Verify user is a participant
        if (!$session->isParticipant(auth()->id())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $messages = $session->messages()
            ->with('sender:id,name,avatar')
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark messages as read for current user
        $this->markMessagesAsRead($session, auth()->id());

        return response()->json([
            'messages' => $messages,
            'total' => $messages->count(),
        ]);
    }

    /**
     * Send a message in the session
     */
    public function store(Request $request, $sessionToken)
    {
        $session = ConsultationSession::where('session_token', $sessionToken)->firstOrFail();
        $userId = auth()->id();

        // Verify user is a participant
        if (!$session->isParticipant($userId)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Check if session is active
        if (!$session->isActive()) {
            return response()->json([
                'error' => 'Session is not active'
            ], 400);
        }

        $request->validate([
            'content' => 'required_without:file|string|max:5000',
            'message_type' => 'sometimes|string|in:text,file',
            'file' => 'sometimes|file|max:10240', // 10MB max
        ]);

        $messageData = [
            'consultation_session_id' => $session->id,
            'sender_id' => $userId,
            'sender_type' => $userId == $session->user_id ? 'user' : 'lawyer',
            'message_type' => $request->input('message_type', 'text'),
            'content' => $request->input('content', ''),
            'is_read' => false,
        ];

        // Handle file upload
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('consultation_files', 'public');

            $messageData['file_path'] = $path;
            $messageData['file_name'] = $file->getClientOriginalName();
            $messageData['file_type'] = $file->getMimeType();
            $messageData['file_size'] = $file->getSize();
            $messageData['message_type'] = 'file';
            
            if (empty($messageData['content'])) {
                $messageData['content'] = 'Sent a file: ' . $file->getClientOriginalName();
            }
        }

        $message = ConsultationMessage::create($messageData);

        // Load sender relationship
        $message->load('sender:id,name,avatar');

        // Broadcast the message via WebSockets (if configured)
        try {
            broadcast(new ConsultationMessageSent($message))->toOthers();
        } catch (\Exception $e) {
            Log::info('Broadcasting not configured, using polling fallback: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Message sent successfully',
            'data' => $message,
        ], 201);
    }

    /**
     * Mark message as read
     */
    public function markAsRead(Request $request, $sessionToken, $messageId)
    {
        $session = ConsultationSession::where('session_token', $sessionToken)->firstOrFail();
        $userId = auth()->id();

        // Verify user is a participant
        if (!$session->isParticipant($userId)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $message = ConsultationMessage::where('consultation_session_id', $session->id)
            ->findOrFail($messageId);

        // Only allow reading messages sent by other participant
        if ($message->sender_id != $userId) {
            $message->markAsRead();
        }

        return response()->json([
            'message' => 'Message marked as read',
        ]);
    }

    /**
     * Mark all messages in session as read for current user
     */
    protected function markMessagesAsRead(ConsultationSession $session, int $userId)
    {
        // Mark all unread messages from other participant as read
        ConsultationMessage::where('consultation_session_id', $session->id)
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Get unread message count for a session
     */
    public function getUnreadCount(Request $request, $sessionToken)
    {
        $session = ConsultationSession::where('session_token', $sessionToken)->firstOrFail();
        $userId = auth()->id();

        // Verify user is a participant
        if (!$session->isParticipant($userId)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $unreadCount = $session->messages()
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Broadcast typing indicator
     */
    public function typing(Request $request, $sessionToken)
    {
        $session = ConsultationSession::where('session_token', $sessionToken)->firstOrFail();
        $userId = auth()->id();

        // Verify user is a participant
        if (!$session->isParticipant($userId)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'is_typing' => 'required|boolean',
        ]);

        $senderType = $userId == $session->user_id ? 'user' : 'lawyer';

        // Here you would broadcast typing indicator via WebSockets
        // broadcast(new UserTyping($session->session_token, $senderType, $request->is_typing));

        return response()->json([
            'message' => 'Typing status updated',
        ]);
    }
}
