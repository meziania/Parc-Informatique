<?php

namespace Tests\Feature;

use App\Enums\ConsumableCategory;
use App\Enums\UserRole;
use App\Models\Consumable;
use App\Models\Entity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsumableTest extends TestCase
{
    use RefreshDatabase;

    public function test_technician_can_adjust_stock(): void
    {
        $tech = User::factory()->create([
            'role' => UserRole::Technician,
            'entity_id' => Entity::firstOrCreate(['name' => 'Test'])->id,
        ]);

        $item = Consumable::create([
            'name' => 'Toner',
            'category' => ConsumableCategory::Cartridge,
            'quantity' => 5,
            'min_quantity' => 2,
            'unit' => 'unité',
        ]);

        $this->actingAs($tech)
            ->post(route('consumables.adjust', $item), ['delta' => -2])
            ->assertRedirect();

        $this->assertEquals(3, $item->fresh()->quantity);
    }
}
