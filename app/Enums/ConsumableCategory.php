<?php

namespace App\Enums;

enum ConsumableCategory: string
{
    case Cartridge = 'cartridge';
    case Cable = 'cable';
    case Paper = 'paper';
    case Accessory = 'accessory';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cartridge => 'Cartouche / toner',
            self::Cable => 'Câble',
            self::Paper => 'Papier',
            self::Accessory => 'Accessoire',
            self::Other => 'Autre',
        };
    }
}
