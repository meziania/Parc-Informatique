<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('entities')) {
            return;
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

        $users = [
            ['name' => 'Admin Parc', 'email' => 'admin@parc.local', 'role' => 'admin'],
            ['name' => 'Technicien Parc', 'email' => 'technicien@parc.local', 'role' => 'technician'],
            ['name' => 'Utilisateur Parc', 'email' => 'utilisateur@parc.local', 'role' => 'user'],
        ];

        foreach ($users as $user) {
            $row = [
                'name' => $user['name'],
                'password' => Hash::make('password'),
                'role' => $user['role'],
                'entity_id' => $entityId,
                'is_active' => true,
                'email_verified_at' => $now,
                'updated_at' => $now,
            ];

            $existing = DB::table('users')->where('email', $user['email'])->first();

            if ($existing) {
                DB::table('users')->where('id', $existing->id)->update($row);
            } else {
                DB::table('users')->insert([
                    ...$row,
                    'email' => $user['email'],
                    'created_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('users')->whereIn('email', [
            'admin@parc.local',
            'technicien@parc.local',
            'utilisateur@parc.local',
        ])->delete();
    }
};
