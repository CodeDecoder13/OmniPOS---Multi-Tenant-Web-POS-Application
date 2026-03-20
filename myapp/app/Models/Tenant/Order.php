<?php

namespace App\Models\Tenant;

use App\Enums\DiscountType;
use App\Enums\KitchenStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'tenant_id', 'branch_id', 'customer_id', 'order_number',
        'subtotal', 'discount_amount', 'discount_type', 'tax_amount',
        'total', 'notes', 'order_type', 'status', 'created_by', 'shift_id',
        'table_id', 'promotion_id', 'promotion_discount',
        'kitchen_status', 'kitchen_sent_at', 'kitchen_completed_at', 'kitchen_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'order_type' => OrderType::class,
            'discount_type' => DiscountType::class,
            'kitchen_status' => KitchenStatus::class,
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'promotion_discount' => 'decimal:2',
            'kitchen_sent_at' => 'datetime',
            'kitchen_completed_at' => 'datetime',
        ];
    }

    public function scopeForTenant(Builder $query, Tenant $tenant): Builder
    {
        return $query->where('tenant_id', $tenant->id);
    }

    public function scopeForKitchen(Builder $query, int $branchId): Builder
    {
        return $query->where('branch_id', $branchId)
            ->whereNotNull('kitchen_status')
            ->whereIn('kitchen_status', [
                KitchenStatus::New->value,
                KitchenStatus::Preparing->value,
                KitchenStatus::Ready->value,
            ]);
    }

    public function isPaid(): bool
    {
        return $this->status === OrderStatus::Completed;
    }

    public function canBeVoided(): bool
    {
        return $this->status === OrderStatus::Completed;
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }
}
