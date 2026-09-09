<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Entity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketAiSuggestionTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(UserRole $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'entity_id' => Entity::firstOrCreate(['name' => 'Test'])->id,
        ]);
    }

    public function test_guest_cannot_use_ai_suggestions(): void
    {
        $response = $this->postJson('/ai/ticket-suggestions', [
            'message' => 'Mon imprimante ne fonctionne plus depuis ce matin.',
        ]);

        $this->assertContains($response->status(), [401, 302]);
    }

    public function test_user_gets_heuristic_ticket_suggestion(): void
    {
        config([
            'ai.enabled' => true,
            'ai.provider' => 'openai',
            'ai.api_key' => null,
        ]);

        $user = $this->userWithRole(UserRole::User);

        $this->actingAs($user)
            ->postJson('/ai/ticket-suggestions', [
                'message' => 'Mon PC est en panne, écran noir, c\'est urgent je suis bloqué.',
            ])
            ->assertOk()
            ->assertJsonPath('suggestion.type', 'incident')
            ->assertJsonPath('suggestion.priority', 'urgent')
            ->assertJsonPath('llm_enabled', false)
            ->assertJsonStructure([
                'suggestion' => [
                    'title',
                    'description',
                    'type',
                    'priority',
                    'asset_id',
                    'rationale',
                    'faq_suggestions',
                    'provider',
                ],
            ]);
    }

    public function test_message_too_short_is_rejected(): void
    {
        $user = $this->userWithRole(UserRole::User);

        $this->actingAs($user)
            ->postJson('/ai/ticket-suggestions', [
                'message' => 'court',
            ])
            ->assertInvalid(['message']);
    }
}
