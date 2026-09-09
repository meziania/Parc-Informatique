<?php

namespace Database\Seeders;

use App\Enums\ConsumableCategory;
use App\Models\Consumable;
use App\Models\Entity;
use App\Models\Location;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class ConsumableSeeder extends Seeder
{
    public function run(): void
    {
        $entity = Entity::first();
        $repro = Location::where('name', 'Salle reprographie')->first();
        $hp = Supplier::where('name', 'HP Inc.')->first();

        Consumable::firstOrCreate(
            ['sku' => 'TONER-HP-M404'],
            [
                'name' => 'Toner HP 58A (CF258A)',
                'category' => ConsumableCategory::Cartridge,
                'quantity' => 2,
                'min_quantity' => 3,
                'unit' => 'unité',
                'location_id' => $repro?->id,
                'supplier_id' => $hp?->id,
                'notes' => 'Compatible LaserJet Pro M404',
                'entity_id' => $entity?->id,
            ]
        );

        Consumable::firstOrCreate(
            ['sku' => 'CABLE-RJ45-3M'],
            [
                'name' => 'Câble RJ45 Cat6 3 m',
                'category' => ConsumableCategory::Cable,
                'quantity' => 25,
                'min_quantity' => 10,
                'unit' => 'unité',
                'entity_id' => $entity?->id,
            ]
        );

        Consumable::firstOrCreate(
            ['sku' => 'PAPER-A4'],
            [
                'name' => 'Ramettes papier A4 80 g',
                'category' => ConsumableCategory::Paper,
                'quantity' => 0,
                'min_quantity' => 5,
                'unit' => 'ramette',
                'location_id' => $repro?->id,
                'entity_id' => $entity?->id,
            ]
        );
    }
}
