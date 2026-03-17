<?php

namespace App\Enums;

enum OrderType: string
{
    case DineIn = 'dine_in';
    case TakeOut = 'take_out';

    public function label(): string
    {
        return match ($this) {
            self::DineIn => 'Dine In',
            self::TakeOut => 'Take Out',
        };
    }
}
