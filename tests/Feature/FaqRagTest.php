<?php

namespace Tests\Feature;

use App\Enums\FaqCategory;
use App\Enums\UserRole;
use App\Models\Entity;
use App\Models\FaqArticle;
use App\Models\User;
use App\Services\Ai\FaqRagService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqRagTest extends TestCase
{
    use RefreshDatabase;

    public function test_keyword_fallback_finds_published_faq(): void
    {
        $author = User::factory()->create([
            'role' => UserRole::Technician,
            'entity_id' => Entity::firstOrCreate(['name' => 'Test'])->id,
        ]);

        FaqArticle::create([
            'title' => 'VPN ne se connecte plus',
            'body' => 'Redémarrer le service VPN puis se reconnecter.',
            'category' => FaqCategory::Network,
            'is_published' => true,
            'author_id' => $author->id,
        ]);

        FaqArticle::create([
            'title' => 'Changer son mot de passe',
            'body' => 'Procédure compte AD.',
            'category' => FaqCategory::Account,
            'is_published' => true,
            'author_id' => $author->id,
        ]);

        // RAG embeddings désactivés en phpunit → fallback mots-clés
        $results = app(FaqRagService::class)->search('Mon VPN refuse la connexion');

        $this->assertNotEmpty($results);
        $this->assertSame('VPN ne se connecte plus', $results[0]['title']);
    }
}
