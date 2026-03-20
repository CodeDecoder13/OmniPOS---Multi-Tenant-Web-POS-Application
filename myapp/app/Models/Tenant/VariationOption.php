<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VariationOption extends Model
{
    protected $fillable = [
        'variation_group_id', 'name', 'price_modifier', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_modifier' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function variationGroup(): BelongsTo
    {
        return $this->belongsTo(VariationGroup::class);
    }
}
