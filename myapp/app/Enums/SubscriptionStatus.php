<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Active = 'active';
    case Trial = 'trial';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case PastDue = 'past_due';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Trial => 'Trial',
            self::Cancelled => 'Cancelled',
            self::Expired => 'Expired',
            self::PastDue => 'Past Due',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Trial => 'blue',
            self::Cancelled => 'red',
            self::Expired => 'gray',
            self::PastDue => 'amber',
        };
    }
}
