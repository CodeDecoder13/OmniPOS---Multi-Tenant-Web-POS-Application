<?php

namespace App\Events;

use App\Models\Tenant\Shift;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShiftOpened
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Shift $shift,
    ) {}
}
