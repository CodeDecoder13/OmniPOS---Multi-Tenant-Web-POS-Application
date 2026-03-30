<?php

namespace App\Enums;

enum ReleaseNoteItemType: string
{
    case Feature = 'feature';
    case Fix = 'fix';
    case Improvement = 'improvement';

    public function label(): string
    {
        return match ($this) {
            self::Feature => 'New Feature',
            self::Fix => 'Bug Fix',
            self::Improvement => 'Improvement',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Feature => 'teal',
            self::Fix => 'red',
            self::Improvement => 'blue',
        };
    }
}
