<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Entity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiHealthTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(UserRole $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'entity_id' => Entity::firstOrCreate(['name' => 'Test'])->id,
        ]);
    }

    public function test_guest_cannot_fetch_ai_health(): void
    {
        $response = $this->getJson('/ai/health');

        $this->assertContains($response->status(), [401, 302]);
    }

    public function test_ai_health_endpoint_reports_ollama_ready(): void
    {
        config([
            'ai.enabled' => true,
            'ai.provider' => 'ollama',
            'ai.base_url' => 'http://127.0.0.1:11434/v1',
            'ai.model' => 'qwen2.5',
            'ai.embedding_model' => 'nomic-embed-text',
            'ai.rag.enabled' => true,
        ]);

        Http::fake([
            'http://127.0.0.1:11434/api/tags' => Http::response([
                'models' => [
                    ['name' => 'qwen2.5:latest'],
                    ['name' => 'nomic-embed-text:latest'],
                ],
            ], 200),
        ]);

        $user = $this->userWithRole(UserRole::User);

        $this->actingAs($user)
            ->getJson(route('ai.health', ['fresh' => 1]))
            ->assertOk()
            ->assertJsonPath('health.provider', 'ollama')
            ->assertJsonPath('health.reachable', true)
            ->assertJsonPath('health.mode', 'ollama')
            ->assertJsonPath('health.chat_model_ready', true)
            ->assertJsonPath('health.embedding_model_ready', true)
            ->assertJsonPath('llm_enabled', true);
    }

    public function test_ai_health_command_fails_when_ollama_unreachable(): void
    {
        config([
            'ai.enabled' => true,
            'ai.provider' => 'ollama',
            'ai.base_url' => 'http://127.0.0.1:11434/v1',
            'ai.model' => 'qwen2.5',
            'ai.embedding_model' => 'nomic-embed-text',
        ]);

        Http::fake([
            'http://127.0.0.1:11434/api/tags' => Http::response('down', 500),
        ]);

        $exit = Artisan::call('ai:health', ['--fresh' => true]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('injoignable', Artisan::output());
    }
}
