<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class SendWelcomeWhatsAppNotification
{
    public function handle(UserRegistered $event): void
    {
        try {
            $service = new WhatsAppService();

            if ($event->lawyer) {
                // Lawyer registration
                $event->lawyer->loadMissing('user');
                $service->sendWelcomeToLawyer($event->lawyer);
            } else {
                // Client registration
                $service->sendWelcomeToClient($event->user);
            }
        } catch (\Throwable $e) {
            Log::error('SendWelcomeWhatsAppNotification failed.', [
                'user_id' => $event->user?->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
