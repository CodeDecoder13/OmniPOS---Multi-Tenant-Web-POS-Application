<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Voided = 'voided';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Completed => 'Completed',
            self::Voided => 'Voided',
            self::Refunded => 'Refunded',
        };
    }
}
