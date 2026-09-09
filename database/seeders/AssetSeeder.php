<?php

namespace Database\Seeders;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\Entity;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;

class AssetSeeder extends Seeder
{
    public function run(): void
    {
        $entity = Entity::first();
        $utilisateur = User::where('email', 'utilisateur@parc.local')->first();
        $technicien = User::where('email', 'technicien@parc.local')->first();
        $bureau = Location::where('name', 'Bureau 201')->first();
        $serveurs = Location::where('name', 'Salle serveurs')->first();
        $repro = Location::where('name', 'Salle reprographie')->first();

        $assets = [
            [
                'name' => 'PC Portable Dell Latitude 5440',
                'type' => AssetType::Computer,
                'status' => AssetStatus::InUse,
                'inventory_number' => 'INV-0001',
                'serial_number' => 'DL5440-XK2P9',
                'manufacturer' => 'Dell',
                'model' => 'Latitude 5440',
                'purchase_date' => '2024-03-15',
                'warranty_end' => '2027-03-14',
                'next_maintenance_at' => now()->addDays(15)->toDateString(),
                'location_id' => $bureau?->id,
                'user_id' => $utilisateur?->id,
            ],
            [
                'name' => 'PC Fixe HP ProDesk 400',
                'type' => AssetType::Computer,
                'status' => AssetStatus::InUse,
                'inventory_number' => 'INV-0002',
                'serial_number' => 'HP400-M7QW3',
                'manufacturer' => 'HP',
                'model' => 'ProDesk 400 G9',
                'purchase_date' => '2023-11-02',
                'warranty_end' => '2026-11-01',
                'location_id' => $bureau?->id,
                'user_id' => $technicien?->id,
            ],
            [
                'name' => 'Moniteur Dell P2422H',
                'type' => AssetType::Monitor,
                'status' => AssetStatus::InStock,
                'inventory_number' => 'INV-0003',
                'serial_number' => 'DLP24-88FZ1',
                'manufacturer' => 'Dell',
                'model' => 'P2422H',
                'purchase_date' => '2024-06-20',
                'warranty_end' => '2027-06-19',
            ],
            [
                'name' => 'Imprimante HP LaserJet Pro M404',
                'type' => AssetType::Printer,
                'status' => AssetStatus::InUse,
                'inventory_number' => 'INV-0004',
                'serial_number' => 'HPM404-T5R8V',
                'manufacturer' => 'HP',
                'model' => 'LaserJet Pro M404dn',
                'purchase_date' => '2022-09-10',
                'warranty_end' => '2024-09-09',
                'next_maintenance_at' => now()->subDays(5)->toDateString(),
                'location_id' => $repro?->id,
            ],
            [
                'name' => 'iPhone 13',
                'type' => AssetType::Phone,
                'status' => AssetStatus::Broken,
                'inventory_number' => 'INV-0005',
                'serial_number' => 'APL13-JJ4K7D',
                'manufacturer' => 'Apple',
                'model' => 'iPhone 13',
                'purchase_date' => '2022-05-18',
                'warranty_end' => '2023-05-17',
                'notes' => 'Écran fissuré, en attente de réparation.',
            ],
            [
                'name' => 'Switch Cisco Catalyst 24 ports',
                'type' => AssetType::Network,
                'status' => AssetStatus::InUse,
                'inventory_number' => 'INV-0006',
                'serial_number' => 'CSC24-P9M2L',
                'manufacturer' => 'Cisco',
                'model' => 'Catalyst 1200-24T',
                'purchase_date' => '2023-01-25',
                'warranty_end' => '2028-01-24',
                'location_id' => $serveurs?->id,
            ],
        ];

        foreach ($assets as $asset) {
            Asset::firstOrCreate(
                ['inventory_number' => $asset['inventory_number']],
                [...$asset, 'entity_id' => $entity?->id],
            );
        }
    }
}
