<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'staff_member_id', 'assistant_staff_member_id', 'assistant_share', 'product_name',
        'product_price', 'quantity', 'subtotal', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'product_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'quantity' => 'integer',
            'assistant_share' => 'decimal:2',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class, 'staff_member_id');
    }

    public function assistant(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class, 'assistant_staff_member_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variations(): HasMany
    {
        return $this->hasMany(OrderItemVariation::class);
    }

    public function itemAddons(): HasMany
    {
        return $this->hasMany(OrderItemAddon::class);
    }
}
