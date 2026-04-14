<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerOrderCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
    }

    public function envelope(): Envelope
    {
        $storeName = $this->order->store?->name ?? config('app.name');
        $locale = Order::resolveOrderLocale($this->order->order_locale ?? app()->getLocale());

        return new Envelope(
            subject: __('mail_orders.created.subject', [
                'store' => $storeName,
                'order_no' => $this->order->order_no,
            ], $locale),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.orders.customer-created',
            with: ['order' => $this->order],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
