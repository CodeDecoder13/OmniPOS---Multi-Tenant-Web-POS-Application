<?php

namespace App\Models;

use App\Enums\DiscountType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromoCode extends Model
{
    protected $fillable = [
        'code',
        'description',
        'discount_type',
        'discount_value',
        'max_uses',
        'used_count',
        'valid_from',
        'valid_until',
        'is_active',
        'applicable_plans',
    ];

    protected function casts(): array
    {
        return [
            'discount_type' => DiscountType::class,
            'discount_value' => 'decimal:2',
            'applicable_plans' => 'array',
            'is_active' => 'boolean',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isValidForPlan(string $planSlug): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        if ($this->valid_from && now()->lt($this->valid_from)) {
            return false;
        }

        if ($this->valid_until && now()->gt($this->valid_until)) {
            return false;
        }

        if ($this->applicable_plans !== null && ! in_array($planSlug, $this->applicable_plans)) {
            return false;
        }

        return true;
    }

    public function incrementUsage(): void
    {
        $this->increment('used_count');
    }
}
