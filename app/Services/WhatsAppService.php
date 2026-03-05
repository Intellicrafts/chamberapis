<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Lawyer;
use App\Models\User;
use App\Models\WhatsAppLog;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;

/**
 * WhatsAppService - Sends WhatsApp messages directly via Twilio.
 *
 * Calls Twilio synchronously (no Job / Queue dependency).
 * Works on any server regardless of queue driver or worker status.
 *
 * Events handled:
 *   1. Appointment Booking  → sendAppointmentConfirmationToClient / sendAppointmentNotificationToLawyer
 *   2. 5-Min Reminder       → sendReminderNotification
 *   3. Join Alert           → sendJoinAlertNotification
 *   4. Session Ended        → sendSessionEndedNotification
 *   5. Cancellation         → sendCancellationNotification
 *   6. Reschedule           → sendRescheduleNotification
 */
class WhatsAppService
{
    private TwilioClient $client;
    private string $fromNumber;
    private string $brandName = "🏛️ *MeraVakil Professional Chambers*";

    public function __construct()
    {
        $sid   = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $this->fromNumber = config('services.twilio.whatsapp_from');

        if (empty($sid) || empty($token)) {
            Log::error('WhatsAppService: Twilio credentials missing in config/services.php');
        }

        $this->client = new TwilioClient($sid, $token);
    }

    // ─────────────────────────────────────────────────────────────
    // PUBLIC NOTIFICATION METHODS
    // ─────────────────────────────────────────────────────────────

    /**
     * Event 1a: Booking confirmation → Client
     */
    public function sendAppointmentConfirmationToClient(User $client, Appointment $appointment): void
    {
        $phone = $this->extractPhone($client->phone);
        if (!$phone) {
            Log::warning('WhatsApp: client phone missing, skipping booking confirmation.', ['user_id' => $client->id]);
            return;
        }

        $date       = $appointment->appointment_time?->format('d M Y') ?? '-';
        $time       = $appointment->appointment_time?->format('h:i A') ?? '-';
        $lawyerName = $appointment->lawyer?->full_name ?? 'your Lawyer';

        $body  = "{$this->brandName}\n\n";
        $body .= "Hello *{$client->name}*,\n\n";
        $body .= "✅ *Booking Confirmed!*\n";
        $body .= "Your legal consultation has been successfully scheduled.\n\n";
        $body .= "👨‍⚖️ *Expert:* {$lawyerName}\n";
        $body .= "📅 *Date:* {$date}\n";
        $body .= "⏰ *Time:* {$time}\n\n";
        $body .= "Please be available 5 minutes before the session. Thank you for choosing MeraVakil!";

        $this->send($phone, $body, 'appointment_confirmation_client', $appointment->id);
    }

    /**
     * Event 1b: New booking notification → Lawyer
     */
    public function sendAppointmentNotificationToLawyer(Lawyer $lawyer, Appointment $appointment): void
    {
        $phone = $this->extractPhone($lawyer->phone_number ?? $lawyer->user?->phone);
        if (!$phone) {
            Log::warning('WhatsApp: lawyer phone missing, skipping booking notification.', ['lawyer_id' => $lawyer->id]);
            return;
        }

        $date       = $appointment->appointment_time?->format('d M Y') ?? '-';
        $time       = $appointment->appointment_time?->format('h:i A') ?? '-';
        $clientName = $appointment->user?->name ?? 'A client';

        $body  = "{$this->brandName}\n\n";
        $body .= "Hello *{$lawyer->full_name}*,\n\n";
        $body .= "🆕 *New Appointment Received!*\n";
        $body .= "A client has booked a consultation session with your chamber.\n\n";
        $body .= "👤 *Client:* {$clientName}\n";
        $body .= "📅 *Date:* {$date}\n";
        $body .= "⏰ *Time:* {$time}\n\n";
        $body .= "Manage your schedule via your MeraVakil Dashboard.";

        $this->send($phone, $body, 'appointment_notification_lawyer', $appointment->id);
    }

    /**
     * Event 2: 5-minute session reminder → both parties
     */
    public function sendReminderNotification(Appointment $appointment): void
    {
        $time = $appointment->appointment_time?->format('h:i A') ?? '-';

        $clientPhone = $this->extractPhone($appointment->user?->phone);
        if ($clientPhone) {
            $lawyerName = $appointment->lawyer?->full_name ?? 'your Lawyer';
            $body = "{$this->brandName}\n\n⚠️ *SESSION REMINDER*\n\n"
                  . "Hello *{$appointment->user->name}*,\n"
                  . "Your consultation with *{$lawyerName}* starts in *5 minutes* at {$time}.\n\n"
                  . "Please log in and join your chamber now!";
            $this->send($clientPhone, $body, 'appointment_reminder_client', $appointment->id);
        }

        $lawyerPhone = $this->extractPhone($appointment->lawyer?->phone_number ?? $appointment->lawyer?->user?->phone);
        if ($lawyerPhone) {
            $clientName = $appointment->user?->name ?? 'Client';
            $body = "{$this->brandName}\n\n⚠️ *SESSION REMINDER*\n\n"
                  . "Hello *{$appointment->lawyer->full_name}*,\n"
                  . "Your session with *{$clientName}* starts in *5 minutes* at {$time}.\n\n"
                  . "Please log in to your consultation chamber now!";
            $this->send($lawyerPhone, $body, 'appointment_reminder_lawyer', $appointment->id);
        }
    }

    /**
     * Event 3: Join alert → the party that hasn't joined yet
     *
     * @param string $joinedUserType  'client' or 'lawyer'
     */
    public function sendJoinAlertNotification(Appointment $appointment, string $joinedUserType): void
    {
        if ($joinedUserType === 'client') {
            // Client just joined → alert Lawyer
            $phone = $this->extractPhone($appointment->lawyer?->phone_number ?? $appointment->lawyer?->user?->phone);
            if ($phone) {
                $clientName = $appointment->user?->name ?? 'Your client';
                $body = "{$this->brandName}\n\n🚨 *Client Waiting*\n\n"
                      . "Hello *{$appointment->lawyer->full_name}*,\n"
                      . "*{$clientName}* has joined the consultation chamber and is waiting for you.\n\n"
                      . "Please join the session immediately.";
                $this->send($phone, $body, 'participant_joined_lawyer_alert', $appointment->id);
            }
        } elseif ($joinedUserType === 'lawyer') {
            // Lawyer just joined → alert Client
            $phone = $this->extractPhone($appointment->user?->phone);
            if ($phone) {
                $lawyerName = $appointment->lawyer?->full_name ?? 'Your lawyer';
                $body = "{$this->brandName}\n\n🚨 *Lawyer Waiting*\n\n"
                      . "Hello *{$appointment->user->name}*,\n"
                      . "*{$lawyerName}* has joined the consultation chamber and is waiting for you.\n\n"
                      . "Please join the session immediately.";
                $this->send($phone, $body, 'participant_joined_client_alert', $appointment->id);
            }
        }
    }

    /**
     * Event 4: Session completed → both parties
     */
    public function sendSessionEndedNotification(Appointment $appointment): void
    {
        $clientPhone = $this->extractPhone($appointment->user?->phone);
        if ($clientPhone) {
            $body = "{$this->brandName}\n\n🤝 *Session Completed*\n\n"
                  . "Hello *{$appointment->user->name}*,\n"
                  . "Your legal consultation has officially ended.\n\n"
                  . "Thank you for trusting *MeraVakil*. We hope your queries were resolved!";
            $this->send($clientPhone, $body, 'session_ended_client', $appointment->id);
        }

        $lawyerPhone = $this->extractPhone($appointment->lawyer?->phone_number ?? $appointment->lawyer?->user?->phone);
        if ($lawyerPhone) {
            $body = "{$this->brandName}\n\n🤝 *Session Completed*\n\n"
                  . "Hello *{$appointment->lawyer->full_name}*,\n"
                  . "The consultation session has safely ended.\n\n"
                  . "Great job! Review session reports in your MeraVakil Dashboard.";
            $this->send($lawyerPhone, $body, 'session_ended_lawyer', $appointment->id);
        }
    }

    /**
     * Event 5: Appointment cancelled → both parties
     */
    public function sendCancellationNotification(Appointment $appointment): void
    {
        $dateTime = $appointment->appointment_time?->format('d M Y, h:i A') ?? '-';

        $clientPhone = $this->extractPhone($appointment->user?->phone);
        if ($clientPhone) {
            $body = "{$this->brandName}\n\n❌ *Appointment Cancelled*\n\n"
                  . "Your consultation scheduled for *{$dateTime}* has been cancelled.";
            $this->send($clientPhone, $body, 'appointment_cancelled_client', $appointment->id);
        }

        $lawyerPhone = $this->extractPhone($appointment->lawyer?->phone_number ?? $appointment->lawyer?->user?->phone);
        if ($lawyerPhone) {
            $clientName = $appointment->user?->name ?? 'Client';
            $body = "{$this->brandName}\n\n❌ *Appointment Cancelled*\n\n"
                  . "Your session with *{$clientName}* on *{$dateTime}* has been cancelled.";
            $this->send($lawyerPhone, $body, 'appointment_cancelled_lawyer', $appointment->id);
        }
    }

    /**
     * Event 6: Appointment rescheduled → both parties
     */
    public function sendRescheduleNotification(Appointment $appointment, string $oldTime = ''): void
    {
        $newDateTime = $appointment->appointment_time?->format('d M Y, h:i A') ?? '-';

        $clientPhone = $this->extractPhone($appointment->user?->phone);
        if ($clientPhone) {
            $body = "{$this->brandName}\n\n🔄 *Appointment Rescheduled*\n\n"
                  . "Your consultation has been moved to *{$newDateTime}*.\n"
                  . "Please update your calendar accordingly.";
            $this->send($clientPhone, $body, 'appointment_rescheduled_client', $appointment->id);
        }

        $lawyerPhone = $this->extractPhone($appointment->lawyer?->phone_number ?? $appointment->lawyer?->user?->phone);
        if ($lawyerPhone) {
            $clientName = $appointment->user?->name ?? 'Client';
            $body = "{$this->brandName}\n\n🔄 *Appointment Rescheduled*\n\n"
                  . "Your session with *{$clientName}* has been moved to *{$newDateTime}*.";
            $this->send($lawyerPhone, $body, 'appointment_rescheduled_lawyer', $appointment->id);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // CORE TWILIO SENDER  (no queue, no job, direct API call)
    // ─────────────────────────────────────────────────────────────

    /**
     * Send a WhatsApp message directly via Twilio REST API.
     *
     * DB logging is separated from the Twilio call — if the whatsapp_logs table
     * is missing (e.g. migration not run on staging), the message STILL sends.
     */
    public function send(
        string $phone,
        string $body,
        string $messageType,
        int|string|null $appointmentId = null,
        bool $throwOnError = false
    ): ?string {
        $formatted = $this->formatWhatsAppNumber($phone);

        Log::debug('WhatsApp: attempting send.', [
            'to'   => $formatted,
            'type' => $messageType,
        ]);

        // ── STEP 1: Send via Twilio ──────────────────────────────
        $messageSid = null;
        $sendError  = null;

        try {
            $message    = $this->client->messages->create($formatted, [
                'from' => $this->fromNumber,
                'body' => $body,
            ]);
            $messageSid = $message->sid;

            Log::info('WhatsApp sent successfully.', [
                'to'   => $formatted,
                'type' => $messageType,
                'sid'  => $messageSid,
            ]);
        } catch (\Throwable $e) {
            $sendError = $e;

            Log::error('WhatsApp Twilio send FAILED.', [
                'to'      => $formatted,
                'type'    => $messageType,
                'error'   => $e->getMessage(),
                'code'    => $e->getCode(),
                'class'   => get_class($e),
            ]);

            if ($throwOnError) {
                throw $e;
            }
        }

        // ── STEP 2: Log to DB (independent — never throws) ──────
        try {
            WhatsAppLog::create([
                'phone'          => $formatted,
                'message_type'   => $messageType,
                'appointment_id' => $appointmentId ? (int) $appointmentId : null,
                'status'         => $sendError ? 'failed' : 'sent',
                'twilio_sid'     => $messageSid,
            ]);
        } catch (\Throwable $logError) {
            // Non-fatal: table might not exist on staging yet.
            // Run: php artisan migrate
            Log::warning('WhatsApp DB log failed (is whatsapp_logs table migrated?)', [
                'error' => $logError->getMessage(),
            ]);
        }

        return $messageSid;
    }

    // ─────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────

    /**
     * Extract and validate a phone string, return null if unusable.
     */
    public function extractPhone(mixed $phone): ?string
    {
        $phone = trim((string) ($phone ?? ''));
        if (empty($phone) || !$this->isValidPhone($phone)) {
            return null;
        }
        return $phone;
    }

    /**
     * Format a phone number to Twilio WhatsApp format: whatsapp:+91XXXXXXXXXX
     */
    public function formatWhatsAppNumber(string $phone): string
    {
        $phone = trim($phone);
        $phone = str_replace([' ', '-', '(', ')'], '', $phone);
        // Strip existing whatsapp: prefix to avoid doubling
        $phone = preg_replace('/^whatsapp:/i', '', $phone) ?? $phone;

        if (!str_starts_with($phone, '+')) {
            // Bare 10-digit → assume India +91
            if (strlen($phone) === 10 && ctype_digit($phone)) {
                $phone = '+91' . $phone;
            } else {
                $phone = '+' . ltrim($phone, '0');
            }
        }

        return 'whatsapp:' . $phone;
    }

    /**
     * Basic phone validation: must have at least 10 digits.
     */
    public function isValidPhone(string $phone): bool
    {
        $digits = preg_replace('/\D/', '', $phone);
        return !empty($digits) && strlen($digits) >= 10;
    }

    /**
     * Legacy alias kept for compatibility with SendWhatsAppMessage job.
     */
    public function sendMessage(
        string $phone,
        string $bodyText,
        string $messageType,
        ?int $appointmentId = null,
        bool $throwOnError = false
    ): ?string {
        return $this->send($phone, $bodyText, $messageType, $appointmentId, $throwOnError);
    }
}
