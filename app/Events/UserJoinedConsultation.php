<?php

namespace App\Events;

use App\Models\ConsultationSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserJoinedConsultation implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $session;
    public $userId;
    public $userName;
    public $userType;

    /**
     * Create a new event instance.
     */
    public function __construct(ConsultationSession $session, int $userId, string $userName, string $userType)
    {
        $this->session = $session;
        $this->userId = $userId;
        $this->userName = $userName;
        $this->userType = $userType;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('consultation.' . $this->session->session_token),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'user.joined';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'user_type' => $this->userType,
            'session_status' => $this->session->status,
            'joined_at' => now()->toISOString(),
        ];
    }
}
