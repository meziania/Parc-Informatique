<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Entity;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketResolutionAssistTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(UserRole $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'entity_id' => Entity::firstOrCreate(['name' => 'Test'])->id,
        ]);
    }

    public function test_simple_user_cannot_use_resolution_assist(): void
    {
        $user = $this->userWithRole(UserRole::User);
        $ticket = Ticket::create([
            'title' => 'Écran bleu',
            'description' => 'BSOD depuis ce matin',
            'type' => 'incident',
            'priority' => TicketPriority::Urgent,
            'requester_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->postJson(route('ai.ticket-resolution-assist', $ticket))
            ->assertForbidden();
    }

    public function test_technician_gets_resolution_assist(): void
    {
        config(['ai.enabled' => true, 'ai.api_key' => null]);

        $technician = $this->userWithRole(UserRole::Technician);
        $user = $this->userWithRole(UserRole::User);

        $ticket = Ticket::create([
            'title' => 'Écran bleu critique',
            'description' => 'Mon PC affiche un écran bleu, CRITICAL_PROCESS_DIED',
            'type' => 'incident',
            'priority' => TicketPriority::Urgent,
            'status' => TicketStatus::InProgress,
            'requester_id' => $user->id,
            'assignee_id' => $technician->id,
        ]);

        $this->actingAs($technician)
            ->postJson(route('ai.ticket-resolution-assist', $ticket))
            ->assertOk()
            ->assertJsonPath('llm_enabled', false)
            ->assertJsonStructure([
                'assist' => [
                    'summary',
                    'checklist',
                    'draft_solution',
                    'similar_tickets',
                    'faq_suggestions',
                    'provider',
                ],
            ]);
    }
}
