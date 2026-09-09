<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\Entity;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(UserRole $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'entity_id' => Entity::firstOrCreate(['name' => 'Test'])->id,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_user_dashboard_shows_only_own_stats(): void
    {
        $user = $this->userWithRole(UserRole::User);
        $other = $this->userWithRole(UserRole::User);

        Ticket::create([
            'title' => 'Mon ticket ouvert',
            'description' => 'desc',
            'type' => 'incident',
            'priority' => TicketPriority::Medium,
            'status' => TicketStatus::New,
            'requester_id' => $user->id,
        ]);

        Ticket::create([
            'title' => 'Ticket dun autre',
            'description' => 'desc',
            'type' => 'incident',
            'priority' => TicketPriority::Urgent,
            'status' => TicketStatus::New,
            'requester_id' => $other->id,
        ]);

        Asset::create([
            'name' => 'Mon PC',
            'type' => 'computer',
            'status' => AssetStatus::InUse,
            'inventory_number' => 'INV-DASH-1',
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('view', 'user')
                ->where('stats.open_tickets', 1)
                ->where('stats.overdue_tickets', 0)
                ->where('stats.my_assets', 1)
                ->where('stats.pending_satisfaction', 0)
                ->has('recent_tickets', 1)
                ->where('recent_tickets.0.title', 'Mon ticket ouvert')
            );
    }

    public function test_technician_dashboard_aggregates_queue_and_fleet(): void
    {
        $technician = $this->userWithRole(UserRole::Technician);
        $user = $this->userWithRole(UserRole::User);

        Ticket::create([
            'title' => 'Ticket non assigné',
            'description' => 'desc',
            'type' => 'incident',
            'priority' => TicketPriority::Urgent,
            'status' => TicketStatus::New,
            'requester_id' => $user->id,
        ]);

        Ticket::create([
            'title' => 'Ticket assigné à moi',
            'description' => 'desc',
            'type' => 'request',
            'priority' => TicketPriority::Medium,
            'status' => TicketStatus::Assigned,
            'requester_id' => $user->id,
            'assignee_id' => $technician->id,
        ]);

        Asset::create([
            'name' => 'Imprimante HS',
            'type' => 'printer',
            'status' => AssetStatus::Broken,
            'inventory_number' => 'INV-DASH-2',
        ]);

        Asset::create([
            'name' => 'PC stock',
            'type' => 'computer',
            'status' => AssetStatus::InStock,
            'inventory_number' => 'INV-DASH-3',
        ]);

        $this->actingAs($technician)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('view', 'technician')
                ->where('stats.open_tickets', 2)
                ->where('stats.unassigned_tickets', 1)
                ->where('stats.urgent_tickets', 1)
                ->where('stats.overdue_tickets', 0)
                ->where('stats.at_risk_tickets', 1) // urgent = SLA 4 h → à risque dès la création
                ->where('stats.my_tickets', 1)
                ->where('stats.broken_assets', 1)
                ->where('stats.assets_total', 2)
                ->where('stats.assets_in_stock', 1)
                ->where('stats.satisfaction_count', 0)
                ->where('stats.awaiting_satisfaction', 0)
                ->where('stats.sla_met_rate_30d', null)
                ->where('stats.avg_satisfaction_30d', null)
                ->where('stats.satisfaction_count_30d', 0)
                ->has('recent_tickets', 2)
                ->has('overdue_tickets', 0)
                ->has('urgent_tickets', 1)
                ->has('broken_assets', 1)
                ->has('my_tickets', 1)
            );
    }

    public function test_technician_dashboard_reports_sla_and_satisfaction_30d(): void
    {
        $technician = $this->userWithRole(UserRole::Technician);
        $user = $this->userWithRole(UserRole::User);

        Ticket::create([
            'title' => 'SLA ok',
            'description' => 'desc',
            'type' => 'incident',
            'priority' => TicketPriority::Medium,
            'status' => TicketStatus::Resolved,
            'requester_id' => $user->id,
            'due_at' => now()->subDays(2),
            'resolved_at' => now()->subDays(3),
            'satisfaction_rating' => 5,
            'satisfaction_rated_at' => now()->subDays(1),
        ]);

        Ticket::create([
            'title' => 'SLA late',
            'description' => 'desc',
            'type' => 'incident',
            'priority' => TicketPriority::Medium,
            'status' => TicketStatus::Closed,
            'requester_id' => $user->id,
            'due_at' => now()->subDays(5),
            'resolved_at' => now()->subDays(4),
            'closed_at' => now()->subDays(4),
            'satisfaction_rating' => 3,
            'satisfaction_rated_at' => now()->subDays(2),
        ]);

        $this->actingAs($technician)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('stats.sla_met_rate_30d', 50)
                ->where('stats.avg_satisfaction_30d', 4)
                ->where('stats.satisfaction_count_30d', 2)
                ->where('stats.awaiting_satisfaction', 0)
            );
    }
}
