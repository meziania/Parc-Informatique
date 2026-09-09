<?php

namespace Tests\Feature;

use App\Enums\FaqCategory;
use App\Enums\UserRole;
use App\Models\Entity;
use App\Models\FaqArticle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FaqTest extends TestCase
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
    private function makeArticle(array $overrides = []): FaqArticle
    {
        return FaqArticle::create([
            'title' => 'Article de test',
            'body' => 'Contenu de l\'article de test.',
            'category' => FaqCategory::Other,
            'is_published' => true,
            ...$overrides,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/faq')->assertRedirect('/login');
    }

    public function test_user_sees_only_published_articles(): void
    {
        $user = $this->userWithRole(UserRole::User);
        $this->makeArticle(['title' => 'Article publie']);
        $this->makeArticle([
            'title' => 'Brouillon secret',
            'is_published' => false,
        ]);

        $this->actingAs($user)->get('/faq')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Faq/Index')
                ->has('articles.data', 1)
                ->where('articles.data.0.title', 'Article publie')
            );
    }

    public function test_technician_sees_drafts(): void
    {
        $technician = $this->userWithRole(UserRole::Technician);
        $this->makeArticle([
            'title' => 'Brouillon IT',
            'is_published' => false,
        ]);

        $this->actingAs($technician)->get('/faq')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Faq/Index')
                ->has('articles.data', 1)
                ->where('articles.data.0.title', 'Brouillon IT')
            );
    }

    public function test_user_cannot_manage_faq(): void
    {
        $user = $this->userWithRole(UserRole::User);

        $this->actingAs($user)->get('/faq-manage/create')->assertForbidden();
        $this->actingAs($user)->post('/faq', [
            'title' => 'Interdit',
            'body' => 'Non',
            'category' => 'other',
            'is_published' => true,
        ])->assertForbidden();
    }

    public function test_technician_can_create_article(): void
    {
        $technician = $this->userWithRole(UserRole::Technician);

        $response = $this->actingAs($technician)->post('/faq', [
            'title' => 'Nouvelle fiche',
            'body' => 'Voici la procédure.',
            'category' => 'network',
            'is_published' => true,
        ]);

        $article = FaqArticle::where('title', 'Nouvelle fiche')->first();
        $this->assertNotNull($article);
        $this->assertSame($technician->id, $article->author_id);
        $response->assertRedirect(route('faq.show', $article));
    }

    public function test_show_increments_views_and_hides_draft_from_user(): void
    {
        $user = $this->userWithRole(UserRole::User);
        $published = $this->makeArticle(['title' => 'Publié', 'views_count' => 0]);
        $draft = $this->makeArticle([
            'title' => 'Draft',
            'is_published' => false,
        ]);

        $this->actingAs($user)->get(route('faq.show', $published))->assertOk();
        $this->assertSame(1, $published->fresh()->views_count);

        $this->actingAs($user)->get(route('faq.show', $draft))->assertNotFound();
    }

    public function test_technician_can_update_and_delete(): void
    {
        $technician = $this->userWithRole(UserRole::Technician);
        $article = $this->makeArticle(['author_id' => $technician->id]);

        $this->actingAs($technician)->put(route('faq.update', $article), [
            'title' => 'Titre mis à jour',
            'body' => 'Nouveau contenu',
            'category' => 'hardware',
            'is_published' => false,
        ])->assertRedirect(route('faq.show', $article));

        $this->assertSame('Titre mis à jour', $article->fresh()->title);
        $this->assertFalse($article->fresh()->is_published);

        $this->actingAs($technician)
            ->delete(route('faq.destroy', $article))
            ->assertRedirect(route('faq.index'));

        $this->assertDatabaseMissing('faq_articles', ['id' => $article->id]);
    }
}
