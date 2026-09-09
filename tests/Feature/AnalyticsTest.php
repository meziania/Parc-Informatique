<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Entity;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(UserRole $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'entity_id' => Entity::firstOrCreate(['name' => 'Test'])->id,
        ]);
    }

    public function test_user_cannot_access_analytics(): void
    {
        $user = $this->userWithRole(UserRole::User);

        $this->actingAs($user)
            ->get(route('analytics.index'))
            ->assertForbidden();
    }

    public function test_technician_can_view_analytics_page(): void
    {
        $tech = $this->userWithRole(UserRole::Technician);
        $user = $this->userWithRole(UserRole::User);

        Ticket::create([
            'title' => 'Analytics sample',
            'description' => 'desc',
            'type' => 'incident',
            'priority' => TicketPriority::Medium,
            'status' => TicketStatus::Resolved,
            'requester_id' => $user->id,
            'assignee_id' => $tech->id,
            'resolved_at' => now()->subDays(2),
            'satisfaction_rating' => 5,
            'satisfaction_rated_at' => now()->subDays(1),
        ]);

        $this->actingAs($tech)
            ->get(route('analytics.index', ['days' => 30]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Analytics/Index')
                ->where('filters.days', 30)
                ->where('analytics.period.days', 30)
                ->has('analytics.kpis')
                ->has('analytics.tickets_per_day')
                ->has('analytics.by_priority')
                ->has('analytics.technician_workload')
            );
    }
}
