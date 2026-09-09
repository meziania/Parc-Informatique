<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\AssetEvent;
use App\Models\Entity;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AssetTest extends TestCase
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
    private function makeAsset(array $overrides = []): Asset
    {
        return Asset::create([
            'name' => 'PC Test',
            'type' => 'computer',
            'status' => 'in_stock',
            'inventory_number' => 'INV-'.fake()->unique()->numberBetween(1000, 9999),
            ...$overrides,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/assets')->assertRedirect('/login');
    }

    public function test_user_sees_only_his_own_assets(): void
    {
        $user = $this->userWithRole(UserRole::User);
        $this->makeAsset(['name' => 'PC de lutilisateur', 'user_id' => $user->id]);
        $this->makeAsset(['name' => 'PC de quelquun dautre']);

        $this->actingAs($user)->get('/assets')
            ->assertOk()
            ->assertSee('PC de lutilisateur')
            ->assertDontSee('PC de quelquun dautre');
    }

    public function test_technician_sees_all_assets(): void
    {
        $technician = $this->userWithRole(UserRole::Technician);
        $other = $this->userWithRole(UserRole::User);
        $this->makeAsset(['name' => 'PC Alpha', 'user_id' => $other->id]);
        $this->makeAsset(['name' => 'PC Beta']);

        $this->actingAs($technician)->get('/assets')
            ->assertOk()
            ->assertSee('PC Alpha')
            ->assertSee('PC Beta');
    }

    public function test_technician_can_create_asset(): void
    {
        $technician = $this->userWithRole(UserRole::Technician);

        $response = $this->actingAs($technician)->post('/assets', [
            'name' => 'Nouveau PC',
            'type' => 'computer',
            'status' => 'in_stock',
            'inventory_number' => 'INV-TEST-1',
        ]);

        $asset = Asset::where('inventory_number', 'INV-TEST-1')->first();
        $this->assertNotNull($asset);
        $response->assertRedirect(route('assets.show', $asset));
    }

    public function test_simple_user_cannot_manage_assets(): void
    {
        $user = $this->userWithRole(UserRole::User);

        $this->actingAs($user)->get('/assets/create')->assertForbidden();
        $this->actingAs($user)->post('/assets', [
            'name' => 'PC interdit',
            'type' => 'computer',
            'status' => 'in_stock',
            'inventory_number' => 'INV-TEST-2',
        ])->assertForbidden();
    }

    public function test_user_cannot_view_an_asset_that_is_not_his(): void
    {
        $user = $this->userWithRole(UserRole::User);
        $asset = $this->makeAsset();

        $this->actingAs($user)->get(route('assets.show', $asset))->assertForbidden();
    }

    public function test_creating_asset_records_history_event(): void
    {
        $technician = $this->userWithRole(UserRole::Technician);

        $this->actingAs($technician)->post('/assets', [
            'name' => 'PC Historique',
            'type' => 'computer',
            'status' => 'in_stock',
            'inventory_number' => 'INV-HIST-1',
        ])->assertRedirect();

        $asset = Asset::where('inventory_number', 'INV-HIST-1')->firstOrFail();

        $this->assertDatabaseHas('asset_events', [
            'asset_id' => $asset->id,
            'event_type' => 'created',
        ]);
    }

    public function test_updating_status_and_assignment_records_history(): void
    {
        $technician = $this->userWithRole(UserRole::Technician);
        $assignee = $this->userWithRole(UserRole::User);
        $asset = $this->makeAsset(['status' => 'in_stock']);

        $this->actingAs($technician)->put(route('assets.update', $asset), [
            'name' => $asset->name,
            'type' => 'computer',
            'status' => 'in_use',
            'inventory_number' => $asset->inventory_number,
            'user_id' => $assignee->id,
        ])->assertRedirect();

        $this->assertTrue(
            AssetEvent::query()
                ->where('asset_id', $asset->id)
                ->where('event_type', 'status_changed')
                ->exists()
        );
        $this->assertTrue(
            AssetEvent::query()
                ->where('asset_id', $asset->id)
                ->where('event_type', 'assigned')
                ->exists()
        );
    }

    public function test_asset_show_includes_timeline_and_ticket_events(): void
    {
        $technician = $this->userWithRole(UserRole::Technician);
        $user = $this->userWithRole(UserRole::User);
        $asset = $this->makeAsset(['user_id' => $user->id]);

        $this->actingAs($user)->post('/tickets', [
            'title' => 'Écran noir',
            'description' => 'Plus d’image au démarrage.',
            'type' => 'incident',
            'priority' => 'medium',
            'asset_id' => $asset->id,
        ])->assertRedirect();

        $ticket = Ticket::where('title', 'Écran noir')->firstOrFail();

        $this->assertDatabaseHas('asset_events', [
            'asset_id' => $asset->id,
            'event_type' => 'ticket_opened',
            'related_id' => $ticket->id,
        ]);

        $this->actingAs($technician)
            ->get(route('assets.show', $asset))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Assets/Show')
                ->has('events')
                ->where('events.0.event_type', 'ticket_opened')
            );
    }
}
