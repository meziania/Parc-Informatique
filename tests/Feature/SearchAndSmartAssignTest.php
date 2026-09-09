<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\Entity;
use App\Models\FaqArticle;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\SatisfactionReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SearchAndSmartAssignTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(UserRole $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'entity_id' => Entity::firstOrCreate(['name' => 'Test'])->id,
        ]);
    }

    public function test_global_search_returns_scoped_results(): void
    {
        $user = $this->userWithRole(UserRole::User);
        $other = $this->userWithRole(UserRole::User);
        $tech = $this->userWithRole(UserRole::Technician);

        Ticket::create([
            'title' => 'VPN entreprise HS',
            'description' => 'desc',
            'type' => 'incident',
            'priority' => 'medium',
            'requester_id' => $user->id,
        ]);

        Ticket::create([
            'title' => 'VPN autre utilisateur',
            'description' => 'desc',
            'type' => 'incident',
            'priority' => 'medium',
            'requester_id' => $other->id,
        ]);

        Asset::create([
            'name' => 'Laptop VPN test',
            'type' => 'computer',
            'status' => AssetStatus::InUse,
            'inventory_number' => 'INV-VPN-1',
            'user_id' => $user->id,
        ]);

        FaqArticle::create([
            'title' => 'Configurer le VPN',
            'body' => 'Étapes de connexion VPN.',
            'category' => 'network',
            'is_published' => true,
            'author_id' => $tech->id,
        ]);

        $this->actingAs($user)
            ->getJson(route('search', ['q' => 'VPN']))
            ->assertOk()
            ->assertJsonCount(1, 'tickets')
            ->assertJsonPath('tickets.0.title', 'VPN entreprise HS')
            ->assertJsonCount(1, 'assets')
            ->assertJsonCount(1, 'faq');
    }

    public function test_ticket_show_suggests_least_loaded_technician(): void
    {
        $user = $this->userWithRole(UserRole::User);
        $busy = User::factory()->create([
            'name' => 'Busy Tech',
            'role' => UserRole::Technician,
            'entity_id' => Entity::firstOrCreate(['name' => 'Test'])->id,
        ]);
        $free = User::factory()->create([
            'name' => 'AAA Free Tech',
            'role' => UserRole::Technician,
            'entity_id' => Entity::firstOrCreate(['name' => 'Test'])->id,
        ]);
        $viewer = User::factory()->create([
            'name' => 'ZZZ Admin',
            'role' => UserRole::Admin,
            'entity_id' => Entity::firstOrCreate(['name' => 'Test'])->id,
        ]);

        Ticket::create([
            'title' => 'Charge busy',
            'description' => 'desc',
            'type' => 'incident',
            'priority' => 'medium',
            'status' => TicketStatus::InProgress,
            'requester_id' => $user->id,
            'assignee_id' => $busy->id,
        ]);

        $ticket = Ticket::create([
            'title' => 'À assigner intelligemment',
            'description' => 'desc',
            'type' => 'incident',
            'priority' => 'medium',
            'requester_id' => $user->id,
        ]);

        $this->actingAs($viewer)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Tickets/Show')
                ->where('suggested_assignee.id', $free->id)
                ->where('suggested_assignee.name', $free->name)
            );
    }

    public function test_satisfaction_reminder_command_notifies_requester_once(): void
    {
        Notification::fake();

        $user = $this->userWithRole(UserRole::User);

        $ticket = Ticket::create([
            'title' => 'À noter',
            'description' => 'desc',
            'type' => 'incident',
            'priority' => 'medium',
            'status' => TicketStatus::Resolved,
            'requester_id' => $user->id,
            'resolved_at' => now()->subHours(50),
        ]);

        Artisan::call('tickets:remind-satisfaction', ['--hours' => 48]);

        Notification::assertSentTo($user, SatisfactionReminderNotification::class);
        $this->assertNotNull($ticket->fresh()->satisfaction_reminded_at);

        Notification::fake();
        Artisan::call('tickets:remind-satisfaction', ['--hours' => 48]);
        Notification::assertNothingSent();
    }
}
