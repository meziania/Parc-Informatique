<?php

namespace App\Enums;

enum UserRole: string
{
    case User = 'user';
    case Technician = 'technician';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::User => 'Utilisateur',
            self::Technician => 'Technicien',
            self::Admin => 'Administrateur',
        };
    }

    public function isAtLeast(self $role): bool
    {
        $levels = [self::User->value => 1, self::Technician->value => 2, self::Admin->value => 3];

        return $levels[$this->value] >= $levels[$role->value];
    }
}
