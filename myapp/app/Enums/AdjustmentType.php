<?php

namespace App\Enums;

enum AdjustmentType: string
{
    case Purchase = 'purchase';
    case Sale = 'sale';
    case Return = 'return';
    case Damage = 'damage';
    case Correction = 'correction';
    case Initial = 'initial';
    case TransferOut = 'transfer_out';
    case TransferIn = 'transfer_in';
    case Refund = 'refund';

    public function label(): string
    {
        return match ($this) {
            self::Purchase => 'Purchase',
            self::Sale => 'Sale',
            self::Return => 'Return',
            self::Damage => 'Damage',
            self::Correction => 'Correction',
            self::Initial => 'Initial',
            self::TransferOut => 'Transfer Out',
            self::TransferIn => 'Transfer In',
            self::Refund => 'Refund',
        };
    }
}
