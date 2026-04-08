<?php

namespace App\Events;

use App\Models\Tenant;
use App\Models\Tenant\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Order $order,
        public Tenant $tenant,
    ) {}
}
