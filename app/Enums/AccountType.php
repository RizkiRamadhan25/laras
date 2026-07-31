<?php

namespace App\Enums;

enum AccountType: string
{
    case Bank = 'bank';
    case EWallet = 'e_wallet';
    case Cash = 'cash';
    case Investment = 'investment';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Bank => 'Rekening bank',
            self::EWallet => 'Dompet digital',
            self::Cash => 'Uang tunai',
            self::Investment => 'Investasi',
            self::Other => 'Lainnya',
        };
    }
}
