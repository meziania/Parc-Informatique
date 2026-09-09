<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Entity;
use App\Models\User;
use Illuminate\Console\Command;

class EnsureDemoUsersCommand extends Command
{
    protected $signature = 'gpsi:demo-users';

    protected $description = 'Crée ou réactive les 3 comptes de démo (mot de passe: password)';

    public function handle(): int
    {
        $entity = Entity::query()->firstOrCreate(['name' => 'Organisation principale']);

        $users = [
            ['name' => 'Admin Parc', 'email' => 'admin@parc.local', 'role' => UserRole::Admin],
            ['name' => 'Technicien Parc', 'email' => 'technicien@parc.local', 'role' => UserRole::Technician],
            ['name' => 'Utilisateur Parc', 'email' => 'utilisateur@parc.local', 'role' => UserRole::User],
        ];

        foreach ($users as $user) {
            User::query()->updateOrCreate(
                ['email' => $user['email']],
                [
                    ...$user,
                    'entity_id' => $entity->id,
                    'password' => 'password',
                    'is_active' => true,
                ],
            );
        }

        $this->info('Comptes démo OK : admin@parc.local / technicien@parc.local / utilisateur@parc.local');

        return self::SUCCESS;
    }
}
