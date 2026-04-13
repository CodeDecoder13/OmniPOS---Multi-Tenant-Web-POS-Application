<?php

namespace App\Listeners;

use App\Events\OrderCompleted;
use App\Services\Tenant\ReceiptService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendAutoReceiptEmail implements ShouldQueue
{
    public function handle(OrderCompleted $event): void
    {
        $order = $event->order;
        $tenant = $event->tenant;

        if (! $order->customer_id) {
            return;
        }

        // Reload customer to ensure email is available (POS checkout may only load id,name)
        $order->load('customer');

        $email = $order->customer->email ?? null;

        if (! $email) {
            return;
        }

        app(ReceiptService::class)->emailReceipt($tenant, $order, $email);
    }
}
