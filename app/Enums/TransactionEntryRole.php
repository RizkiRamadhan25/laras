<?php

namespace App\Enums;

enum TransactionEntryRole: string
{
    case Principal = 'principal';
    case Fee = 'fee';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::Principal => 'Nilai utama',
            self::Fee => 'Biaya',
            self::Adjustment => 'Penyesuaian',
        };
    }
}
