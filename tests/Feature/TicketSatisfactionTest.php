<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Entity;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketSatisfactionTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(UserRole $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'entity_id' => Entity::firstOrCreate(['name' => 'Test'])->id,
        ]);
    }

    private function resolvedTicket(User $requester): Ticket
    {
        return Ticket::create([
            'title' => 'Imprimante bloquée',
            'description' => 'Plus rien ne s’imprime.',
            'type' => 'incident',
            'priority' => 'medium',
            'status' => 'resolved',
            'requester_id' => $requester->id,
            'solution' => 'Redémarrage du spooler.',
            'resolved_at' => now(),
        ]);
    }

    public function test_requester_can_rate_resolved_ticket(): void
    {
        $user = $this->userWithRole(UserRole::User);
        $ticket = $this->resolvedTicket($user);

        $this->actingAs($user)
            ->post(route('tickets.satisfaction', $ticket), [
                'satisfaction_rating' => 5,
                'satisfaction_comment' => 'Intervention rapide, merci.',
            ])
            ->assertRedirect();

        $ticket->refresh();
        $this->assertSame(5, $ticket->satisfaction_rating);
        $this->assertSame('Intervention rapide, merci.', $ticket->satisfaction_comment);
        $this->assertNotNull($ticket->satisfaction_rated_at);
    }

    public function test_technician_cannot_rate_for_requester(): void
    {
        $user = $this->userWithRole(UserRole::User);
        $tech = $this->userWithRole(UserRole::Technician);
        $ticket = $this->resolvedTicket($user);

        $this->actingAs($tech)
            ->post(route('tickets.satisfaction', $ticket), [
                'satisfaction_rating' => 4,
            ])
            ->assertForbidden();
    }

    public function test_cannot_rate_twice(): void
    {
        $user = $this->userWithRole(UserRole::User);
        $ticket = $this->resolvedTicket($user);
        $ticket->update([
            'satisfaction_rating' => 3,
            'satisfaction_rated_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('tickets.satisfaction', $ticket), [
                'satisfaction_rating' => 5,
            ])
            ->assertStatus(422);
    }

    public function test_reopen_clears_satisfaction(): void
    {
        $user = $this->userWithRole(UserRole::User);
        $ticket = $this->resolvedTicket($user);
        $ticket->update([
            'status' => 'closed',
            'closed_at' => now(),
            'satisfaction_rating' => 4,
            'satisfaction_comment' => 'OK',
            'satisfaction_rated_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('tickets.reopen', $ticket))
            ->assertRedirect();

        $ticket->refresh();
        $this->assertNull($ticket->satisfaction_rating);
        $this->assertNull($ticket->satisfaction_comment);
        $this->assertNull($ticket->satisfaction_rated_at);
        $this->assertSame('in_progress', $ticket->status->value);
    }
}
