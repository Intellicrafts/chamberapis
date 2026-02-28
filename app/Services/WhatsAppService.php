<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Lawyer;
use App\Models\User;
use App\Models\WhatsAppLog;
use Illuminate\Support\Facades\Log;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class WhatsAppService
{
    protected Client $twilio;

    public function __construct()
    {
        $this->twilio = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );
    }

    public function sendMessage(
        string $phone,
        array $variables,
        string $messageType,
        ?int $appointmentId = null
    ): ?string {
        $formattedPhone = $this->formatWhatsAppNumber($phone);

        try {
            $message = $this->twilio->messages->create(
                $formattedPhone,
                [
                    'from' => config('services.twilio.whatsapp_from'),
                    'contentSid' => config('services.twilio.template_sid'),
                    'contentVariables' => json_encode($variables, JSON_UNESCAPED_UNICODE),
                ]
            );

            WhatsAppLog::create([
                'phone' => $formattedPhone,
                'message_type' => $messageType,
                'appointment_id' => $appointmentId,
                'status' => 'sent',
                'twilio_sid' => $message->sid,
            ]);

            return $message->sid;
        } catch (TwilioException $exception) {
            $this->logFailure($formattedPhone, $messageType, $appointmentId, $exception);
            throw $exception;
        } catch (\Throwable $exception) {
            $this->logFailure($formattedPhone, $messageType, $appointmentId, $exception);
            throw $exception;
        }
    }

    public function sendAppointmentConfirmationToClient(User $client, Appointment $appointment): ?string
    {
        $variables = [
            '1' => $appointment->appointment_time?->format('Y-m-d') ?? '',
            '2' => $appointment->appointment_time?->format('h:i A') ?? '',
        ];

        return $this->sendMessage(
            (string) $client->phone,
            $variables,
            'appointment_confirmation_client',
            (int) $appointment->id
        );
    }

    public function sendAppointmentNotificationToLawyer(Lawyer $lawyer, Appointment $appointment): ?string
    {
        $variables = [
            '1' => $appointment->user?->name ?? 'Client',
            '2' => $appointment->appointment_time?->format('h:i A') ?? '',
        ];

        return $this->sendMessage(
            (string) ($lawyer->phone_number ?? $lawyer->user?->phone ?? ''),
            $variables,
            'appointment_notification_lawyer',
            (int) $appointment->id
        );
    }

    public function formatWhatsAppNumber(string $phone): string
    {
        $phone = trim($phone);
        $phone = str_replace([' ', '-', '(', ')'], '', $phone);
        $phone = preg_replace('/^whatsapp:/', '', $phone) ?? $phone;

        if (!str_starts_with($phone, '+')) {
            $phone = '+' . ltrim($phone, '0');
        }

        return 'whatsapp:' . $phone;
    }

    protected function logFailure(string $phone, string $messageType, ?int $appointmentId, \Throwable $exception): void
    {
        WhatsAppLog::create([
            'phone' => $phone,
            'message_type' => $messageType,
            'appointment_id' => $appointmentId,
            'status' => 'failed',
            'twilio_sid' => null,
        ]);

        Log::error('WhatsApp send failed', [
            'phone' => $phone,
            'message_type' => $messageType,
            'appointment_id' => $appointmentId,
            'error' => $exception->getMessage(),
        ]);
    }
}

