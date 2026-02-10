<?php

namespace App\Events;

use App\Models\ConsultationMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConsultationMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(ConsultationMessage $message)
    {
        $this->message = $message->load('sender:id,name,avatar');
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('consultation.' . $this->message->session->session_token),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'content' => $this->message->content,
            'sender_type' => $this->message->sender_type,
            'sender_id' => $this->message->sender_id,
            'sender' => $this->message->sender ? [
                'id' => $this->message->sender->id,
                'name' => $this->message->sender->name,
                'avatar' => $this->message->sender->avatar ?? null,
            ] : null,
            'message_type' => $this->message->message_type,
            'file_name' => $this->message->file_name,
            'file_path' => $this->message->file_path,
            'file_type' => $this->message->file_type,
            'created_at' => $this->message->created_at->toISOString(),
        ];
    }
}
