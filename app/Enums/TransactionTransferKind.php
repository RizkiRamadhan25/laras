<?php

namespace App\Enums;

enum TransactionTransferKind: string
{
    case Internal = 'internal';
    case External = 'external';

    public function label(): string
    {
        return match ($this) {
            self::Internal => 'Antar-rekening Laras',
            self::External => 'Ke pihak luar Laras',
        };
    }
}
