<?php

namespace App\Mail;

use App\Models\Tenant;
use App\Models\Tenant\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public Tenant $tenant,
        public string $receiptUrl,
    ) {}

    public function envelope(): Envelope
    {
        $storeName = $this->tenant->data['store_name'] ?? $this->tenant->name;

        return new Envelope(
            subject: "Receipt for Order {$this->order->order_number} - {$storeName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.receipt',
        );
    }
}
