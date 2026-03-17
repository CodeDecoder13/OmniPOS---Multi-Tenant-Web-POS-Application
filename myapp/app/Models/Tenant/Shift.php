<?php

namespace App\Models\Tenant;

use App\Enums\ShiftStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    protected $fillable = [
        'tenant_id', 'branch_id', 'user_id',
        'starting_cash', 'ending_cash', 'expected_cash', 'cash_difference',
        'total_sales', 'total_orders', 'status', 'notes',
        'opened_at', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ShiftStatus::class,
            'starting_cash' => 'decimal:2',
            'ending_cash' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'cash_difference' => 'decimal:2',
            'total_sales' => 'decimal:2',
            'total_orders' => 'integer',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function scopeForTenant(Builder $query, Tenant $tenant): Builder
    {
        return $query->where('tenant_id', $tenant->id);
    }

    public function isOpen(): bool
    {
        return $this->status === ShiftStatus::Open;
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
