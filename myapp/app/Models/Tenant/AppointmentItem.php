<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['appointment_id', 'product_id', 'name', 'price', 'duration_minutes'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2'];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
