<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Contract;
use App\Models\Entity;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierContractSeeder extends Seeder
{
    public function run(): void
    {
        $entity = Entity::first();

        $dell = Supplier::firstOrCreate(
            ['name' => 'Dell Technologies'],
            [
                'contact_name' => 'Service Entreprises',
                'email' => 'entreprises@dell.example',
                'phone' => '+212 5 22 00 00 00',
                'website' => 'https://www.dell.com',
                'is_active' => true,
                'entity_id' => $entity?->id,
            ]
        );

        $hp = Supplier::firstOrCreate(
            ['name' => 'HP Inc.'],
            [
                'contact_name' => 'Support Pro',
                'email' => 'pro@hp.example',
                'phone' => '+212 5 22 11 11 11',
                'is_active' => true,
                'entity_id' => $entity?->id,
            ]
        );

        $maintenance = Contract::firstOrCreate(
            ['reference' => 'CTR-MAINT-2025'],
            [
                'title' => 'Contrat maintenance parc PC',
                'supplier_id' => $dell->id,
                'starts_on' => now()->subMonths(6)->toDateString(),
                'ends_on' => now()->addDays(25)->toDateString(),
                'amount' => 48000,
                'currency' => 'MAD',
                'notes' => 'Maintenance préventive + remplacement sous 48 h.',
                'is_active' => true,
                'entity_id' => $entity?->id,
            ]
        );

        $print = Contract::firstOrCreate(
            ['reference' => 'CTR-PRINT-2024'],
            [
                'title' => 'Contrat impression HP',
                'supplier_id' => $hp->id,
                'starts_on' => now()->subYear()->toDateString(),
                'ends_on' => now()->subDays(5)->toDateString(),
                'amount' => 12000,
                'currency' => 'MAD',
                'is_active' => true,
                'entity_id' => $entity?->id,
            ]
        );

        $pc1 = Asset::where('inventory_number', 'INV-0001')->first();
        $pc2 = Asset::where('inventory_number', 'INV-0002')->first();
        $printer = Asset::where('inventory_number', 'INV-0004')->first();

        if ($pc1) {
            $maintenance->assets()->syncWithoutDetaching([$pc1->id => ['linked_at' => now()]]);
        }
        if ($pc2) {
            $maintenance->assets()->syncWithoutDetaching([$pc2->id => ['linked_at' => now()]]);
        }
        if ($printer) {
            $print->assets()->syncWithoutDetaching([$printer->id => ['linked_at' => now()]]);
        }
    }
}
