<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Entity;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketAssignedNotification;
use App\Notifications\TicketCommentedNotification;
use App\Notifications\TicketCreatedNotification;
use App\Notifications\TicketResolvedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(UserRole $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'entity_id' => Entity::firstOrCreate(['name' => 'Test'])->id,
        ]);
    }

    public function test_creating_a_ticket_notifies_technicians(): void
    {
        Notification::fake();

        $user = $this->userWithRole(UserRole::User);
        $technician = $this->userWithRole(UserRole::Technician);
        $admin = $this->userWithRole(UserRole::Admin);

        $this->actingAs($user)->post('/tickets', [
            'title' => 'Écran noir',
            'description' => 'Plus rien ne s\'affiche.',
            'type' => 'incident',
            'priority' => 'high',
        ])->assertRedirect();

        Notification::assertSentTo($technician, TicketCreatedNotification::class);
        Notification::assertSentTo($admin, TicketCreatedNotification::class);
        Notification::assertNotSentTo($user, TicketCreatedNotification::class);
    }

    public function test_assigning_a_ticket_notifies_assignee(): void
    {
        Notification::fake();

        $user = $this->userWithRole(UserRole::User);
        $assigner = $this->userWithRole(UserRole::Admin);
        $assignee = $this->userWithRole(UserRole::Technician);

        $ticket = Ticket::create([
            'title' => 'À assigner',
            'description' => 'desc',
            'type' => 'incident',
            'priority' => 'medium',
            'requester_id' => $user->id,
        ]);

        $this->actingAs($assigner)->post("/tickets/{$ticket->id}/assign", [
            'assignee_id' => $assignee->id,
        ])->assertRedirect();

        Notification::assertSentTo($assignee, TicketAssignedNotification::class);
    }

    public function test_resolving_a_ticket_notifies_requester(): void
    {
        Notification::fake();

        $user = $this->userWithRole(UserRole::User);
        $technician = $this->userWithRole(UserRole::Technician);

        $ticket = Ticket::create([
            'title' => 'À résoudre',
            'description' => 'desc',
            'type' => 'incident',
            'priority' => 'medium',
            'status' => 'in_progress',
            'requester_id' => $user->id,
            'assignee_id' => $technician->id,
        ]);

        $this->actingAs($technician)->post("/tickets/{$ticket->id}/resolve", [
            'solution' => 'Redémarrage effectué.',
        ])->assertRedirect();

        Notification::assertSentTo($user, TicketResolvedNotification::class);
    }

    public function test_comment_notifies_the_other_party(): void
    {
        Notification::fake();

        $user = $this->userWithRole(UserRole::User);
        $technician = $this->userWithRole(UserRole::Technician);

        $ticket = Ticket::create([
            'title' => 'Avec commentaire',
            'description' => 'desc',
            'type' => 'incident',
            'priority' => 'medium',
            'requester_id' => $user->id,
            'assignee_id' => $technician->id,
        ]);

        $this->actingAs($user)->post("/tickets/{$ticket->id}/comments", [
            'body' => 'Toujours bloqué de mon côté.',
        ])->assertRedirect();

        Notification::assertSentTo($technician, TicketCommentedNotification::class);
        Notification::assertNotSentTo($user, TicketCommentedNotification::class);
    }

    public function test_user_can_list_and_mark_notifications_as_read(): void
    {
        $user = $this->userWithRole(UserRole::User);
        $ticket = Ticket::create([
            'title' => 'Notif UI',
            'description' => 'desc',
            'type' => 'incident',
            'priority' => 'medium',
            'requester_id' => $user->id,
        ]);

        $user->notify(new TicketResolvedNotification($ticket));

        $this->actingAs($user)
            ->get('/notifications')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Notifications/Index')
                ->has('items.data', 1)
            );

        $notificationId = $user->notifications()->first()->id;

        $this->actingAs($user)
            ->post("/notifications/{$notificationId}/read")
            ->assertRedirect();

        $this->assertNotNull($user->fresh()->notifications()->first()->read_at);
    }
}
