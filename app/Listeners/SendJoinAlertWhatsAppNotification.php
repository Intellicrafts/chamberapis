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
            $session->loadMissing('appointment', 'appointment.user', 'appointment.lawyer', 'appointment.lawyer.user');

            if (!$session->appointment) {
                return;
            }

            // Figure out who joined
            // In the event, userType can be 'user' (client) or 'lawyer'
            $joinedUserType = $event->userType === 'lawyer' ? 'lawyer' : 'client';

            // Check if the other person hasn't joined yet
            $shouldAlert = false;
            
            if ($joinedUserType === 'client' && !$session->lawyer_joined_at) {
                $shouldAlert = true;
            } elseif ($joinedUserType === 'lawyer' && !$session->user_joined_at) {
                $shouldAlert = true;
            }

            if ($shouldAlert) {
                $whatsAppService = new WhatsAppService();
                $whatsAppService->sendJoinAlertNotification($session->appointment, $joinedUserType);
            }

        } catch (\Throwable $exception) {
            Log::error('SendJoinAlertWhatsAppNotification failed.', [
                'session_id' => $event->session->id ?? null,
                'error'      => $exception->getMessage(),
            ]);
        }
    }
}
