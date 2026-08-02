<?php

namespace App\Enums;

enum BudgetAlertLevel: string
{
    case Safe = 'safe';
    case Warning = 'warning';
    case Exceeded = 'exceeded';

    public function label(): string
    {
        return match ($this) {
            self::Safe => 'Aman',
            self::Warning => 'Mendekati batas',
            self::Exceeded => 'Batas terlampaui',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Safe => 'Penggunaan masih berada di bawah ambang peringatan.',

            self::Warning => 'Penggunaan telah mendekati batas anggaran.',

            self::Exceeded => 'Penggunaan telah mencapai atau melewati batas anggaran.',
        };
    }

    public function colorClass(): string
    {
        return match ($this) {
            self::Safe => 'border-emerald-200 bg-emerald-50 text-emerald-700',

            self::Warning => 'border-amber-200 bg-amber-50 text-amber-700',

            self::Exceeded => 'border-rose-200 bg-rose-50 text-rose-700',
        };
    }
}
