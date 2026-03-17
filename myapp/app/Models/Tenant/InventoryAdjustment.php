<?php

namespace App\Models\Tenant;

use App\Enums\AdjustmentType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryAdjustment extends Model
{
    protected $fillable = [
        'tenant_id',
        'inventory_id',
        'type',
        'quantity_before',
        'quantity_after',
        'quantity_change',
        'reason',
        'reference_type',
        'reference_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => AdjustmentType::class,
            'quantity_before' => 'integer',
            'quantity_after' => 'integer',
            'quantity_change' => 'integer',
        ];
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
