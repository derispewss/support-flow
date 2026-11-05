<?php

namespace App\Shared\Enums;

enum TicketStatus: string
{
    case New = 'new';
    case Open = 'open';
    case Pending = 'pending';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Baru',
            self::Open => 'Terbuka',
            self::Pending => 'Menunggu',
            self::Resolved => 'Selesai',
            self::Closed => 'Ditutup',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::New, self::Open => 'open',
            self::Pending => 'pending',
            self::Resolved => 'resolved',
            self::Closed => 'faint',
        };
    }

    public function isFinal(): bool
    {
        return $this === self::Closed;
    }

    /**
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::New => [self::Open],
            self::Open => [self::Pending, self::Resolved],
            self::Pending => [self::Open, self::Resolved],
            self::Resolved => [self::Open, self::Closed],
            self::Closed => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }
}
