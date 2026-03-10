<?php

namespace App\Listeners;

use App\Events\AppointmentBooked;
use App\Services\Mail\AppMailService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendAppointmentConfirmationEmail
{
    public function handle(AppointmentBooked $event): void
    {
        try {
            $appointment = $event->appointment;
            $appointment->loadMissing(['user', 'lawyer', 'lawyer.user']);

            $mailService = app(AppMailService::class);

            // --- Shared appointment data ---
            $timezone = config('app.timezone', 'UTC');
            $dt = null;
            if (!empty($appointment->appointment_time)) {
                try {
                    $dt = Carbon::parse($appointment->appointment_time)->timezone($timezone);
                } catch (\Throwable) {}
            }

            $sharedData = [
                'appointment' => [
                    'id'                        => $appointment->id,
                    'appointment_date'          => $dt?->format('l, d M Y') ?? '—',
                    'appointment_time_formatted' => $dt?->format('h:i A') ?? '—',
                    'duration_minutes'          => $appointment->duration_minutes,
                    'status'                    => $appointment->status,
                    'meeting_link'              => $appointment->meeting_link,
                    'user_name'                 => $appointment->user?->name,
                    'lawyer_name'               => $appointment->lawyer?->full_name,
                ],
            ];

            // --- Email to CLIENT ---
            $clientEmail = $appointment->user?->email;
            if (!empty($clientEmail)) {
                $mailService->send(
                    to: $clientEmail,
                    subject: '✅ Your Appointment Is Confirmed — ' . ($appointment->lawyer?->full_name ?? 'Your Lawyer'),
                    view: 'emails.templates.appointment-confirmation',
                    data: array_merge($sharedData, ['isLawyerRecipient' => false])
                );
            }

            // --- Email to LAWYER ---
            $lawyerEmail = $appointment->lawyer?->email ?? $appointment->lawyer?->user?->email;
            if (!empty($lawyerEmail)) {
                $mailService->send(
                    to: $lawyerEmail,
                    subject: '📅 New Consultation Assigned — ' . ($appointment->user?->name ?? 'A Client'),
                    view: 'emails.templates.appointment-confirmation',
                    data: array_merge($sharedData, ['isLawyerRecipient' => true])
                );
            }

        } catch (\Throwable $e) {
            Log::error('SendAppointmentConfirmationEmail failed.', [
                'appointment_id' => $event->appointment?->id,
                'error'          => $e->getMessage(),
            ]);
        }
    }
}
