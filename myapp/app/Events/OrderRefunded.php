<?php

namespace App\Events;

use App\Models\Tenant;
use App\Models\Tenant\Order;
use App\Models\Tenant\Refund;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderRefunded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Refund $refund,
        public Order $order,
        public Tenant $tenant,
    ) {}
}
