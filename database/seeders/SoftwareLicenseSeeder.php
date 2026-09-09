<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Entity;
use App\Models\SoftwareLicense;
use Illuminate\Database\Seeder;

class SoftwareLicenseSeeder extends Seeder
{
    public function run(): void
    {
        $entity = Entity::first();
        $pc1 = Asset::where('inventory_number', 'INV-0001')->first();
        $pc2 = Asset::where('inventory_number', 'INV-0002')->first();

        $office = SoftwareLicense::firstOrCreate(
            ['name' => 'Microsoft 365 Apps'],
            [
                'vendor' => 'Microsoft',
                'product_key' => 'XXXXX-DEMO-M365-PARC',
                'seats' => 50,
                'purchase_date' => now()->subYear()->toDateString(),
                'expiry_date' => now()->addDays(20)->toDateString(),
                'notes' => 'Abonnement annuel — alerte 30 j avant renouvellement.',
                'is_active' => true,
                'entity_id' => $entity?->id,
            ]
        );

        $adobe = SoftwareLicense::firstOrCreate(
            ['name' => 'Adobe Acrobat Pro'],
            [
                'vendor' => 'Adobe',
                'product_key' => null,
                'seats' => 5,
                'purchase_date' => now()->subMonths(8)->toDateString(),
                'expiry_date' => now()->subDays(10)->toDateString(),
                'notes' => 'Licence expirée — à renouveler.',
                'is_active' => true,
                'entity_id' => $entity?->id,
            ]
        );

        SoftwareLicense::firstOrCreate(
            ['name' => 'Windows 11 Pro (OEM)'],
            [
                'vendor' => 'Microsoft',
                'seats' => 100,
                'purchase_date' => now()->subYears(2)->toDateString(),
                'expiry_date' => null,
                'is_active' => true,
                'entity_id' => $entity?->id,
            ]
        );

        if ($pc1 && ! $office->assets()->where('assets.id', $pc1->id)->exists()) {
            $office->assets()->attach($pc1->id, ['assigned_at' => now()]);
        }

        if ($pc2 && ! $office->assets()->where('assets.id', $pc2->id)->exists()) {
            $office->assets()->attach($pc2->id, ['assigned_at' => now()]);
        }

        if ($pc1 && ! $adobe->assets()->where('assets.id', $pc1->id)->exists()) {
            $adobe->assets()->attach($pc1->id, ['assigned_at' => now()]);
        }
    }
}
