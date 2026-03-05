<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Fires 3 minutes after a participant joins a session.
 * If the OTHER party STILL hasn't joined, sends a WhatsApp alert.
 */
class SendDelayedJoinAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $appointmentId,
        public string $joinedParty,   // 'client' or 'lawyer'
    ) {}

    public function handle(): void
    {
        try {
            $appointment = Appointment::with(['user', 'lawyer', 'lawyer.user', 'consultationSession'])
                ->find($this->appointmentId);

            if (!$appointment) return;

            $session = $appointment->consultationSession;

            if (!$session) return;

            // Re-check: has the other party joined by now?
            $otherHasJoined = ($this->joinedParty === 'client')
                ? !empty($session->lawyer_joined_at)
                : !empty($session->user_joined_at);

            if ($otherHasJoined) {
                // Both joined — no alert needed
                Log::info('SendDelayedJoinAlert: other party joined, skipping.', [
                    'appointment_id' => $this->appointmentId,
                ]);
                return;
            }

            // Session already ended — no point sending
            if (in_array($session->status, ['ended', 'expired', 'cancelled'])) {
                return;
            }

            $service = new WhatsAppService();
            $service->sendJoinAlertNotification($appointment, $this->joinedParty);

        } catch (\Throwable $e) {
            Log::error('SendDelayedJoinAlert failed.', [
                'appointment_id' => $this->appointmentId,
                'joined_party'   => $this->joinedParty,
                'error'          => $e->getMessage(),
            ]);
        }
    }
}
