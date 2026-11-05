<?php

namespace App\Shared\Enums;

enum UserRole: string
{
    case Superadmin = 'superadmin';
    case Owner = 'owner';
    case Admin = 'admin';
    case Agent = 'agent';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::Superadmin => 'Superadmin',
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::Agent => 'Agent',
            self::Customer => 'Customer',
        };
    }

    public function canManageOrganization(): bool
    {
        return in_array($this, [self::Superadmin, self::Owner, self::Admin], true);
    }

    public function isStaff(): bool
    {
        return in_array($this, [self::Superadmin, self::Owner, self::Admin, self::Agent], true);
    }
}
