<?php

namespace App\Enums;

enum ActivityStatus: string
{
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Direncanakan',
            self::InProgress => 'Sedang dikerjakan',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function isOpen(): bool
    {
        return in_array(
            $this,
            [
                self::Planned,
                self::InProgress,
            ],
            true
        );
    }
}
