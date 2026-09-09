<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Entity;
use App\Models\FaqArticle;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketFaqDraftTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(UserRole $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'entity_id' => Entity::firstOrCreate(['name' => 'Test'])->id,
        ]);
    }

    public function test_technician_can_generate_faq_draft_from_resolved_ticket(): void
    {
        config(['ai.enabled' => true, 'ai.api_key' => null]);

        $technician = $this->userWithRole(UserRole::Technician);
        $user = $this->userWithRole(UserRole::User);

        $ticket = Ticket::create([
            'title' => 'Écran bleu au démarrage',
            'description' => 'BSOD depuis ce matin',
            'type' => 'incident',
            'priority' => TicketPriority::High,
            'status' => TicketStatus::Resolved,
            'requester_id' => $user->id,
            'assignee_id' => $technician->id,
            'solution' => 'Réinstallation pilote GPU + sfc /scannow.',
            'resolved_at' => now(),
        ]);

        $this->actingAs($technician)
            ->postJson(route('ai.ticket-faq-draft', $ticket))
            ->assertOk()
            ->assertJsonStructure([
                'draft' => ['title', 'body', 'category', 'rationale', 'provider'],
            ]);
    }

    public function test_technician_can_create_faq_from_draft(): void
    {
        $technician = $this->userWithRole(UserRole::Technician);
        $user = $this->userWithRole(UserRole::User);

        $ticket = Ticket::create([
            'title' => 'Imprimante bloquée',
            'description' => 'File d’attente saturée',
            'type' => 'incident',
            'priority' => TicketPriority::Medium,
            'status' => TicketStatus::Resolved,
            'requester_id' => $user->id,
            'solution' => 'Purge de la file d’attente et réinstall du pilote.',
            'resolved_at' => now(),
        ]);

        $this->actingAs($technician)
            ->post(route('tickets.faq.store', $ticket), [
                'title' => 'Que faire si l’imprimante ne répond plus ?',
                'body' => "## Résolution\n\nPurger la file d’attente.",
                'category' => 'hardware',
                'is_published' => false,
            ])
            ->assertRedirect();

        $article = FaqArticle::where('title', 'Que faire si l’imprimante ne répond plus ?')->first();
        $this->assertNotNull($article);
        $this->assertFalse($article->is_published);
        $this->assertSame($technician->id, $article->author_id);
    }
}
