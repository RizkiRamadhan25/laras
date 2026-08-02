<?php

namespace App\Enums;

enum BudgetPeriodType: string
{
    case Monthly = 'monthly';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Bulanan',
            self::Custom => 'Periode khusus',
        };
    }

    public function isRecurring(): bool
    {
        return $this === self::Monthly;
    }
}
