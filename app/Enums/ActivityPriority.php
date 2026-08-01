<?php

namespace App\Enums;

enum ActivityPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Rendah',
            self::Medium => 'Sedang',
            self::High => 'Tinggi',
            self::Urgent => 'Mendesak',
        };
    }

    public function weight(): int
    {
        return match ($this) {
            self::Low => 1,
            self::Medium => 2,
            self::High => 3,
            self::Urgent => 4,
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low => '#64748B',
            self::Medium => '#3B82F6',
            self::High => '#F59E0B',
            self::Urgent => '#EF4444',
        };
    }
}
