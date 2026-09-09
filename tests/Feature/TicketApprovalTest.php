<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Enums\UserRole;
use App\Models\Entity;
use App\Models\ServiceCatalogItem;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_item_requiring_approval_sets_pending(): void
    {
        $entity = Entity::firstOrCreate(['name' => 'Test']);
        $user = User::factory()->create([
            'role' => UserRole::User,
            'entity_id' => $entity->id,
        ]);

        $item = ServiceCatalogItem::create([
            'code' => 'POSTE-T',
            'name' => 'Demande PC',
            'type' => TicketType::Request,
            'default_priority' => TicketPriority::Medium,
            'sla_hours' => 48,
            'is_active' => true,
            'requires_approval' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($user)->post('/tickets', [
            'title' => 'Nouveau portable',
            'description' => 'Pour le commercial',
            'type' => 'request',
            'priority' => 'medium',
            'service_catalog_item_id' => $item->id,
        ])->assertRedirect();

        $ticket = Ticket::first();
        $this->assertEquals(ApprovalStatus::Pending, $ticket->approval_status);
    }

    public function test_cannot_assign_while_pending_approval(): void
    {
        $entity = Entity::firstOrCreate(['name' => 'Test']);
        $tech = User::factory()->create([
            'role' => UserRole::Technician,
            'entity_id' => $entity->id,
        ]);
        $user = User::factory()->create([
            'role' => UserRole::User,
            'entity_id' => $entity->id,
        ]);

        $ticket = Ticket::create([
            'title' => 'Demande VPN',
            'description' => 'Accès',
            'type' => TicketType::Request,
            'priority' => TicketPriority::High,
            'status' => TicketStatus::New,
            'requester_id' => $user->id,
            'approval_status' => ApprovalStatus::Pending,
        ]);

        $this->actingAs($tech)
            ->post(route('tickets.assign', $ticket), ['assignee_id' => $tech->id])
            ->assertStatus(422);
    }

    public function test_technician_can_approve_then_assign(): void
    {
        $entity = Entity::firstOrCreate(['name' => 'Test']);
        $tech = User::factory()->create([
            'role' => UserRole::Technician,
            'entity_id' => $entity->id,
        ]);
        $user = User::factory()->create([
            'role' => UserRole::User,
            'entity_id' => $entity->id,
        ]);

        $ticket = Ticket::create([
            'title' => 'Demande VPN',
            'description' => 'Accès',
            'type' => TicketType::Request,
            'priority' => TicketPriority::High,
            'status' => TicketStatus::New,
            'requester_id' => $user->id,
            'approval_status' => ApprovalStatus::Pending,
        ]);

        $this->actingAs($tech)
            ->post(route('tickets.approve', $ticket))
            ->assertRedirect();

        $this->assertEquals(ApprovalStatus::Approved, $ticket->fresh()->approval_status);

        $this->actingAs($tech)
            ->post(route('tickets.assign', $ticket), ['assignee_id' => $tech->id])
            ->assertRedirect();

        $this->assertEquals(TicketStatus::Assigned, $ticket->fresh()->status);
    }
}
