<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Entity;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $entity = Entity::firstOrCreate(['name' => 'Organisation principale']);

        $users = [
            ['name' => 'Admin Parc', 'email' => 'admin@parc.local', 'role' => UserRole::Admin],
            ['name' => 'Technicien Parc', 'email' => 'technicien@parc.local', 'role' => UserRole::Technician],
            ['name' => 'Utilisateur Parc', 'email' => 'utilisateur@parc.local', 'role' => UserRole::User],
        ];

        foreach ($users as $user) {
            User::firstOrCreate(
                ['email' => $user['email']],
                [...$user, 'entity_id' => $entity->id, 'password' => 'password'],
            );
        }

        $this->call([
            LocationSeeder::class,
            AssetSeeder::class,
            ServiceCatalogSeeder::class,
            SoftwareLicenseSeeder::class,
            SupplierContractSeeder::class,
            InstalledSoftwareSeeder::class,
            ConsumableSeeder::class,
            TicketSeeder::class,
            FaqSeeder::class,
            ReservationSeeder::class,
        ]);
    }
}
