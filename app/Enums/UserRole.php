<?php

namespace App\Enums;

enum UserRole: string
{
    case User = 'USER';
    case Admin = 'ADMIN';

    public function label(): string
    {
        return match ($this) {
            self::User => 'Utilisateur',
            self::Admin => 'Administrateur',
        };
    }
}
