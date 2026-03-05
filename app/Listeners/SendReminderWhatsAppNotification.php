<?php

namespace App\Listeners;

use App\Events\AppointmentStartingSoon;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class SendReminderWhatsAppNotification
{
    public function handle(AppointmentStartingSoon $event): void
    {
        try {
            $appointment = $event->appointment;

            // Ensure relations are loaded
            $appointment->loadMissing(['user', 'lawyer', 'lawyer.user']);

            $service = new WhatsAppService();
            $service->sendReminderNotification($appointment);
        } catch (\Throwable $e) {
            Log::error('SendReminderWhatsAppNotification failed.', [
                'appointment_id' => $event->appointment?->id,
                'error'          => $e->getMessage(),
            ]);
        }
    }
}
