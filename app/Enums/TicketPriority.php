<?php

namespace App\Enums;

enum TicketPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Basse',
            self::Medium => 'Normale',
            self::High => 'Haute',
            self::Urgent => 'Urgente',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low => 'gray',
            self::Medium => 'blue',
            self::High => 'orange',
            self::Urgent => 'red',
        };
    }

    /** Délai de résolution cible (SLA) en heures. */
    public function slaHours(): int
    {
        return match ($this) {
            self::Urgent => 4,
            self::High => 8,
            self::Medium => 24,
            self::Low => 72,
        };
    }

    public function slaLabel(): string
    {
        $hours = $this->slaHours();

        if ($hours < 24) {
            return "{$hours} h";
        }

        $days = (int) ($hours / 24);

        return $days === 1 ? '1 jour' : "{$days} jours";
    }
}
