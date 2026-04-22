<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MerchantInquiryNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $inquiry)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('merchant_inquiry.notify_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.admin.merchant-inquiry-notification',
            with: [
                'inquiry' => $this->inquiry,
                'submittedAt' => now(),
            ],
        );
    }
}
