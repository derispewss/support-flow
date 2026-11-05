<?php

namespace App\Shared\Enums;

enum TicketChannel: string
{
    case Web = 'web';
    case Email = 'email';
    case Api = 'api';
    case Portal = 'portal';

    public function label(): string
    {
        return match ($this) {
            self::Web => 'Web',
            self::Email => 'Email',
            self::Api => 'API',
            self::Portal => 'Portal',
        };
    }
}
