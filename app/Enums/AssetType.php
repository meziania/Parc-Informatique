<?php

namespace App\Enums;

enum AssetType: string
{
    case Computer = 'computer';
    case Monitor = 'monitor';
    case Printer = 'printer';
    case Phone = 'phone';
    case Peripheral = 'peripheral';
    case Network = 'network';

    public function label(): string
    {
        return match ($this) {
            self::Computer => 'Ordinateur',
            self::Monitor => 'Moniteur',
            self::Printer => 'Imprimante',
            self::Phone => 'Téléphone',
            self::Peripheral => 'Périphérique',
            self::Network => 'Équipement réseau',
        };
    }
}
