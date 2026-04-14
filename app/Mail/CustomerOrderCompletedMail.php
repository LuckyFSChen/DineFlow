<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerOrderCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
    }

    public function envelope(): Envelope
    {
        $storeName = $this->order->store?->name ?? config('app.name');

        return new Envelope(
            subject: sprintf('[%s] 訂單已完成 #%s', $storeName, $this->order->order_no),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.orders.customer-completed',
            with: ['order' => $this->order],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
