<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Entity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(UserRole $role, array $overrides = []): User
    {
        return User::factory()->create([
            'role' => $role,
            'entity_id' => Entity::firstOrCreate(['name' => 'Test'])->id,
            ...$overrides,
        ]);
    }

    public function test_admin_can_create_technician_with_generated_password(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        $entityId = $admin->entity_id;

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Nouveau Tech',
            'email' => 'nouveau.tech@parc.local',
            'role' => UserRole::Technician->value,
            'entity_id' => $entityId,
            'is_active' => true,
        ]);

        $user = User::where('email', 'nouveau.tech@parc.local')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->is_active);
        $this->assertSame(UserRole::Technician, $user->role);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('generated_password');

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'user.created',
            'subject_id' => $user->id,
        ]);
    }

    public function test_admin_can_update_user_profile_and_role(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        $target = $this->userWithRole(UserRole::User);

        $this->actingAs($admin)->put(route('admin.users.update', $target), [
            'name' => 'Utilisateur Modifié',
            'email' => $target->email,
            'role' => UserRole::Technician->value,
            'entity_id' => $target->entity_id,
            'is_active' => true,
        ])->assertRedirect(route('admin.users.index'));

        $target->refresh();
        $this->assertSame('Utilisateur Modifié', $target->name);
        $this->assertSame(UserRole::Technician, $target->role);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.role_changed',
            'subject_id' => $target->id,
        ]);
    }

    public function test_admin_can_deactivate_user_and_block_login(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        $target = $this->userWithRole(UserRole::Technician, [
            'email' => 'tech.disabled@parc.local',
            'password' => 'password',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-active', $target))
            ->assertRedirect();

        $this->assertFalse($target->fresh()->is_active);

        // Quitter la session admin avant de tester le login du compte désactivé
        $this->post('/logout');
        $this->assertGuest();

        $this->post('/login', [
            'email' => 'tech.disabled@parc.local',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_can_reset_password(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        $target = $this->userWithRole(UserRole::User, [
            'password' => 'old-password',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.users.reset-password', $target));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('generated_password');

        $plain = session('generated_password');
        $this->assertTrue(Hash::check($plain, $target->fresh()->password));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.password_reset',
            'subject_id' => $target->id,
        ]);
    }

    public function test_admin_cannot_deactivate_own_account(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-active', $admin))
            ->assertForbidden();
    }

    public function test_cannot_demote_last_active_admin(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => UserRole::Technician->value,
                'entity_id' => $admin->entity_id,
                'is_active' => true,
            ])
            ->assertSessionHasErrors('role');
    }

    public function test_non_admin_cannot_manage_users(): void
    {
        $technician = $this->userWithRole(UserRole::Technician);

        $this->actingAs($technician)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($technician)->get(route('admin.users.create'))->assertForbidden();
    }

    public function test_admin_users_index_renders(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users/Index')
                ->has('users')
                ->has('roles')
            );
    }
}
