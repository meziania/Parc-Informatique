<?php

namespace Database\Seeders;

use App\Enums\ReservationStatus;
use App\Models\Asset;
use App\Models\Location;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReservationSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'utilisateur@parc.local')->first();
        $tech = User::where('email', 'technicien@parc.local')->first();
        $portable = Asset::where('inventory_number', 'INV-0001')->first();
        $salle = Location::where('name', 'Salle de réunion')->first()
            ?? Location::where('name', 'Bureau 201')->first();

        if (! $user) {
            return;
        }

        Reservation::firstOrCreate(
            [
                'user_id' => $user->id,
                'title' => 'Formation Excel — salle',
                'starts_at' => now()->addDays(2)->setTime(9, 0),
            ],
            [
                'location_id' => $salle?->id,
                'asset_id' => null,
                'purpose' => 'Session formation métier (10 personnes).',
                'ends_at' => now()->addDays(2)->setTime(12, 0),
                'status' => ReservationStatus::Pending,
            ]
        );

        Reservation::firstOrCreate(
            [
                'user_id' => $user->id,
                'title' => 'Prêt PC portable démo client',
                'starts_at' => now()->addDays(5)->setTime(14, 0),
            ],
            [
                'asset_id' => $portable?->id,
                'location_id' => null,
                'purpose' => 'Présentation client hors site.',
                'ends_at' => now()->addDays(5)->setTime(18, 0),
                'status' => ReservationStatus::Approved,
                'reviewed_by' => $tech?->id,
                'reviewed_at' => now()->subDay(),
                'review_note' => 'OK — récupérer à l’accueil IT.',
            ]
        );
    }
}
