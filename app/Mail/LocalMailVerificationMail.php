<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LocalMailVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $sentAt)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[' . config('app.name') . '] Local mail verification',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.local-verification',
            with: ['sentAt' => $this->sentAt],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
