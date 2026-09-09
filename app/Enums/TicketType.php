<?php

namespace App\Enums;

enum TicketType: string
{
    case Incident = 'incident';
    case Request = 'request';

    public function label(): string
    {
        return match ($this) {
            self::Incident => 'Incident',
            self::Request => 'Demande',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Incident => 'Quelque chose est en panne ou ne fonctionne plus',
            self::Request => 'Besoin de quelque chose de nouveau (matériel, accès, logiciel…)',
        };
    }
}
