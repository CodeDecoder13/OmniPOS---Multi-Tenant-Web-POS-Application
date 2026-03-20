<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemAddon extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_item_id', 'addon_name', 'addon_price',
    ];

    protected function casts(): array
    {
        return [
            'addon_price' => 'decimal:2',
        ];
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
