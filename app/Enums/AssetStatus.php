<?php

namespace App\Enums;

enum AssetStatus: string
{
    case InUse = 'in_use';
    case InStock = 'in_stock';
    case Broken = 'broken';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::InUse => 'En service',
            self::InStock => 'En stock',
            self::Broken => 'En panne',
            self::Retired => 'Retiré',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::InUse => 'green',
            self::InStock => 'blue',
            self::Broken => 'red',
            self::Retired => 'gray',
        };
    }
}
