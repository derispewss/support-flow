<?php

namespace App\Shared\Enums;

enum MembershipStatus: string
{
    case Active = 'active';
    case Invited = 'invited';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Invited => 'Diundang',
            self::Suspended => 'Ditangguhkan',
        };
    }
}
