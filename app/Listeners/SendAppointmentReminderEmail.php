<?php

namespace App\Listeners;

use App\Events\AppointmentStartingSoon;
use App\Services\Mail\AppMailService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendAppointmentReminderEmail
{
    public function handle(AppointmentStartingSoon $event): void
    {
        try {
            $appointment = $event->appointment;
            $appointment->loadMissing(['user', 'lawyer', 'lawyer.user']);

            $mailService = app(AppMailService::class);

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
                    'meeting_link'              => $appointment->meeting_link,
                    'user_name'                 => $appointment->user?->name,
                    'lawyer_name'               => $appointment->lawyer?->full_name,
                ],
            ];

            // --- Reminder to CLIENT ---
            $clientEmail = $appointment->user?->email;
            if (!empty($clientEmail)) {
                $mailService->send(
                    to: $clientEmail,
                    subject: '⏰ Your Session Starts in 5 Minutes — MeraBakil',
                    view: 'emails.templates.appointment-reminder',
                    data: array_merge($sharedData, ['isLawyerRecipient' => false])
                );
            }

            // --- Reminder to LAWYER ---
            $lawyerEmail = $appointment->lawyer?->email ?? $appointment->lawyer?->user?->email;
            if (!empty($lawyerEmail)) {
                $mailService->send(
                    to: $lawyerEmail,
                    subject: '⏰ Consultation Begins in 5 Minutes — MeraBakil',
                    view: 'emails.templates.appointment-reminder',
                    data: array_merge($sharedData, ['isLawyerRecipient' => true])
                );
            }

        } catch (\Throwable $e) {
            Log::error('SendAppointmentReminderEmail failed.', [
                'appointment_id' => $event->appointment?->id,
                'error'          => $e->getMessage(),
            ]);
        }
    }
}
