<?php

namespace App\Jobs;

use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * SendWhatsAppMessage - used only by appointments:send-reminders command.
 * All event-based notifications are now sent directly via WhatsAppService::send().
 */
class SendWhatsAppMessage implements ShouldQueue
{
    use Queueable;

    public int $tries   = 3;
    public array $backoff = [15, 60, 180];
    public int $timeout = 60;

    public function __construct(
        public string $phone,
        public string $bodyText,
        public string $messageType,
        public ?int   $appointmentId = null
    ) {
        $this->onQueue('whatsapp');
    }

    public function handle(WhatsAppService $service): void
    {
        if (!$service->isValidPhone($this->phone)) {
            Log::warning('SendWhatsAppMessage: invalid phone, discarding job.', [
                'phone' => $this->phone,
                'type'  => $this->messageType,
            ]);
            return;
        }

        // Use send() directly — throwOnError=true so queue retries on transient Twilio errors
        $service->send(
            $this->phone,
            $this->bodyText,
            $this->messageType,
            $this->appointmentId,
            throwOnError: true
        );
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendWhatsAppMessage job permanently failed.', [
            'phone' => $this->phone,
            'type'  => $this->messageType,
            'error' => $e->getMessage(),
        ]);
    }
}
