<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\Contract;
use App\Models\Entity;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierContractTest extends TestCase
{
    use RefreshDatabase;

    private function tech(): User
    {
        return User::factory()->create([
            'role' => UserRole::Technician,
            'entity_id' => Entity::firstOrCreate(['name' => 'Test'])->id,
        ]);
    }

    public function test_technician_can_create_supplier_and_contract(): void
    {
        $tech = $this->tech();

        $this->actingAs($tech)->post('/suppliers', [
            'name' => 'Fournisseur Test',
            'email' => 'contact@test.local',
            'is_active' => true,
        ])->assertRedirect();

        $supplier = Supplier::where('name', 'Fournisseur Test')->first();
        $this->assertNotNull($supplier);

        $this->actingAs($tech)->post('/contracts', [
            'title' => 'Contrat maintenance',
            'supplier_id' => $supplier->id,
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->addYear()->toDateString(),
            'amount' => 1000,
            'currency' => 'MAD',
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('contracts', ['title' => 'Contrat maintenance']);
    }

    public function test_can_link_asset_to_contract(): void
    {
        $tech = $this->tech();
        $supplier = Supplier::create(['name' => 'S', 'is_active' => true]);
        $contract = Contract::create([
            'title' => 'C',
            'supplier_id' => $supplier->id,
            'is_active' => true,
        ]);
        $asset = Asset::create([
            'name' => 'PC',
            'type' => 'computer',
            'status' => 'in_use',
            'inventory_number' => 'INV-CTR-1',
        ]);

        $this->actingAs($tech)
            ->post(route('contracts.assets.attach', $contract), ['asset_id' => $asset->id])
            ->assertRedirect();

        $this->assertTrue($contract->assets()->where('assets.id', $asset->id)->exists());
    }
}
