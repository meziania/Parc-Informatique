<?php

namespace App\Enums;

enum FaqCategory: string
{
    case Account = 'account';
    case Hardware = 'hardware';
    case Software = 'software';
    case Network = 'network';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Account => 'Compte & accès',
            self::Hardware => 'Matériel',
            self::Software => 'Logiciels',
            self::Network => 'Réseau & VPN',
            self::Other => 'Autre',
        };
    }
}
