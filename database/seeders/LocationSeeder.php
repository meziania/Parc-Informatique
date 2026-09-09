<?php

namespace Database\Seeders;

use App\Models\Entity;
use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $entity = Entity::first();

        $locations = [
            ['name' => 'Bureau 201', 'building' => 'Siège — Bâtiment A', 'room' => '2e étage'],
            ['name' => 'Salle serveurs', 'building' => 'Siège — Bâtiment A', 'room' => 'Rez-de-chaussée'],
            ['name' => 'Salle reprographie', 'building' => 'Siège — Bâtiment B', 'room' => '1er étage'],
            ['name' => 'Agence Lyon', 'building' => null, 'room' => null],
            ['name' => 'Salle de réunion', 'building' => 'Siège — Bâtiment B', 'room' => '2e étage'],
        ];

        foreach ($locations as $location) {
            Location::firstOrCreate(
                ['name' => $location['name']],
                [...$location, 'entity_id' => $entity?->id],
            );
        }
    }
}
