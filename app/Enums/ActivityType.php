<?php

namespace App\Enums;

enum ActivityType: string
{
    case Task = 'task';
    case Event = 'event';
    case Deadline = 'deadline';

    public function label(): string
    {
        return match ($this) {
            self::Task => 'Tugas',
            self::Event => 'Acara',
            self::Deadline => 'Deadline',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Task => 'list-todo',
            self::Event => 'calendar-days',
            self::Deadline => 'alarm-clock',
        };
    }
}
