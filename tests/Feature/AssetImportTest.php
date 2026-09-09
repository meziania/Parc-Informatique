<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\Entity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AssetImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_technician_can_import_csv(): void
    {
        $tech = User::factory()->create([
            'role' => UserRole::Technician,
            'entity_id' => Entity::firstOrCreate(['name' => 'Test'])->id,
        ]);

        $csv = "N° inventaire;Nom;Type;Statut;Fabricant\nINV-CSV-1;PC Import;computer;in_stock;Dell\n";
        $file = UploadedFile::fake()->createWithContent('parc.csv', $csv);

        $this->actingAs($tech)
            ->post(route('assets.import.store'), ['file' => $file])
            ->assertRedirect(route('assets.index'));

        $this->assertDatabaseHas('assets', [
            'inventory_number' => 'INV-CSV-1',
            'name' => 'PC Import',
        ]);
    }

    public function test_user_cannot_import(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::User,
            'entity_id' => Entity::firstOrCreate(['name' => 'Test'])->id,
        ]);

        $file = UploadedFile::fake()->createWithContent('parc.csv', "a;b\n");

        $this->actingAs($user)
            ->post(route('assets.import.store'), ['file' => $file])
            ->assertForbidden();
    }
}
