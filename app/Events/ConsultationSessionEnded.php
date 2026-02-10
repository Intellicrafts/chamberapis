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

class ConsultationSessionEnded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $session;
    public $reason;

    /**
     * Create a new event instance.
     */
    public function __construct(ConsultationSession $session, string $reason = 'completed')
    {
        $this->session = $session;
        $this->reason = $reason;
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
        return 'session.ended';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'session_token' => $this->session->session_token,
            'reason' => $this->reason,
            'ended_at' => now()->toISOString(),
            'duration_minutes' => $this->session->actual_start_time
                ? now()->diffInMinutes($this->session->actual_start_time)
                : 0,
        ];
    }
}
