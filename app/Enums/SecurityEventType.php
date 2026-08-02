<?php

namespace App\Enums;

enum SecurityEventType: string
{
    case PasswordChanged =
        'password_changed';

    case OtherSessionsLoggedOut =
        'other_sessions_logged_out';

    public function label(): string
    {
        return match ($this) {
            self::PasswordChanged => 'Kata sandi diubah',

            self::OtherSessionsLoggedOut => 'Perangkat lain dikeluarkan',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PasswordChanged => 'Kata sandi akun berhasil diperbarui.',

            self::OtherSessionsLoggedOut => 'Seluruh sesi selain perangkat ini telah dikeluarkan.',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PasswordChanged => 'key-round',

            self::OtherSessionsLoggedOut => 'monitor-smartphone',
        };
    }

    public function colorClass(): string
    {
        return match ($this) {
            self::PasswordChanged => 'bg-emerald-100 text-emerald-700',

            self::OtherSessionsLoggedOut => 'bg-blue-100 text-blue-700',
        };
    }
}
