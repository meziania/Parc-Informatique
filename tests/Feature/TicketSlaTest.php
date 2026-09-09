<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Entity;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TicketSlaTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(UserRole $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'entity_id' => Entity::firstOrCreate(['name' => 'Test'])->id,
        ]);
    }

    public function test_creating_a_ticket_sets_due_at_from_priority_sla(): void
    {
        Carbon::setTestNow('2026-08-07 10:00:00');

        $user = $this->userWithRole(UserRole::User);

        $this->actingAs($user)->post('/tickets', [
            'title' => 'Urgent SLA',
            'description' => 'Besoin rapide',
            'type' => 'incident',
            'priority' => 'urgent',
        ])->assertRedirect();

        $ticket = Ticket::where('title', 'Urgent SLA')->firstOrFail();

        $this->assertNotNull($ticket->due_at);
        $this->assertTrue($ticket->due_at->equalTo(Carbon::parse('2026-08-07 14:00:00')));
        $this->assertSame('ok', $ticket->sla_status);

        Carbon::setTestNow();
    }

    public function test_open_ticket_past_due_is_breached(): void
    {
        $user = $this->userWithRole(UserRole::User);

        $ticket = Ticket::create([
            'title' => 'En retard',
            'description' => 'desc',
            'type' => 'incident',
            'priority' => TicketPriority::High,
            'status' => TicketStatus::InProgress,
            'requester_id' => $user->id,
            'due_at' => now()->subHour(),
        ]);

        $this->assertSame('breached', $ticket->fresh()->sla_status);
        $this->assertSame(1, Ticket::query()->slaBreached()->count());
    }

    public function test_resolved_before_due_is_met(): void
    {
        $user = $this->userWithRole(UserRole::User);

        $ticket = Ticket::create([
            'title' => 'Résolu à temps',
            'description' => 'desc',
            'type' => 'incident',
            'priority' => TicketPriority::Medium,
            'status' => TicketStatus::Resolved,
            'requester_id' => $user->id,
            'due_at' => now()->addDay(),
            'resolved_at' => now(),
        ]);

        $this->assertSame('met', $ticket->fresh()->sla_status);
    }

    public function test_technician_can_filter_breached_tickets(): void
    {
        $technician = $this->userWithRole(UserRole::Technician);
        $user = $this->userWithRole(UserRole::User);

        Ticket::create([
            'title' => 'Ticket dépassé',
            'description' => 'desc',
            'type' => 'incident',
            'priority' => TicketPriority::Urgent,
            'status' => TicketStatus::New,
            'requester_id' => $user->id,
            'due_at' => now()->subHours(2),
        ]);

        Ticket::create([
            'title' => 'Ticket ok',
            'description' => 'desc',
            'type' => 'incident',
            'priority' => TicketPriority::Low,
            'status' => TicketStatus::New,
            'requester_id' => $user->id,
            'due_at' => now()->addDays(2),
        ]);

        $this->actingAs($technician)
            ->get('/tickets?sla=breached')
            ->assertOk()
            ->assertSee('Ticket dépassé')
            ->assertDontSee('Ticket ok');
    }
}
