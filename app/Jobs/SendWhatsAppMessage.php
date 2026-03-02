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
    public array $backoff = [15, 60, 180];
    public int $timeout = 60;

    /**
     * @param string $phone The recipient phone number
     * @param string $bodyText The beautiful custom formatted message
     * @param string $messageType Internal tracking type
     * @param int|null $appointmentId
     */
    public function __construct(
        public string $phone,
        public string $bodyText,
        public string $messageType,
        public ?int   $appointmentId = null
    ) {
        $this->onQueue('whatsapp');
    }

    public function handle(WhatsAppService $whatsAppService): void
    {
        if (!$whatsAppService->isValidPhone($this->phone)) {
            return;
        }

        $whatsAppService->sendMessage(
            $this->phone,
            $this->bodyText,
            $this->messageType,
            $this->appointmentId,
            throwOnError: true
        );
    }
}
