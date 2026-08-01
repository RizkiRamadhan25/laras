<?php

namespace App\Enums;

enum SubscriptionBillingStatus: string
{
    case Scheduled = 'scheduled';
    case Processing = 'processing';
    case Posted = 'posted';
    case Failed = 'failed';
    case Skipped = 'skipped';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Terjadwal',
            self::Processing => 'Sedang diproses',
            self::Posted => 'Berhasil dicatat',
            self::Failed => 'Gagal',
            self::Skipped => 'Dilewati',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function isFinal(): bool
    {
        return in_array(
            $this,
            [
                self::Posted,
                self::Skipped,
                self::Cancelled,
            ],
            true
        );
    }
}