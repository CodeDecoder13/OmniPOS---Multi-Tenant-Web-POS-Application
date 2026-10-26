<?php

namespace App\Models\Tenant;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** "We paid this person for this period": a snapshot, so later sales or refunds never rewrite it. */
class CommissionPayout extends Model
{
    protected $fillable = [
        'tenant_id', 'staff_member_id', 'period_from', 'period_to',
        'sales', 'commission', 'tips', 'amount', 'paid_by', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'period_from' => 'date',
            'period_to' => 'date',
            'sales' => 'decimal:2',
            'commission' => 'decimal:2',
            'tips' => 'decimal:2',
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
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

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
