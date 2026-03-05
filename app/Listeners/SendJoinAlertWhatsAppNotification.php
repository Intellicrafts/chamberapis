<?php

namespace App\Listeners;

use App\Events\UserJoinedConsultation;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

/**
 * Sends a WhatsApp join alert to the party who HASN'T joined yet,
 * but only after a 3-minute grace period to avoid spamming on fast joins.
 */
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
            if (!$appointment) return;

            // Determine who just joined
            $joinedParty = ($event->userType === 'lawyer') ? 'lawyer' : 'client';

            // Only send alert if the OTHER party hasn't joined yet
            $otherHasJoined = ($joinedParty === 'client')
                ? !empty($session->lawyer_joined_at)
                : !empty($session->user_joined_at);

            if ($otherHasJoined) {
                // Both are in — no alert needed
                return;
            }

            // ── 3-minute grace period ─────────────────────────────
            // Wait 3 minutes before alerting the absent party.
            // We do this by scheduling a deferred check via a queued job.
            // Since queue might not be available on staging, we use a
            // simple time-based check: if this listener fires more than
            // 3 minutes after the session started, send immediately.
            $sessionStarted = $session->user_joined_at ?? $session->lawyer_joined_at ?? now();
            $minutesElapsed = now()->diffInMinutes($sessionStarted);

            if ($minutesElapsed >= 3) {
                // Already waited 3+ minutes — send now
                $service = new WhatsAppService();
                $service->sendJoinAlertNotification($appointment, $joinedParty);
            } else {
                // Schedule a delayed job to send alert after 3 minutes
                \App\Jobs\SendDelayedJoinAlert::dispatch($appointment->id, $joinedParty)
                    ->delay(now()->addMinutes(3));
            }

        } catch (\Throwable $e) {
            Log::error('SendJoinAlertWhatsAppNotification failed.', [
                'session_id' => $event->session?->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
