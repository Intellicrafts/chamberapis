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
            $session->loadMissing('appointment', 'appointment.user', 'appointment.lawyer', 'appointment.lawyer.user');

            if ($session->appointment) {
                $whatsAppService = new WhatsAppService();
                $whatsAppService->sendSessionEndedNotification($session->appointment);
            }
        } catch (\Throwable $exception) {
            Log::error('SendSessionEndedWhatsAppNotification failed.', [
                'session_id' => $event->session->id ?? null,
                'error'      => $exception->getMessage(),
            ]);
        }
    }
}
