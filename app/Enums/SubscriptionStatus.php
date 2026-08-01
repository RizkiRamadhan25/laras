<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Paused => 'Dijeda',
            self::Cancelled => 'Dihentikan',
            self::Expired => 'Berakhir',
        };
    }

    public function canGenerateBilling(): bool
    {
        return $this === self::Active;
    }
}