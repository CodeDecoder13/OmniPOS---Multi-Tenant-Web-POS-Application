<?php

namespace App\Models\Tenant;

use App\Enums\AppointmentStatus;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Appointment extends Model
{
    protected $fillable = [
        'tenant_id', 'branch_id', 'staff_member_id', 'customer_name', 'customer_phone',
        'starts_at', 'ends_at', 'status', 'notes', 'order_id', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => AppointmentStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function scopeForTenant(Builder $query, Tenant $tenant): Builder
    {
        return $query->where('tenant_id', $tenant->id);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class, 'staff_member_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AppointmentItem::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
