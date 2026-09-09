<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Enums\UserRole;
use App\Models\Entity;
use App\Models\Ticket;
use App\Models\TicketTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTaskTest extends TestCase
{
    use RefreshDatabase;

    private function tech(): User
    {
        return User::factory()->create([
            'role' => UserRole::Technician,
            'entity_id' => Entity::firstOrCreate(['name' => 'Test'])->id,
        ]);
    }

    private function makeTicket(User $requester): Ticket
    {
        return Ticket::create([
            'title' => 'Ticket tâches',
            'description' => 'Description',
            'type' => TicketType::Incident,
            'priority' => TicketPriority::Medium,
            'status' => TicketStatus::InProgress,
            'requester_id' => $requester->id,
        ]);
    }

    public function test_technician_can_add_and_toggle_task(): void
    {
        $tech = $this->tech();
        $ticket = $this->makeTicket($tech);

        $this->actingAs($tech)
            ->post(route('tickets.tasks.store', $ticket), [
                'title' => 'Diagnostiquer le poste',
            ])
            ->assertRedirect();

        $task = TicketTask::first();
        $this->assertNotNull($task);
        $this->assertFalse($task->is_done);

        $this->actingAs($tech)
            ->post(route('tickets.tasks.toggle', [$ticket, $task]))
            ->assertRedirect();

        $this->assertTrue($task->fresh()->is_done);
        $this->assertNotNull($task->fresh()->completed_at);
    }

    public function test_user_cannot_manage_tasks(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::User,
            'entity_id' => Entity::firstOrCreate(['name' => 'Test'])->id,
        ]);
        $ticket = $this->makeTicket($user);

        $this->actingAs($user)
            ->post(route('tickets.tasks.store', $ticket), [
                'title' => 'Interdit',
            ])
            ->assertForbidden();
    }
}
