<?php

namespace App\Listeners;

use App\Events\AppointmentBooked;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class SendAppointmentWhatsAppNotification
{
    public function handle(AppointmentBooked $event): void
    {
        try {
            $appointment = $event->appointment;

            // Ensure relations are loaded so phone numbers and names are available
            $appointment->loadMissing(['user', 'lawyer', 'lawyer.user']);

            $service = new WhatsAppService();

            // One message to client, one message to lawyer — no more
            $service->sendAppointmentConfirmationToClient($event->client, $appointment);
            $service->sendAppointmentNotificationToLawyer($event->lawyer, $appointment);

        } catch (\Throwable $e) {
            Log::error('SendAppointmentWhatsAppNotification failed.', [
                'appointment_id' => $event->appointment?->id,
                'error'          => $e->getMessage(),
                'trace'          => $e->getTraceAsString(),
            ]);
        }
    }
}
