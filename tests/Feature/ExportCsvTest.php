<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\Entity;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportCsvTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(UserRole $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'entity_id' => Entity::firstOrCreate(['name' => 'Test'])->id,
        ]);
    }

    public function test_technician_can_export_assets_csv(): void
    {
        $technician = $this->userWithRole(UserRole::Technician);

        Asset::create([
            'name' => 'PC Export',
            'type' => 'computer',
            'status' => 'in_stock',
            'inventory_number' => 'INV-CSV-1',
        ]);

        $response = $this->actingAs($technician)->get(route('assets.export'));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('INV-CSV-1', $response->streamedContent());
        $this->assertStringContainsString('N° inventaire', $response->streamedContent());
    }

    public function test_user_export_assets_only_includes_own(): void
    {
        $user = $this->userWithRole(UserRole::User);
        $other = $this->userWithRole(UserRole::User);

        Asset::create([
            'name' => 'Mon PC',
            'type' => 'computer',
            'status' => 'in_use',
            'inventory_number' => 'INV-MINE',
            'user_id' => $user->id,
        ]);
        Asset::create([
            'name' => 'PC Autre',
            'type' => 'computer',
            'status' => 'in_use',
            'inventory_number' => 'INV-OTHER',
            'user_id' => $other->id,
        ]);

        $content = $this->actingAs($user)->get(route('assets.export'))->streamedContent();

        $this->assertStringContainsString('INV-MINE', $content);
        $this->assertStringNotContainsString('INV-OTHER', $content);
    }

    public function test_user_can_export_own_tickets_csv(): void
    {
        $user = $this->userWithRole(UserRole::User);
        $other = $this->userWithRole(UserRole::User);

        Ticket::create([
            'title' => 'Mon ticket export',
            'description' => 'Desc',
            'type' => 'incident',
            'priority' => 'medium',
            'requester_id' => $user->id,
        ]);
        Ticket::create([
            'title' => 'Ticket collegue',
            'description' => 'Desc',
            'type' => 'incident',
            'priority' => 'medium',
            'requester_id' => $other->id,
        ]);

        $content = $this->actingAs($user)->get(route('tickets.export'))->streamedContent();

        $this->assertStringContainsString('Mon ticket export', $content);
        $this->assertStringNotContainsString('Ticket collegue', $content);
        $this->assertStringContainsString('Numéro', $content);
    }

    public function test_guest_cannot_export(): void
    {
        $this->get(route('assets.export'))->assertRedirect('/login');
        $this->get(route('tickets.export'))->assertRedirect('/login');
    }
}
