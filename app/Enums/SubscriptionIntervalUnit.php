<?php

namespace App\Enums;

enum SubscriptionIntervalUnit: string
{
    case Day = 'day';
    case Week = 'week';
    case Month = 'month';
    case Year = 'year';

    public function label(): string
    {
        return match ($this) {
            self::Day => 'Hari',
            self::Week => 'Minggu',
            self::Month => 'Bulan',
            self::Year => 'Tahun',
        };
    }

    public function recurringLabel(int $count): string
    {
        if ($count === 1) {
            return match ($this) {
                self::Day => 'Setiap hari',
                self::Week => 'Setiap minggu',
                self::Month => 'Setiap bulan',
                self::Year => 'Setiap tahun',
            };
        }

        return sprintf(
            'Setiap %d %s',
            $count,
            strtolower($this->label())
        );
    }
}
