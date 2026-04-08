<?php

namespace App\Jobs;

use App\Mail\ReceiptMail;
use App\Models\Tenant;
use App\Models\Tenant\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendReceiptEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private Order $order,
        private Tenant $tenant,
        private string $email,
        private string $receiptUrl,
    ) {}

    public function handle(): void
    {
        $this->order->load(['items', 'payments', 'customer']);

        Mail::to($this->email)->send(new ReceiptMail($this->order, $this->tenant, $this->receiptUrl));
    }
}
