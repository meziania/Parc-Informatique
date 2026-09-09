<?php

namespace Database\Seeders;

use App\Enums\TicketPriority;
use App\Enums\TicketType;
use App\Models\ServiceCatalogItem;
use Illuminate\Database\Seeder;

class ServiceCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'code' => 'VPN',
                'name' => 'Accès VPN',
                'description' => 'Problème de connexion VPN ou demande d’accès distant.',
                'type' => TicketType::Incident,
                'default_priority' => TicketPriority::High,
                'sla_hours' => 8,
                'sort_order' => 10,
            ],
            [
                'code' => 'MATERIEL',
                'name' => 'Panne matériel',
                'description' => 'PC, écran, clavier, imprimante ou autre équipement en panne.',
                'type' => TicketType::Incident,
                'default_priority' => TicketPriority::Urgent,
                'sla_hours' => 4,
                'sort_order' => 20,
            ],
            [
                'code' => 'COMPTE',
                'name' => 'Compte / accès applicatif',
                'description' => 'Création, reset ou droits d’accès à une application métier.',
                'type' => TicketType::Request,
                'default_priority' => TicketPriority::Medium,
                'sla_hours' => 24,
                'sort_order' => 30,
                'requires_approval' => true,
            ],
            [
                'code' => 'LOGICIEL',
                'name' => 'Installation logiciel',
                'description' => 'Demande d’installation ou mise à jour d’un logiciel validé.',
                'type' => TicketType::Request,
                'default_priority' => TicketPriority::Low,
                'sla_hours' => 72,
                'sort_order' => 40,
                'requires_approval' => true,
            ],
            [
                'code' => 'RESEAU',
                'name' => 'Réseau / Wi‑Fi',
                'description' => 'Coupure réseau, Wi‑Fi, lenteur ou imprimante réseau.',
                'type' => TicketType::Incident,
                'default_priority' => TicketPriority::High,
                'sla_hours' => 8,
                'sort_order' => 50,
            ],
            [
                'code' => 'POSTE',
                'name' => 'Demande de matériel',
                'description' => 'Demande de poste, périphérique ou remplacement.',
                'type' => TicketType::Request,
                'default_priority' => TicketPriority::Medium,
                'sla_hours' => 48,
                'sort_order' => 60,
                'requires_approval' => true,
            ],
        ];

        foreach ($items as $item) {
            ServiceCatalogItem::query()->updateOrCreate(
                ['code' => $item['code']],
                [...$item, 'is_active' => true, 'requires_approval' => $item['requires_approval'] ?? false],
            );
        }
    }
}
