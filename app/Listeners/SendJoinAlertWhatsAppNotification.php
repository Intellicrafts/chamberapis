<?php

namespace App\Listeners;

use App\Events\UserJoinedConsultation;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class SendJoinAlertWhatsAppNotification
{
    public function handle(UserJoinedConsultation $event): void
    {
        try {
            $session = $event->session;

            // Load appointment and both parties
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

            // Determine who joined: 'user' (client) maps to 'client' type
            $joinedUserType = ($event->userType === 'lawyer') ? 'lawyer' : 'client';

            // Only alert if the OTHER party hasn't joined yet
            $shouldAlert = false;
            if ($joinedUserType === 'client' && empty($session->lawyer_joined_at)) {
                $shouldAlert = true;
            } elseif ($joinedUserType === 'lawyer' && empty($session->user_joined_at)) {
                $shouldAlert = true;
            }

            if ($shouldAlert) {
                $service = new WhatsAppService();
                $service->sendJoinAlertNotification($appointment, $joinedUserType);
            }

        } catch (\Throwable $e) {
            Log::error('SendJoinAlertWhatsAppNotification failed.', [
                'session_id' => $event->session?->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
