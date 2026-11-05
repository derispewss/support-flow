<?php

namespace App\Shared\Enums;

enum MessageType: string
{
    case Reply = 'reply';
    case Note = 'note';
    case Event = 'event';

    public function label(): string
    {
        return match ($this) {
            self::Reply => 'Balasan',
            self::Note => 'Catatan internal',
            self::Event => 'Peristiwa',
        };
    }

    public function isInternal(): bool
    {
        return $this === self::Note;
    }

    public function isVisibleToCustomer(): bool
    {
        return $this !== self::Note;
    }
}
