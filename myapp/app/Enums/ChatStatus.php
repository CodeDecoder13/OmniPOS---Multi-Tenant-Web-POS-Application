<?php

namespace App\Enums;

enum ChatStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Closed => 'Closed',
            self::Resolved => 'Resolved',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'green',
            self::Closed => 'gray',
            self::Resolved => 'blue',
        };
    }
}
