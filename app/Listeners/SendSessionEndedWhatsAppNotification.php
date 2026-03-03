<?php

namespace App\Listeners;

use App\Events\ConsultationSessionEnded;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class SendSessionEndedWhatsAppNotification
{
    public function handle(ConsultationSessionEnded $event): void
    {
        try {
            $session = $event->session;

            $session->loadMissing([
                'appointment',
                'appointment.user',
                'appointment.lawyer',
                'appointment.lawyer.user',
            ]);

            $appointment = $session->appointment;
            if (!$appointment) {
                return;
            }

            $service = new WhatsAppService();
            $service->sendSessionEndedNotification($appointment);

        } catch (\Throwable $e) {
            Log::error('SendSessionEndedWhatsAppNotification failed.', [
                'session_id' => $event->session?->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
