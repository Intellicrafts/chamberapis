<?php

namespace App\Jobs;

use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendWhatsAppMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(
        public string $phone,
        public array $variables,
        public string $messageType,
        public ?int $appointmentId = null
    ) {
        $this->onQueue('whatsapp');
    }

    public function handle(WhatsAppService $whatsAppService): void
    {
        try {
            $sid = $whatsAppService->sendMessage(
                $this->phone,
                $this->variables,
                $this->messageType,
                $this->appointmentId
            );

            Log::info('WhatsApp message queued and sent', [
                'phone' => $this->phone,
                'message_type' => $this->messageType,
                'appointment_id' => $this->appointmentId,
                'twilio_sid' => $sid,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('WhatsApp message job failed and will be retried', [
                'phone' => $this->phone,
                'message_type' => $this->messageType,
                'appointment_id' => $this->appointmentId,
                'attempt' => $this->attempts(),
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('WhatsApp message job permanently failed', [
            'phone' => $this->phone,
            'message_type' => $this->messageType,
            'appointment_id' => $this->appointmentId,
            'error' => $exception->getMessage(),
        ]);
    }
}

