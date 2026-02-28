<?php

namespace App\Listeners;

use App\Events\AppointmentBooked;
use App\Jobs\SendWhatsAppMessage;
use Illuminate\Support\Facades\Log;

class SendAppointmentWhatsAppNotification
{
    public function handle(AppointmentBooked $event): void
    {
        $appointmentDate = $event->appointment->appointment_time?->format('Y-m-d') ?? '';
        $appointmentTime = $event->appointment->appointment_time?->format('h:i A') ?? '';

        $clientPhone = $event->client->phone;
        if (!empty($clientPhone)) {
            try {
                SendWhatsAppMessage::dispatch(
                    $clientPhone,
                    [
                        '1' => $appointmentDate,
                        '2' => $appointmentTime,
                    ],
                    'appointment_confirmation_client',
                    (int) $event->appointment->id
                );
            } catch (\Throwable $exception) {
                Log::error('Failed to dispatch client WhatsApp message job', [
                    'appointment_id' => $event->appointment->id,
                    'client_id' => $event->client->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $lawyerPhone = $event->lawyer->phone_number ?: $event->lawyer->user?->phone;
        if (!empty($lawyerPhone)) {
            try {
                SendWhatsAppMessage::dispatch(
                    $lawyerPhone,
                    [
                        '1' => $event->client->name ?? 'Client',
                        '2' => $appointmentTime,
                    ],
                    'appointment_notification_lawyer',
                    (int) $event->appointment->id
                );
            } catch (\Throwable $exception) {
                Log::error('Failed to dispatch lawyer WhatsApp message job', [
                    'appointment_id' => $event->appointment->id,
                    'lawyer_id' => $event->lawyer->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }
}

