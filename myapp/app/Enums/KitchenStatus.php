<?php

namespace App\Enums;

enum KitchenStatus: string
{
    case New = 'new';
    case Preparing = 'preparing';
    case Ready = 'ready';
    case Served = 'served';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Preparing => 'Preparing',
            self::Ready => 'Ready',
            self::Served => 'Served',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'red',
            self::Preparing => 'yellow',
            self::Ready => 'green',
            self::Served => 'gray',
        };
    }
}
