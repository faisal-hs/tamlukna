<?php

namespace App\Enums;

enum AccountStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Deactivated = 'deactivated';

    public function canAuthenticate(): bool
    {
        return $this === self::Active;
    }
}
