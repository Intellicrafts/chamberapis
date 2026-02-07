<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatSessionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title ?? 'New Conversation',
            'status' => $this->status,
            'last_message_at' => $this->last_message_at?->diffForHumans(),
            'created_at' => $this->created_at->format('M d, Y H:i'),
            'user' => [
                'id' => $this->user_id,
                'name' => $this->user->name ?? 'Unknown',
            ],
            'events_count' => $this->events_count ?? $this->events()->count(),
            'messages' => $this->whenLoaded('events', function () {
                return $this->events->map(function ($event) {
                    return [
                        'id' => $event->id,
                        'sender' => $event->sender,
                        'message' => $event->message,
                        'type' => $event->event_type,
                        'metadata' => $event->metadata,
                        'timestamp' => $event->created_at->format('H:i'),
                    ];
                });
            }),
        ];
    }
}
