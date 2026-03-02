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
            $whatsAppService = new WhatsAppService();

            // 1. Notify Client (Job dispatched internally)
            $whatsAppService->sendAppointmentConfirmationToClient($event->client, $event->appointment);

            // 2. Notify Lawyer (Job dispatched internally)
            $whatsAppService->sendAppointmentNotificationToLawyer($event->lawyer, $event->appointment);

        } catch (\Throwable $exception) {
            Log::error('SendAppointmentWhatsAppNotification execution failed.', [
                'appointment_id' => $event->appointment->id ?? null,
                'error'          => $exception->getMessage(),
            ]);
        }
    }
}
