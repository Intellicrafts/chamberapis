<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    /**
     * List all notifications (optional pagination).
     */
    public function index(Request $request)
    {
        $notifications = Notification::latest()->paginate(20);

        return response()->json($notifications);
    }

    /**
     * Show notifications for a specific user.
     */
    public function userNotifications($userId)
    {
        $notifications = Notification::where('user_id', $userId)
            ->where('status', 'unread')
            ->latest()
            ->paginate(20);

        return response()->json($notifications);
    }

    /**
     * Store a new notification.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'     => 'required|exists:users,id',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'status'      => 'in:read,unread',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $notification = Notification::create($validator->validated());

        return response()->json(['message' => 'Notification created.', 'data' => $notification], 201);
    }

    /**
     * Show a single notification.
     */
    public function show($id)
    {
        $notification = Notification::findOrFail($id);
        return response()->json($notification);
    }

    /**
     * Update a notification.
     */
    public function update(Request $request, $id)
    {
        $notification = Notification::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status'      => 'in:read,unread',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $notification->update($validator->validated());

        return response()->json(['message' => 'Notification updated.', 'data' => $notification]);
    }

    /**
     * Delete a notification.
     */
    public function destroy($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->delete();

        return response()->json(['message' => 'Notification deleted.']);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read.']);
    }

    /**
     * Mark a notification as unread.
     */
    public function markAsUnread($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->markAsUnread();

        return response()->json(['message' => 'Notification marked as unread.']);
    }

    /**
     * Get unread notifications for a user.
     */
    public function unreadByUser($userId)
    {
        $notifications = Notification::where('user_id', $userId)
            ->unread()
            ->latest()
            ->paginate(20);

        return response()->json($notifications);
    }

    /**
     * Get read notifications for a user.
     */
    public function readByUser($userId)
    {
        $notifications = Notification::where('user_id', $userId)
            ->read()
            ->latest()
            ->paginate(20);

        return response()->json($notifications);
    }
}
