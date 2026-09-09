<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Entity;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnsureDemoUsersCommand extends Command
{
    protected $signature = 'gpsi:demo-users';

    protected $description = 'Crée ou réactive les comptes de démonstration et affiche l\'état de la base';

    public function handle(): int
    {
        $connection = DB::connection();

        $this->line('DB connexion : '.$connection->getName().' / '.$connection->getDatabaseName());

        if (! Schema::hasTable('users')) {
            $this->error('Table users absente : les migrations n\'ont pas abouti.');

            return self::FAILURE;
        }

        $password = (string) env('DEMO_PASSWORD', 'password');
        $entity = Entity::query()->firstOrCreate(['name' => 'Organisation principale']);

        $accounts = [
            ['name' => 'Admin Parc', 'email' => 'admin@parc.local', 'role' => UserRole::Admin],
            ['name' => 'Technicien Parc', 'email' => 'technicien@parc.local', 'role' => UserRole::Technician],
            ['name' => 'Utilisateur Parc', 'email' => 'utilisateur@parc.local', 'role' => UserRole::User],
        ];

        foreach ($accounts as $account) {
            User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    ...$account,
                    'entity_id' => $entity->id,
                    'password' => $password,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
            );
        }

        $this->info('Comptes prêts : '.User::query()->count().' utilisateurs en base.');

        return self::SUCCESS;
    }
}
