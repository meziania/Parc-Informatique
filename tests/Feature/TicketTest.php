<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\Entity;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(UserRole $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'entity_id' => Entity::firstOrCreate(['name' => 'Test'])->id,
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function makeTicket(User $requester, array $overrides = []): Ticket
    {
        return Ticket::create([
            'title' => 'Ticket de test',
            'description' => 'Description du ticket de test',
            'type' => 'incident',
            'priority' => 'medium',
            'requester_id' => $requester->id,
            ...$overrides,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/tickets')->assertRedirect('/login');
    }

    public function test_user_sees_only_his_own_tickets(): void
    {
        $user = $this->userWithRole(UserRole::User);
        $other = $this->userWithRole(UserRole::User);
        $this->makeTicket($user, ['title' => 'Mon ticket perso']);
        $this->makeTicket($other, ['title' => 'Ticket dun collegue']);

        $this->actingAs($user)->get('/tickets')
            ->assertOk()
            ->assertSee('Mon ticket perso')
            ->assertDontSee('Ticket dun collegue');
    }

    public function test_technician_sees_all_tickets(): void
    {
        $technician = $this->userWithRole(UserRole::Technician);
        $user = $this->userWithRole(UserRole::User);
        $this->makeTicket($user, ['title' => 'Ticket visible par IT']);

        $this->actingAs($technician)->get('/tickets')
            ->assertOk()
            ->assertSee('Ticket visible par IT');
    }

    public function test_user_can_create_ticket_with_generated_number(): void
    {
        $user = $this->userWithRole(UserRole::User);

        $response = $this->actingAs($user)->post('/tickets', [
            'title' => 'Mon imprimante fume',
            'description' => 'Elle fait un drôle de bruit aussi.',
            'type' => 'incident',
            'priority' => 'high',
        ]);

        $ticket = Ticket::where('title', 'Mon imprimante fume')->first();
        $this->assertNotNull($ticket);
        $this->assertSame('new', $ticket->status->value);
        $this->assertMatchesRegularExpression('/^T-\d{6}$/', $ticket->number);
        $response->assertRedirect(route('tickets.show', $ticket));
    }

    public function test_user_cannot_link_ticket_to_someone_elses_asset(): void
    {
        $user = $this->userWithRole(UserRole::User);
        $other = $this->userWithRole(UserRole::User);
        $asset = Asset::create([
            'name' => 'PC autrui',
            'type' => 'computer',
            'status' => 'in_use',
            'inventory_number' => 'INV-AUTRUI',
            'user_id' => $other->id,
        ]);

        $this->actingAs($user)->post('/tickets', [
            'title' => 'Ticket sur PC autrui',
            'description' => 'Tentative.',
            'type' => 'incident',
            'priority' => 'medium',
            'asset_id' => $asset->id,
        ])->assertSessionHasErrors('asset_id');
    }

    public function test_simple_user_cannot_assign_or_resolve(): void
    {
        $user = $this->userWithRole(UserRole::User);
        $ticket = $this->makeTicket($user);

        $this->actingAs($user)->post("/tickets/{$ticket->id}/assign", [
            'assignee_id' => $user->id,
        ])->assertForbidden();

        $ticket->update(['status' => 'in_progress']);
        $this->actingAs($user)->post("/tickets/{$ticket->id}/resolve", [
            'solution' => 'Solution.',
        ])->assertForbidden();
    }

    public function test_full_lifecycle_assign_start_resolve_close(): void
    {
        $technician = $this->userWithRole(UserRole::Technician);
        $user = $this->userWithRole(UserRole::User);
        $ticket = $this->makeTicket($user);

        $this->actingAs($technician)->post("/tickets/{$ticket->id}/assign", [
            'assignee_id' => $technician->id,
        ])->assertRedirect();
        $this->assertSame('assigned', $ticket->fresh()->status->value);

        $this->actingAs($technician)->post("/tickets/{$ticket->id}/start")
            ->assertRedirect();
        $this->assertSame('in_progress', $ticket->fresh()->status->value);

        $this->actingAs($technician)->post("/tickets/{$ticket->id}/resolve", [
            'solution' => 'Carte graphique rebranchée.',
        ])->assertRedirect();
        $this->assertSame('resolved', $ticket->fresh()->status->value);
        $this->assertNotNull($ticket->fresh()->resolved_at);

        $this->actingAs($user)->post("/tickets/{$ticket->id}/close")
            ->assertRedirect();
        $this->assertSame('closed', $ticket->fresh()->status->value);
        $this->assertNotNull($ticket->fresh()->closed_at);
    }

    public function test_requester_can_reopen_a_resolved_ticket(): void
    {
        $technician = $this->userWithRole(UserRole::Technician);
        $user = $this->userWithRole(UserRole::User);
        $ticket = $this->makeTicket($user, [
            'status' => 'resolved',
            'assignee_id' => $technician->id,
            'resolved_at' => now(),
        ]);

        $this->actingAs($user)->post("/tickets/{$ticket->id}/reopen")
            ->assertRedirect();
        $this->assertSame('in_progress', $ticket->fresh()->status->value);
    }

    public function test_user_cannot_view_someone_elses_ticket(): void
    {
        $user = $this->userWithRole(UserRole::User);
        $other = $this->userWithRole(UserRole::User);
        $ticket = $this->makeTicket($other);

        $this->actingAs($user)->get("/tickets/{$ticket->id}")->assertForbidden();
    }

    public function test_participants_can_comment(): void
    {
        $user = $this->userWithRole(UserRole::User);
        $ticket = $this->makeTicket($user);

        $this->actingAs($user)->post("/tickets/{$ticket->id}/comments", [
            'body' => 'Une précision supplémentaire.',
        ])->assertRedirect();

        $this->assertDatabaseHas('ticket_comments', [
            'ticket_id' => $ticket->id,
            'body' => 'Une précision supplémentaire.',
        ]);
    }
}
