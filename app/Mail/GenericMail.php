<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GenericMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        protected string $subjectLine,
        protected string $viewName,
        protected array $viewData = [],
        protected ?array $replyToAddress = null
    ) {
    }

    public function envelope(): Envelope
    {
        $envelope = new Envelope(subject: $this->subjectLine);

        if (is_array($this->replyToAddress) && !empty($this->replyToAddress['address'])) {
            $envelope->replyTo($this->replyToAddress['address'], $this->replyToAddress['name'] ?? null);
        }

        return $envelope;
    }

    public function content(): Content
    {
        return new Content(
            view: $this->viewName,
            with: $this->viewData
        );
    }
}
