<?php

namespace App\Models;

use App\Enums\BusinessType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'slug',
        'business_type',
        'data',
        'owner_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'business_type' => BusinessType::class,
            'data' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_users')
            ->withPivot('role_id', 'branch_id', 'is_active', 'last_login_at')
            ->withTimestamps();
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(TenantSubscription::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Tenant\Branch::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Tenant\Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Tenant\Product::class);
    }
}
