<?php

namespace App\Enums;

enum BudgetPeriodStatus: string
{
    case Upcoming = 'upcoming';
    case Active = 'active';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Upcoming => 'Akan datang',
            self::Active => 'Aktif',
            self::Closed => 'Selesai',
        };
    }
}
