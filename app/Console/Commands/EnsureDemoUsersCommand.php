<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class EnsureDemoUsersCommand extends Command
{
    protected $signature = 'gpsi:demo-users';

    protected $description = 'Crée les comptes de démo via Query Builder (sans recoder le hash Eloquent)';

    public function handle(): int
    {
        $this->line('Connexion : '.config('database.default'));

        if (! Schema::hasTable('users') || ! Schema::hasTable('entities')) {
            $this->error('Tables users/entities absentes.');

            return self::FAILURE;
        }

        $now = now();
        $entityId = DB::table('entities')->where('name', 'Organisation principale')->value('id');

        if (! $entityId) {
            $entityId = DB::table('entities')->insertGetId([
                'name' => 'Organisation principale',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $accounts = [
            ['name' => 'Admin Parc', 'email' => 'admin@parc.local', 'role' => 'admin'],
            ['name' => 'Technicien Parc', 'email' => 'technicien@parc.local', 'role' => 'technician'],
            ['name' => 'Utilisateur Parc', 'email' => 'utilisateur@parc.local', 'role' => 'user'],
        ];

        foreach ($accounts as $account) {
            $hash = Hash::make('password');
            $payload = [
                'name' => $account['name'],
                'password' => $hash,
                'role' => $account['role'],
                'entity_id' => $entityId,
                'updated_at' => $now,
            ];

            if (Schema::hasColumn('users', 'is_active')) {
                $payload['is_active'] = true;
            }

            $existing = DB::table('users')->where('email', $account['email'])->first();

            if ($existing) {
                DB::table('users')->where('id', $existing->id)->update($payload);
            } else {
                DB::table('users')->insert([
                    ...$payload,
                    'email' => $account['email'],
                    'email_verified_at' => $now,
                    'created_at' => $now,
                ]);
            }

            $stored = DB::table('users')->where('email', $account['email'])->value('password');
            if (! Hash::check('password', $stored)) {
                $this->error('Hash invalide pour '.$account['email']);

                return self::FAILURE;
            }

            $this->info('OK '.$account['email']);
        }

        return self::SUCCESS;
    }
}
