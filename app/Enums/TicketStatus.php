<?php

namespace App\Enums;

enum TicketStatus: string
{
    case New = 'new';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nouveau',
            self::Assigned => 'Assigné',
            self::InProgress => 'En cours',
            self::Resolved => 'Résolu',
            self::Closed => 'Clos',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'blue',
            self::Assigned => 'purple',
            self::InProgress => 'orange',
            self::Resolved => 'green',
            self::Closed => 'gray',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::New, self::Assigned, self::InProgress], true);
    }
}
