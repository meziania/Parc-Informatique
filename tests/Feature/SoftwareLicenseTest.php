<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\Entity;
use App\Models\SoftwareLicense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoftwareLicenseTest extends TestCase
{
    use RefreshDatabase;

    private function tech(): User
    {
        return User::factory()->create([
            'role' => UserRole::Technician,
            'entity_id' => Entity::firstOrCreate(['name' => 'Test'])->id,
        ]);
    }

    private function makeLicense(array $overrides = []): SoftwareLicense
    {
        return SoftwareLicense::create([
            'name' => 'Licence Test',
            'vendor' => 'Éditeur',
            'seats' => 2,
            'is_active' => true,
            ...$overrides,
        ]);
    }

    private function makeAsset(): Asset
    {
        return Asset::create([
            'name' => 'PC Licence',
            'type' => 'computer',
            'status' => 'in_use',
            'inventory_number' => 'INV-LIC-'.fake()->unique()->numberBetween(1, 9999),
        ]);
    }

    public function test_technician_can_create_license(): void
    {
        $tech = $this->tech();

        $this->actingAs($tech)->post('/licenses', [
            'name' => 'Office 365',
            'vendor' => 'Microsoft',
            'seats' => 10,
            'expiry_date' => now()->addMonth()->toDateString(),
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('software_licenses', [
            'name' => 'Office 365',
            'seats' => 10,
        ]);
    }

    public function test_can_attach_asset_until_seats_exhausted(): void
    {
        $tech = $this->tech();
        $license = $this->makeLicense(['seats' => 1]);
        $a1 = $this->makeAsset();
        $a2 = $this->makeAsset();

        $this->actingAs($tech)
            ->post(route('licenses.assets.attach', $license), ['asset_id' => $a1->id])
            ->assertRedirect();

        $this->actingAs($tech)
            ->post(route('licenses.assets.attach', $license), ['asset_id' => $a2->id])
            ->assertSessionHasErrors('asset_id');

        $this->assertEquals(1, $license->fresh()->assets()->count());
    }

    public function test_user_cannot_create_license(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::User,
            'entity_id' => Entity::firstOrCreate(['name' => 'Test'])->id,
        ]);

        $this->actingAs($user)->post('/licenses', [
            'name' => 'Interdit',
            'seats' => 1,
        ])->assertForbidden();
    }
}
