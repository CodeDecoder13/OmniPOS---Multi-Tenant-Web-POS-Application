<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Card = 'card';
    case EWallet = 'e_wallet';
    case BankTransfer = 'bank_transfer';
    case Other = 'other';
    case Online = 'online';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Card => 'Card',
            self::EWallet => 'E-Wallet',
            self::BankTransfer => 'Bank Transfer',
            self::Other => 'Other',
            self::Online => 'Online',
        };
    }
}
