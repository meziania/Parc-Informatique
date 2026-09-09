<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketType;
use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\Entity;
use App\Models\ServiceCatalogItem;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\ServiceCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CatalogReportMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(UserRole $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'entity_id' => Entity::firstOrCreate(['name' => 'Test'])->id,
        ]);
    }

    public function test_ticket_create_lists_service_catalog(): void
    {
        $this->seed(ServiceCatalogSeeder::class);
        $user = $this->userWithRole(UserRole::User);

        $this->actingAs($user)
            ->get(route('tickets.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Tickets/Create')
                ->has('catalog', 6)
            );
    }

    public function test_catalog_item_applies_type_priority_and_custom_sla(): void
    {
        $this->seed(ServiceCatalogSeeder::class);
        $user = $this->userWithRole(UserRole::User);
        $vpn = ServiceCatalogItem::query()->where('code', 'VPN')->firstOrFail();

        $this->actingAs($user)
            ->post(route('tickets.store'), [
                'title' => 'VPN HS ce matin',
                'description' => 'Impossible de se connecter au VPN entreprise.',
                'type' => TicketType::Request->value,
                'priority' => TicketPriority::Low->value,
                'service_catalog_item_id' => $vpn->id,
            ])
            ->assertRedirect();

        $ticket = Ticket::query()->latest('id')->first();
        $this->assertNotNull($ticket);
        $this->assertSame(TicketType::Incident, $ticket->type);
        $this->assertSame(TicketPriority::High, $ticket->priority);
        $this->assertSame($vpn->id, $ticket->service_catalog_item_id);
        $this->assertTrue(
            $ticket->due_at?->between(now()->addHours(7), now()->addHours(9)) ?? false
        );
    }

    public function test_technician_can_download_weekly_pdf_report(): void
    {
        $tech = $this->userWithRole(UserRole::Technician);

        $this->actingAs($tech)
            ->get(route('reports.weekly'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_dashboard_shows_maintenance_stats(): void
    {
        $tech = $this->userWithRole(UserRole::Technician);

        Asset::create([
            'name' => 'PC garantie expirée',
            'type' => 'computer',
            'status' => AssetStatus::InUse,
            'inventory_number' => 'INV-MAINT-1',
            'warranty_end' => now()->subDays(10)->toDateString(),
            'next_maintenance_at' => now()->subDays(2)->toDateString(),
        ]);

        $this->actingAs($tech)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('stats.warranty_expired', 1)
                ->where('stats.maintenance_due', 1)
                ->has('maintenance_assets', 1)
            );
    }
}
