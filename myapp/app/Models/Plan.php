<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'price',
        'features',
        'max_branches',
        'max_users',
        'max_products',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isFree(): bool
    {
        return (float) $this->price === 0.0;
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class);
    }
}
