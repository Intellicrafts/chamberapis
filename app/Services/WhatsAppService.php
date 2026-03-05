<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Lawyer;
use App\Models\User;
use App\Models\WhatsAppLog;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;

/**
 * WhatsAppService — Premium Professional Messaging via Twilio
 *
 * All messages use a luxury, branded tone matching MeraVakil's identity.
 * Direct synchronous Twilio calls — no queue dependency.
 *
 * Events:
 *   1.  Registration Welcome   → sendWelcomeToClient / sendWelcomeToLawyer
 *   2.  Appointment Booked     → sendAppointmentConfirmationToClient / ToLawyer
 *   3.  1-Min Reminder         → sendReminderNotification
 *   4.  Join Alert (3-min wait)→ sendJoinAlertNotification
 *   5.  Session Ended          → sendSessionEndedNotification
 *   6.  Cancellation           → sendCancellationNotification
 *   7.  Reschedule             → sendRescheduleNotification
 */
class WhatsAppService
{
    private TwilioClient $client;
    private string $fromNumber;

    // ── Brand identity ────────────────────────────────────────────
    private const BRAND     = "🏛️ *MeraVakil*";
    private const TAGLINE   = "_India's Trusted Legal Platform_";
    private const DIVIDER   = "━━━━━━━━━━━━━━━━━━━━━━";
    private const FOOTER    = "📲 *meravakil.com*  |  ☎️ Support: +91-XXXXXXXXXX";

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
    // EVENT 1 — REGISTRATION WELCOME
    // ─────────────────────────────────────────────────────────────

    /**
     * Welcome message for a newly registered CLIENT user.
     */
    public function sendWelcomeToClient(User $user): void
    {
        $phone = $this->extractPhone($user->phone);
        if (!$phone) {
            Log::warning('WhatsApp: client phone missing, skipping welcome.', ['user_id' => $user->id]);
            return;
        }

        $name = $user->name ?? 'Valued Client';

        $body = self::BRAND . "\n" . self::TAGLINE . "\n" . self::DIVIDER . "\n\n"
              . "👋 *Welcome to MeraVakil, {$name}!*\n\n"
              . "We're thrilled to have you onboard India's most trusted legal consultation platform.\n\n"
              . "Here's what you can do:\n"
              . "  ✅ Book consultations with verified advocates\n"
              . "  ✅ Join live video sessions from anywhere\n"
              . "  ✅ Access your case history anytime\n\n"
              . "💡 *Get started:* Browse top lawyers and book your first appointment today!\n\n"
              . self::DIVIDER . "\n"
              . self::FOOTER;

        $this->send($phone, $body, 'welcome_client');
    }

    /**
     * Welcome message for a newly registered LAWYER / ADVOCATE.
     */
    public function sendWelcomeToLawyer(Lawyer $lawyer): void
    {
        $phone = $this->extractPhone($lawyer->phone_number ?? $lawyer->user?->phone);
        if (!$phone) {
            Log::warning('WhatsApp: lawyer phone missing, skipping welcome.', ['lawyer_id' => $lawyer->id]);
            return;
        }

        $name = $lawyer->full_name ?? 'Advocate';
        $spec = $lawyer->specialization ?? 'Legal Services';

        $body = self::BRAND . "\n" . self::TAGLINE . "\n" . self::DIVIDER . "\n\n"
              . "⚖️ *Welcome Aboard, {$name}!*\n\n"
              . "Your profile as a *{$spec}* specialist is now live on MeraVakil.\n\n"
              . "Your platform at a glance:\n"
              . "  🗓️ Manage appointments from your dashboard\n"
              . "  💼 Accept consultations from verified clients\n"
              . "  📊 Track your performance and ratings\n"
              . "  💳 Receive payments securely\n\n"
              . "⚡ *Pro tip:* Complete your profile to appear higher in search results.\n\n"
              . self::DIVIDER . "\n"
              . self::FOOTER;

        $this->send($phone, $body, 'welcome_lawyer');
    }

    // ─────────────────────────────────────────────────────────────
    // EVENT 2 — APPOINTMENT BOOKING CONFIRMATION
    // ─────────────────────────────────────────────────────────────

    /**
     * Appointment confirmed → Client
     */
    public function sendAppointmentConfirmationToClient(User $client, Appointment $appointment): void
    {
        $phone = $this->extractPhone($client->phone);
        if (!$phone) {
            Log::warning('WhatsApp: client phone missing, skipping booking confirmation.', ['user_id' => $client->id]);
            return;
        }

        $date        = $appointment->appointment_time?->format('d M Y') ?? '-';
        $time        = $appointment->appointment_time?->format('h:i A') ?? '-';
        $duration    = $appointment->duration_minutes ?? 30;
        $lawyerName  = $appointment->lawyer?->full_name ?? 'Your Advocate';
        $bookingRef  = 'MVK-' . str_pad($appointment->id, 6, '0', STR_PAD_LEFT);

        $body = self::BRAND . "\n" . self::TAGLINE . "\n" . self::DIVIDER . "\n\n"
              . "✅ *Appointment Confirmed!*\n\n"
              . "Dear *{$client->name}*, your consultation has been successfully booked.\n\n"
              . "📋 *Booking Details*\n"
              . "  🔖 Reference : #{$bookingRef}\n"
              . "  👨‍⚖️ Advocate  : {$lawyerName}\n"
              . "  📅 Date      : {$date}\n"
              . "  🕐 Time      : {$time} IST\n"
              . "  ⏱️ Duration  : {$duration} minutes\n\n"
              . "📌 *Important:* Please join the session on time. You will receive a reminder 1 minute before the session begins.\n\n"
              . self::DIVIDER . "\n"
              . self::FOOTER;

        $this->send($phone, $body, 'appointment_confirmation_client', $appointment->id);
    }

    /**
     * New appointment notification → Lawyer
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
        $duration   = $appointment->duration_minutes ?? 30;
        $clientName = $appointment->user?->name ?? 'Your Client';
        $bookingRef = 'MVK-' . str_pad($appointment->id, 6, '0', STR_PAD_LEFT);

        $body = self::BRAND . "\n" . self::TAGLINE . "\n" . self::DIVIDER . "\n\n"
              . "🗓️ *New Appointment Scheduled*\n\n"
              . "Dear *{$lawyer->full_name}*, a new consultation has been booked with you.\n\n"
              . "📋 *Appointment Details*\n"
              . "  🔖 Reference : #{$bookingRef}\n"
              . "  👤 Client    : {$clientName}\n"
              . "  📅 Date      : {$date}\n"
              . "  🕐 Time      : {$time} IST\n"
              . "  ⏱️ Duration  : {$duration} minutes\n\n"
              . "✅ Please ensure you are available and logged into your MeraVakil dashboard on time.\n\n"
              . self::DIVIDER . "\n"
              . self::FOOTER;

        $this->send($phone, $body, 'appointment_notification_lawyer', $appointment->id);
    }

    // ─────────────────────────────────────────────────────────────
    // EVENT 3 — 1-MINUTE REMINDER
    // ─────────────────────────────────────────────────────────────

    /**
     * Sends a 1-minute reminder to BOTH client and lawyer.
     */
    public function sendReminderNotification(Appointment $appointment): void
    {
        $time  = $appointment->appointment_time?->format('h:i A') ?? '-';
        $bookingRef = 'MVK-' . str_pad($appointment->id, 6, '0', STR_PAD_LEFT);

        // → Client
        $clientPhone = $this->extractPhone($appointment->user?->phone);
        if ($clientPhone) {
            $lawyerName  = $appointment->lawyer?->full_name ?? 'your Advocate';
            $body = self::BRAND . "\n" . self::DIVIDER . "\n\n"
                  . "⏰ *Session Starting in 1 Minute!*\n\n"
                  . "Dear *{$appointment->user->name}*, your consultation with *{$lawyerName}* begins at *{$time} IST*.\n\n"
                  . "  🔖 Ref: #{$bookingRef}\n\n"
                  . "👆 *Action Required:* Open MeraVakil and click *\"Join Session\"* now to avoid delay.\n\n"
                  . self::DIVIDER . "\n"
                  . self::FOOTER;
            $this->send($clientPhone, $body, 'reminder_client', $appointment->id);
        }

        // → Lawyer
        $lawyerPhone = $this->extractPhone($appointment->lawyer?->phone_number ?? $appointment->lawyer?->user?->phone);
        if ($lawyerPhone) {
            $clientName = $appointment->user?->name ?? 'your client';
            $body = self::BRAND . "\n" . self::DIVIDER . "\n\n"
                  . "⏰ *Session Starting in 1 Minute!*\n\n"
                  . "Dear *{$appointment->lawyer->full_name}*, your consultation with *{$clientName}* begins at *{$time} IST*.\n\n"
                  . "  🔖 Ref: #{$bookingRef}\n\n"
                  . "👆 *Action Required:* Open MeraVakil Dashboard and click *\"Join Session\"* now.\n\n"
                  . self::DIVIDER . "\n"
                  . self::FOOTER;
            $this->send($lawyerPhone, $body, 'reminder_lawyer', $appointment->id);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // EVENT 4 — JOIN ALERT (one party waiting 2–3 mins)
    // ─────────────────────────────────────────────────────────────

    /**
     * Alert to the party who HASN'T joined yet after ~3 minutes of waiting.
     *
     * @param string $joinedParty  'client' = client joined, alert the lawyer
     *                             'lawyer' = lawyer joined, alert the client
     */
    public function sendJoinAlertNotification(Appointment $appointment, string $joinedParty): void
    {
        $bookingRef = 'MVK-' . str_pad($appointment->id, 6, '0', STR_PAD_LEFT);

        if ($joinedParty === 'client') {
            // CLIENT joined → Alert the LAWYER
            $lawyerPhone = $this->extractPhone($appointment->lawyer?->phone_number ?? $appointment->lawyer?->user?->phone);
            if (!$lawyerPhone) return;

            $clientName = $appointment->user?->name ?? 'Your client';

            $body = self::BRAND . "\n" . self::DIVIDER . "\n\n"
                  . "🔔 *Client is Waiting for You!*\n\n"
                  . "Dear *{$appointment->lawyer->full_name}*,\n\n"
                  . "*{$clientName}* has joined the session and is waiting for you.\n\n"
                  . "  🔖 Ref: #{$bookingRef}\n"
                  . "  ⏳ Waiting since: " . now()->format('h:i A') . "\n\n"
                  . "⚡ *Please join immediately* through your MeraVakil Dashboard to avoid keeping your client waiting.\n\n"
                  . self::DIVIDER . "\n"
                  . self::FOOTER;

            $this->send($lawyerPhone, $body, 'join_alert_lawyer', $appointment->id);

        } else {
            // LAWYER joined → Alert the CLIENT
            $clientPhone = $this->extractPhone($appointment->user?->phone);
            if (!$clientPhone) return;

            $lawyerName = $appointment->lawyer?->full_name ?? 'Your Advocate';

            $body = self::BRAND . "\n" . self::DIVIDER . "\n\n"
                  . "🔔 *Your Advocate is Waiting for You!*\n\n"
                  . "Dear *{$appointment->user->name}*,\n\n"
                  . "*{$lawyerName}* has joined the session and is waiting for you.\n\n"
                  . "  🔖 Ref: #{$bookingRef}\n"
                  . "  ⏳ Waiting since: " . now()->format('h:i A') . "\n\n"
                  . "⚡ *Please join now* through MeraVakil to begin your consultation.\n\n"
                  . self::DIVIDER . "\n"
                  . self::FOOTER;

            $this->send($clientPhone, $body, 'join_alert_client', $appointment->id);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // EVENT 5 — SESSION ENDED
    // ─────────────────────────────────────────────────────────────

    /**
     * Session ended notification → BOTH parties.
     */
    public function sendSessionEndedNotification(Appointment $appointment): void
    {
        $date       = $appointment->appointment_time?->format('d M Y') ?? '-';
        $bookingRef = 'MVK-' . str_pad($appointment->id, 6, '0', STR_PAD_LEFT);

        // → Client
        $clientPhone = $this->extractPhone($appointment->user?->phone);
        if ($clientPhone) {
            $lawyerName = $appointment->lawyer?->full_name ?? 'your Advocate';

            $body = self::BRAND . "\n" . self::DIVIDER . "\n\n"
                  . "✅ *Consultation Completed*\n\n"
                  . "Dear *{$appointment->user->name}*,\n\n"
                  . "Your consultation with *{$lawyerName}* on *{$date}* has been successfully concluded.\n\n"
                  . "  🔖 Ref: #{$bookingRef}\n\n"
                  . "🌟 We hope your legal queries were resolved. Your feedback matters!\n\n"
                  . "💬 *Rate your experience* on MeraVakil to help other clients make informed decisions.\n\n"
                  . "Thank you for trusting MeraVakil. We're here whenever you need legal guidance. ⚖️\n\n"
                  . self::DIVIDER . "\n"
                  . self::FOOTER;

            $this->send($clientPhone, $body, 'session_ended_client', $appointment->id);
        }

        // → Lawyer
        $lawyerPhone = $this->extractPhone($appointment->lawyer?->phone_number ?? $appointment->lawyer?->user?->phone);
        if ($lawyerPhone) {
            $clientName = $appointment->user?->name ?? 'your client';

            $body = self::BRAND . "\n" . self::DIVIDER . "\n\n"
                  . "✅ *Consultation Session Closed*\n\n"
                  . "Dear *{$appointment->lawyer->full_name}*,\n\n"
                  . "Your session with *{$clientName}* on *{$date}* has been successfully completed.\n\n"
                  . "  🔖 Ref: #{$bookingRef}\n\n"
                  . "📊 *Session Report & Analytics* are now available in your MeraVakil Dashboard.\n\n"
                  . "Great work! Keep delivering excellent legal support. ⚖️\n\n"
                  . self::DIVIDER . "\n"
                  . self::FOOTER;

            $this->send($lawyerPhone, $body, 'session_ended_lawyer', $appointment->id);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // EVENT 6 — APPOINTMENT CANCELLED
    // ─────────────────────────────────────────────────────────────

    public function sendCancellationNotification(Appointment $appointment): void
    {
        $dateTime   = $appointment->appointment_time?->format('d M Y, h:i A') ?? '-';
        $bookingRef = 'MVK-' . str_pad($appointment->id, 6, '0', STR_PAD_LEFT);

        // → Client
        $clientPhone = $this->extractPhone($appointment->user?->phone);
        if ($clientPhone) {
            $lawyerName = $appointment->lawyer?->full_name ?? 'your Advocate';

            $body = self::BRAND . "\n" . self::DIVIDER . "\n\n"
                  . "❌ *Appointment Cancelled*\n\n"
                  . "Dear *{$appointment->user->name}*,\n\n"
                  . "Your appointment with *{$lawyerName}* scheduled for *{$dateTime} IST* has been cancelled.\n\n"
                  . "  🔖 Ref: #{$bookingRef}\n\n"
                  . "🔄 You can rebook your consultation anytime on MeraVakil.\n"
                  . "📞 For assistance, contact our support team.\n\n"
                  . self::DIVIDER . "\n"
                  . self::FOOTER;

            $this->send($clientPhone, $body, 'appointment_cancelled_client', $appointment->id);
        }

        // → Lawyer
        $lawyerPhone = $this->extractPhone($appointment->lawyer?->phone_number ?? $appointment->lawyer?->user?->phone);
        if ($lawyerPhone) {
            $clientName = $appointment->user?->name ?? 'the client';

            $body = self::BRAND . "\n" . self::DIVIDER . "\n\n"
                  . "❌ *Appointment Cancelled*\n\n"
                  . "Dear *{$appointment->lawyer->full_name}*,\n\n"
                  . "Your appointment with *{$clientName}* scheduled for *{$dateTime} IST* has been cancelled.\n\n"
                  . "  🔖 Ref: #{$bookingRef}\n\n"
                  . "📅 Your calendar slot is now available for other appointments.\n\n"
                  . self::DIVIDER . "\n"
                  . self::FOOTER;

            $this->send($lawyerPhone, $body, 'appointment_cancelled_lawyer', $appointment->id);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // EVENT 7 — APPOINTMENT RESCHEDULED
    // ─────────────────────────────────────────────────────────────

    public function sendRescheduleNotification(Appointment $appointment, string $oldTime = ''): void
    {
        $newDateTime = $appointment->appointment_time?->format('d M Y, h:i A') ?? '-';
        $bookingRef  = 'MVK-' . str_pad($appointment->id, 6, '0', STR_PAD_LEFT);
        $oldLine     = $oldTime ? "  📅 Old Time : {$oldTime} IST\n" : '';

        // → Client
        $clientPhone = $this->extractPhone($appointment->user?->phone);
        if ($clientPhone) {
            $lawyerName = $appointment->lawyer?->full_name ?? 'your Advocate';

            $body = self::BRAND . "\n" . self::DIVIDER . "\n\n"
                  . "🔄 *Appointment Rescheduled*\n\n"
                  . "Dear *{$appointment->user->name}*,\n\n"
                  . "Your consultation with *{$lawyerName}* has been rescheduled.\n\n"
                  . "  🔖 Ref     : #{$bookingRef}\n"
                  . $oldLine
                  . "  📅 New Time: *{$newDateTime} IST*\n\n"
                  . "📆 Please update your calendar accordingly. A reminder will be sent before the session.\n\n"
                  . self::DIVIDER . "\n"
                  . self::FOOTER;

            $this->send($clientPhone, $body, 'appointment_rescheduled_client', $appointment->id);
        }

        // → Lawyer
        $lawyerPhone = $this->extractPhone($appointment->lawyer?->phone_number ?? $appointment->lawyer?->user?->phone);
        if ($lawyerPhone) {
            $clientName = $appointment->user?->name ?? 'your client';

            $body = self::BRAND . "\n" . self::DIVIDER . "\n\n"
                  . "🔄 *Appointment Rescheduled*\n\n"
                  . "Dear *{$appointment->lawyer->full_name}*,\n\n"
                  . "Your session with *{$clientName}* has been rescheduled.\n\n"
                  . "  🔖 Ref     : #{$bookingRef}\n"
                  . $oldLine
                  . "  📅 New Time: *{$newDateTime} IST*\n\n"
                  . "📆 Please update your availability accordingly.\n\n"
                  . self::DIVIDER . "\n"
                  . self::FOOTER;

            $this->send($lawyerPhone, $body, 'appointment_rescheduled_lawyer', $appointment->id);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // CORE TWILIO SENDER (synchronous, no queue)
    // ─────────────────────────────────────────────────────────────

    /**
     * Send a WhatsApp message directly via Twilio REST API.
     *
     * DB logging is separated from the Twilio call — if whatsapp_logs table is
     * missing (e.g. migration not run on staging), the message STILL sends.
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
                'to'    => $formatted,
                'type'  => $messageType,
                'error' => $e->getMessage(),
                'code'  => $e->getCode(),
                'class' => get_class($e),
            ]);

            if ($throwOnError) {
                throw $e;
            }
        }

        // ── STEP 2: Log to DB (independent — never throws) ───────
        try {
            WhatsAppLog::create([
                'phone'          => $formatted,
                'message_type'   => $messageType,
                'appointment_id' => $appointmentId ? (int) $appointmentId : null,
                'status'         => $sendError ? 'failed' : 'sent',
                'twilio_sid'     => $messageSid,
            ]);
        } catch (\Throwable $logError) {
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
     * Format any phone number to whatsapp:+91XXXXXXXXXX format.
     */
    private function formatWhatsAppNumber(string $phone): string
    {
        // Strip all non-digit chars
        $digits = preg_replace('/\D/', '', $phone);

        // Already has country code (12 digits starting with 91)
        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            return 'whatsapp:+' . $digits;
        }

        // 10-digit Indian mobile → prepend +91
        if (strlen($digits) === 10) {
            return 'whatsapp:+91' . $digits;
        }

        // Already formatted (whatsapp:+...) or other country code
        if (str_starts_with($phone, 'whatsapp:')) {
            return $phone;
        }

        return 'whatsapp:+' . ltrim($digits, '0');
    }

    /**
     * Extract and validate phone from raw field value.
     * Returns null if empty or invalid.
     */
    private function extractPhone(mixed $phone): ?string
    {
        if (empty($phone)) return null;
        $digits = preg_replace('/\D/', '', (string) $phone);
        if (strlen($digits) < 10) return null;
        return $phone;
    }

    // ──────────────────────────────────────────────────────────────
    // LEGACY ALIAS (kept for backward-compat with SendWhatsAppMessage job)
    // ──────────────────────────────────────────────────────────────

    public function sendMessage(string $to, string $body): ?string
    {
        return $this->send($to, $body, 'legacy_direct');
    }
}
