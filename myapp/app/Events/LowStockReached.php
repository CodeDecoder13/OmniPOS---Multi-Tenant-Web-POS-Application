<?php

namespace App\Events;

use App\Models\Tenant;
use App\Models\Tenant\Inventory;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LowStockReached
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Inventory $inventory,
        public Tenant $tenant,
    ) {}
}
