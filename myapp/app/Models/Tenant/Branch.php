<?php

namespace App\Models\Tenant;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    const DEFAULT_SETTINGS = [
        'pos_enabled'        => true,
        'inventory_tracking' => true,
        'customer_loyalty'   => false,
        'discounts_enabled'  => true,
        'dine_in'            => true,
        'takeout'            => true,
        'delivery'           => false,
        'kitchen_display'    => false,
        'receipt_printing'   => true,
    ];

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'address',
        'phone',
        'email',
        'is_active',
        'settings',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function getSetting(string $key): bool
    {
        $settings = $this->settings ?? [];

        return (bool) ($settings[$key] ?? (self::DEFAULT_SETTINGS[$key] ?? false));
    }

    public function getSettings(): array
    {
        return array_merge(self::DEFAULT_SETTINGS, $this->settings ?? []);
    }

    public function scopeForTenant(Builder $query, Tenant $tenant): Builder
    {
        return $query->where('tenant_id', $tenant->id);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot(['custom_price', 'is_available'])
            ->withTimestamps();
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }
}
