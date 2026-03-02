<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Lawyer;
use App\Models\User;
use App\Models\WhatsAppLog;
use App\Jobs\SendWhatsAppMessage;
use Illuminate\Support\Facades\Log;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class WhatsAppService
{
    protected Client $twilio;
    protected string $brandName = "🏛️ *MeraVakil Professional Chambers*";

    public function __construct()
    {
        $this->twilio = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );
    }

    /**
     * Internal method to actually hit Twilio API.
     * Should only be called by the SendWhatsAppMessage queued job.
     */
    public function sendMessage(
        string $phone,
        string $bodyText,
        string $messageType,
        ?int $appointmentId = null,
        bool $throwOnError = false
    ): ?string {
        if (empty(trim($phone))) {
            return null;
        }

        $formattedPhone = $this->formatWhatsAppNumber($phone);

        try {
            $message = $this->twilio->messages->create(
                $formattedPhone,
                [
                    'from' => config('services.twilio.whatsapp_from'),
                    'body' => $bodyText,
                ]
            );

            WhatsAppLog::create([
                'phone'          => $formattedPhone,
                'message_type'   => $messageType,
                'appointment_id' => $appointmentId,
                'status'         => 'sent',
                'twilio_sid'     => $message->sid,
            ]);

            return $message->sid;

        } catch (\Throwable $exception) {
            $this->logFailure($formattedPhone, $messageType, $appointmentId, $exception);
            if ($throwOnError) throw $exception;
            return null;
        }
    }

    /**
     * Send booking confirmation to the client.
     */
    public function sendAppointmentConfirmationToClient(User $client, Appointment $appointment): void
    {
        $phone = trim((string) ($client->phone ?? ''));
        if (empty($phone)) return;

        $date = $appointment->appointment_time?->format('d M Y') ?? '';
        $time = $appointment->appointment_time?->format('h:i A') ?? '';
        $lawyerName = $appointment->lawyer?->full_name ?? 'your Lawyer';

        $body = "{$this->brandName}\n\n";
        $body .= "Hello *{$client->name}*,\n\n";
        $body .= "✅ *Booking Confirmed!*\n";
        $body .= "Your legal consultation has been successfully scheduled.\n\n";
        $body .= "👨‍⚖️ *Expert:* {$lawyerName}\n";
        $body .= "📅 *Date:* {$date}\n";
        $body .= "⏰ *Time:* {$time}\n\n";
        $body .= "Please be available 5 minutes before the session starts. Thank you for choosing MeraVakil!";

        SendWhatsAppMessage::dispatch($phone, $body, 'appointment_confirmation_client', (int) $appointment->id);
    }

    /**
     * Send new appointment notification to the lawyer.
     */
    public function sendAppointmentNotificationToLawyer(Lawyer $lawyer, Appointment $appointment): void
    {
        $phone = trim((string) ($lawyer->phone_number ?? $lawyer->user?->phone ?? ''));
        if (empty($phone)) return;

        $date = $appointment->appointment_time?->format('d M Y') ?? '';
        $time = $appointment->appointment_time?->format('h:i A') ?? '';
        $clientName = $appointment->user?->name ?? 'A client';

        $body = "{$this->brandName}\n\n";
        $body .= "Hello *{$lawyer->full_name}*,\n\n";
        $body .= "🆕 *New Appointment Received!*\n";
        $body .= "A client has booked a consultation session with your chamber.\n\n";
        $body .= "👤 *Client:* {$clientName}\n";
        $body .= "📅 *Date:* {$date}\n";
        $body .= "⏰ *Time:* {$time}\n\n";
        $body .= "To manage your schedule, please log into your MeraVakil Dashboard.";

        SendWhatsAppMessage::dispatch($phone, $body, 'appointment_notification_lawyer', (int) $appointment->id);
    }

    /**
     * Send 5-minute reminder notification.
     */
    public function sendReminderNotification(Appointment $appointment): void
    {
        $time = $appointment->appointment_time?->format('h:i A') ?? '';

        // Remind client
        $clientPhone = trim((string) ($appointment->user?->phone ?? ''));
        if (!empty($clientPhone)) {
            $lawyerName = $appointment->lawyer?->full_name ?? 'your Lawyer';
            $body = "{$this->brandName}\n\n⚠️ *URGENT REMINDER*\n\nHello *{$appointment->user->name}*,\nYour consultation with *{$lawyerName}* is starting in *5 minutes* ({$time}).\n\nPlease log in to the App and join your chamber now!";
            SendWhatsAppMessage::dispatch($clientPhone, $body, 'appointment_reminder_client', (int) $appointment->id);
        }

        // Remind lawyer
        $lawyerPhone = trim((string) ($appointment->lawyer?->phone_number ?? $appointment->lawyer?->user?->phone ?? ''));
        if (!empty($lawyerPhone)) {
            $clientName = $appointment->user?->name ?? 'Client';
            $body = "{$this->brandName}\n\n⚠️ *URGENT REMINDER*\n\nHello *{$appointment->lawyer->full_name}*,\nYour session with *{$clientName}* starts in *5 minutes* ({$time}).\n\nPlease log in to join your consultation chamber now!";
            SendWhatsAppMessage::dispatch($lawyerPhone, $body, 'appointment_reminder_lawyer', (int) $appointment->id);
        }
    }

    /**
     * Send Join Alert when one participant joins and the other hasn't.
     */
    public function sendJoinAlertNotification(Appointment $appointment, string $joinedUserType): void
    {
        if ($joinedUserType === 'client') {
            // Client joined, alert Lawyer
            $lawyerPhone = trim((string) ($appointment->lawyer?->phone_number ?? $appointment->lawyer?->user?->phone ?? ''));
            if (!empty($lawyerPhone)) {
                $clientName = $appointment->user?->name ?? 'Your client';
                $body = "{$this->brandName}\n\n🚨 *Client Waiting*\n\nHello *{$appointment->lawyer->full_name}*,\n*{$clientName}* has just joined the consultation chamber and is officially waiting for you.\n\nPlease join the session immediately.";
                SendWhatsAppMessage::dispatch($lawyerPhone, $body, 'participant_joined_lawyer_alert', (int) $appointment->id);
            }
        } elseif ($joinedUserType === 'lawyer') {
            // Lawyer joined, alert Client
            $clientPhone = trim((string) ($appointment->user?->phone ?? ''));
            if (!empty($clientPhone)) {
                $lawyerName = $appointment->lawyer?->full_name ?? 'Your lawyer';
                $body = "{$this->brandName}\n\n🚨 *Lawyer Waiting*\n\nHello *{$appointment->user->name}*,\n*{$lawyerName}* has just joined the consultation chamber and is officially waiting for you to resolve your legal queries.\n\nPlease join the session immediately.";
                SendWhatsAppMessage::dispatch($clientPhone, $body, 'participant_joined_client_alert', (int) $appointment->id);
            }
        }
    }

    /**
     * Send Session Ended Notification.
     */
    public function sendSessionEndedNotification(Appointment $appointment): void
    {
        // To Client
        $clientPhone = trim((string) ($appointment->user?->phone ?? ''));
        if (!empty($clientPhone)) {
            $body = "{$this->brandName}\n\n🤝 *Session Completed*\n\nHello *{$appointment->user->name}*,\nYour legal consultation session has officially ended.\n\nThank you for trusting *MeraVakil*. We hope your queries were resolved successfully!";
            SendWhatsAppMessage::dispatch($clientPhone, $body, 'session_ended_client', (int) $appointment->id);
        }

        // To Lawyer
        $lawyerPhone = trim((string) ($appointment->lawyer?->phone_number ?? $appointment->lawyer?->user?->phone ?? ''));
        if (!empty($lawyerPhone)) {
            $body = "{$this->brandName}\n\n🤝 *Session Completed*\n\nHello *{$appointment->lawyer->full_name}*,\nThe consultation session with your client has safely ended.\n\nGreat job! You can review reports in your MeraVakil Dashboard.";
            SendWhatsAppMessage::dispatch($lawyerPhone, $body, 'session_ended_lawyer', (int) $appointment->id);
        }
    }

    public function sendCancellationNotification(Appointment $appointment): void
    {
        $clientPhone = trim((string) ($appointment->user?->phone ?? ''));
        if (!empty($clientPhone)) {
            $body = "{$this->brandName}\n\n❌ *Appointment Cancelled*\n\nYour consultation scheduled for *" . ($appointment->appointment_time?->format('d M, h:i A') ?? '') . "* has been successfully cancelled.";
            SendWhatsAppMessage::dispatch($clientPhone, $body, 'appointment_cancelled_client', (int) $appointment->id);
        }

        $lawyerPhone = trim((string) ($appointment->lawyer?->phone_number ?? $appointment->lawyer?->user?->phone ?? ''));
        if (!empty($lawyerPhone)) {
            $body = "{$this->brandName}\n\n❌ *Appointment Cancelled*\n\nYour session with " . ($appointment->user?->name ?? 'Client') . " on *" . ($appointment->appointment_time?->format('d M, h:i A') ?? '') . "* has been effectively cancelled.";
            SendWhatsAppMessage::dispatch($lawyerPhone, $body, 'appointment_cancelled_lawyer', (int) $appointment->id);
        }
    }

    public function sendRescheduleNotification(Appointment $appointment, string $oldTime): void
    {
        $clientPhone = trim((string) ($appointment->user?->phone ?? ''));
        $newTime = $appointment->appointment_time?->format('d M, h:i A') ?? '';
        if (!empty($clientPhone)) {
            $body = "{$this->brandName}\n\n🔄 *Appointment Rescheduled*\n\nYour consultation has been officially moved to *{$newTime}*. Please update your calendar.";
            SendWhatsAppMessage::dispatch($clientPhone, $body, 'appointment_rescheduled_client', (int) $appointment->id);
        }

        $lawyerPhone = trim((string) ($appointment->lawyer?->phone_number ?? $appointment->lawyer?->user?->phone ?? ''));
        if (!empty($lawyerPhone)) {
            $body = "{$this->brandName}\n\n🔄 *Appointment Rescheduled*\n\nYour session with " . ($appointment->user?->name ?? 'Client') . " has been officially moved to *{$newTime}*.";
            SendWhatsAppMessage::dispatch($lawyerPhone, $body, 'appointment_rescheduled_lawyer', (int) $appointment->id);
        }
    }

    public function formatWhatsAppNumber(string $phone): string
    {
        $phone = trim($phone);
        $phone = str_replace([' ', '-', '(', ')'], '', $phone);
        $phone = preg_replace('/^whatsapp:/', '', $phone) ?? $phone;

        if (!str_starts_with($phone, '+')) {
            if (strlen($phone) === 10 && ctype_digit($phone)) {
                $phone = '+91' . $phone;
            } else {
                $phone = '+' . ltrim($phone, '0');
            }
        }
        return 'whatsapp:' . $phone;
    }

    public function isValidPhone(string $phone): bool
    {
        $phone = trim($phone);
        return !empty($phone) && strlen(preg_replace('/\D/', '', $phone)) >= 10;
    }

    protected function logFailure(string $phone, string $messageType, ?int $appointmentId, \Throwable $exception): void
    {
        WhatsAppLog::create([
            'phone'          => $phone,
            'message_type'   => $messageType,
            'appointment_id' => $appointmentId,
            'status'         => 'failed',
            'twilio_sid'     => null,
        ]);

        Log::error('WhatsApp send failed.', [
            'phone'          => $phone,
            'message_type'   => $messageType,
            'error'          => $exception->getMessage(),
        ]);
    }
}
