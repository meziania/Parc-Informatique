<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\Entity;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    private function user(UserRole $role = UserRole::User): User
    {
        return User::factory()->create([
            'role' => $role,
            'entity_id' => Entity::firstOrCreate(['name' => 'Test'])->id,
        ]);
    }

    private function makeAsset(): Asset
    {
        return Asset::create([
            'name' => 'PC Portable Résa',
            'type' => 'computer',
            'status' => 'in_stock',
            'inventory_number' => 'INV-RES-'.fake()->unique()->numberBetween(1, 9999),
        ]);
    }

    public function test_user_can_create_reservation(): void
    {
        $user = $this->user();
        $asset = $this->makeAsset();
        $starts = now()->addDay()->setTime(10, 0);
        $ends = now()->addDay()->setTime(12, 0);

        $this->actingAs($user)->post('/reservations', [
            'title' => 'Prêt portable',
            'asset_id' => $asset->id,
            'starts_at' => $starts->format('Y-m-d\TH:i'),
            'ends_at' => $ends->format('Y-m-d\TH:i'),
        ])->assertRedirect();

        $this->assertDatabaseHas('reservations', [
            'title' => 'Prêt portable',
            'user_id' => $user->id,
            'status' => ReservationStatus::Pending->value,
        ]);
    }

    public function test_conflict_blocks_overlapping_reservation(): void
    {
        $user = $this->user();
        $asset = $this->makeAsset();
        $starts = now()->addDays(2)->setTime(9, 0);
        $ends = now()->addDays(2)->setTime(11, 0);

        Reservation::create([
            'user_id' => $user->id,
            'asset_id' => $asset->id,
            'title' => 'Déjà pris',
            'starts_at' => $starts,
            'ends_at' => $ends,
            'status' => ReservationStatus::Approved,
        ]);

        $this->actingAs($user)->post('/reservations', [
            'title' => 'Conflit',
            'asset_id' => $asset->id,
            'starts_at' => $starts->copy()->addHour()->format('Y-m-d\TH:i'),
            'ends_at' => $ends->copy()->addHour()->format('Y-m-d\TH:i'),
        ])->assertSessionHasErrors('starts_at');
    }

    public function test_technician_can_approve(): void
    {
        $user = $this->user();
        $tech = $this->user(UserRole::Technician);
        $asset = $this->makeAsset();

        $reservation = Reservation::create([
            'user_id' => $user->id,
            'asset_id' => $asset->id,
            'title' => 'À valider',
            'starts_at' => now()->addDays(3)->setTime(14, 0),
            'ends_at' => now()->addDays(3)->setTime(16, 0),
            'status' => ReservationStatus::Pending,
        ]);

        $this->actingAs($tech)
            ->post(route('reservations.approve', $reservation))
            ->assertRedirect();

        $this->assertEquals(
            ReservationStatus::Approved,
            $reservation->fresh()->status
        );
    }
}
