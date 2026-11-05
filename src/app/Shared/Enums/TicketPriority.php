<?php

namespace App\Shared\Enums;

enum TicketPriority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Rendah',
            self::Normal => 'Normal',
            self::High => 'Tinggi',
            self::Urgent => 'Mendesak',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Low => 'faint',
            self::Normal => 'ink',
            self::High => 'pending',
            self::Urgent => 'open',
        };
    }

    public function weight(): int
    {
        return match ($this) {
            self::Low => 1,
            self::Normal => 2,
            self::High => 3,
            self::Urgent => 4,
        };
    }
}
