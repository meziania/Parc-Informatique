<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Entity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(UserRole $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'entity_id' => Entity::firstOrCreate(['name' => 'Test'])->id,
        ]);
    }

    public function test_changing_a_user_role_creates_an_audit_log(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        $target = $this->userWithRole(UserRole::User);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'role' => UserRole::Technician->value,
                'entity_id' => $target->entity_id,
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'user.role_changed',
            'subject_type' => User::class,
            'subject_id' => $target->id,
        ]);

        $log = AuditLog::first();
        $this->assertSame('user', $log->old_values['role']);
        $this->assertSame('technician', $log->new_values['role']);
    }

    public function test_non_admin_cannot_view_audit_logs(): void
    {
        $technician = $this->userWithRole(UserRole::Technician);

        $this->actingAs($technician)
            ->get(route('admin.audit-logs.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_audit_logs_page(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);

        AuditLog::create([
            'actor_id' => $admin->id,
            'action' => 'user.role_changed',
            'subject_type' => User::class,
            'subject_id' => $admin->id,
            'old_values' => ['role' => 'user'],
            'new_values' => ['role' => 'technician'],
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.audit-logs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/AuditLogs/Index')
                ->has('logs.data', 1)
                ->where('logs.data.0.action', 'user.role_changed')
            );
    }
}
