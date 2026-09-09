<?php

namespace App\Enums;

enum ApprovalStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente de validation',
            self::Approved => 'Validée',
            self::Rejected => 'Refusée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'orange',
            self::Approved => 'green',
            self::Rejected => 'red',
        };
    }
}
