<?php

namespace App\Enums;

enum UserRole: int
{
    case User = 1;
    case Admin = 2;

    public function label(): string
    {
        return match ($this) {
            self::User => 'User',
            self::Admin => 'Admin',
        };
    }
}
