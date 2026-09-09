<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetInstalledSoftware;
use App\Models\SoftwareLicense;
use Illuminate\Database\Seeder;

class InstalledSoftwareSeeder extends Seeder
{
    public function run(): void
    {
        $pc = Asset::where('inventory_number', 'INV-0001')->first();
        if (! $pc) {
            return;
        }

        $office = SoftwareLicense::where('name', 'Microsoft 365 Apps')->first();

        AssetInstalledSoftware::firstOrCreate(
            ['asset_id' => $pc->id, 'name' => 'Microsoft 365 Apps'],
            [
                'vendor' => 'Microsoft',
                'version' => '2408',
                'software_license_id' => $office?->id,
                'installed_at' => now()->subMonths(3)->toDateString(),
            ]
        );

        AssetInstalledSoftware::firstOrCreate(
            ['asset_id' => $pc->id, 'name' => 'Google Chrome'],
            [
                'vendor' => 'Google',
                'version' => '128.0',
                'installed_at' => now()->subMonths(1)->toDateString(),
            ]
        );

        AssetInstalledSoftware::firstOrCreate(
            ['asset_id' => $pc->id, 'name' => 'CrowdStrike Falcon'],
            [
                'vendor' => 'CrowdStrike',
                'version' => '7.18',
                'installed_at' => now()->subMonths(6)->toDateString(),
            ]
        );
    }
}
