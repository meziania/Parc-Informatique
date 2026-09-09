<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\Document;
use App\Models\Entity;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(UserRole $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'entity_id' => Entity::firstOrCreate(['name' => 'Test'])->id,
        ]);
    }

    private function makeAsset(?User $owner = null): Asset
    {
        return Asset::create([
            'name' => 'PC Docs',
            'type' => 'computer',
            'status' => 'in_use',
            'inventory_number' => 'INV-DOC-'.fake()->unique()->numberBetween(1000, 9999),
            'user_id' => $owner?->id,
        ]);
    }

    private function makeTicket(User $requester): Ticket
    {
        return Ticket::create([
            'title' => 'Ticket docs',
            'description' => 'Description',
            'type' => 'incident',
            'priority' => 'medium',
            'requester_id' => $requester->id,
        ]);
    }

    public function test_technician_can_attach_document_to_asset(): void
    {
        Storage::fake('local');

        $technician = $this->userWithRole(UserRole::Technician);
        $asset = $this->makeAsset();

        $this->actingAs($technician)->post('/documents', [
            'documentable_type' => 'asset',
            'documentable_id' => $asset->id,
            'file' => UploadedFile::fake()->create('facture.pdf', 120, 'application/pdf'),
        ])->assertRedirect();

        $this->assertDatabaseHas('documents', [
            'documentable_type' => Asset::class,
            'documentable_id' => $asset->id,
            'original_name' => 'facture.pdf',
            'uploaded_by' => $technician->id,
        ]);

        $document = Document::first();
        Storage::disk('local')->assertExists($document->path);
    }

    public function test_simple_user_cannot_attach_document_to_asset(): void
    {
        Storage::fake('local');

        $user = $this->userWithRole(UserRole::User);
        $asset = $this->makeAsset($user);

        $this->actingAs($user)->post('/documents', [
            'documentable_type' => 'asset',
            'documentable_id' => $asset->id,
            'file' => UploadedFile::fake()->create('photo.jpg', 80, 'image/jpeg'),
        ])->assertForbidden();
    }

    public function test_requester_can_attach_document_to_own_ticket(): void
    {
        Storage::fake('local');

        $user = $this->userWithRole(UserRole::User);
        $ticket = $this->makeTicket($user);

        $this->actingAs($user)->post('/documents', [
            'documentable_type' => 'ticket',
            'documentable_id' => $ticket->id,
            'file' => UploadedFile::fake()->create('capture.png', 50, 'image/png'),
        ])->assertRedirect();

        $this->assertDatabaseHas('documents', [
            'documentable_type' => Ticket::class,
            'documentable_id' => $ticket->id,
            'original_name' => 'capture.png',
        ]);
    }

    public function test_user_cannot_attach_to_someone_elses_ticket(): void
    {
        Storage::fake('local');

        $user = $this->userWithRole(UserRole::User);
        $other = $this->userWithRole(UserRole::User);
        $ticket = $this->makeTicket($other);

        $this->actingAs($user)->post('/documents', [
            'documentable_type' => 'ticket',
            'documentable_id' => $ticket->id,
            'file' => UploadedFile::fake()->create('secret.pdf', 20, 'application/pdf'),
        ])->assertForbidden();
    }

    public function test_authorized_user_can_download_document(): void
    {
        Storage::fake('local');

        $user = $this->userWithRole(UserRole::User);
        $ticket = $this->makeTicket($user);
        $path = UploadedFile::fake()->create('note.txt', 10, 'text/plain')->store('tickets/'.$ticket->id, 'local');

        $document = $ticket->documents()->create([
            'original_name' => 'note.txt',
            'disk' => 'local',
            'path' => $path,
            'mime_type' => 'text/plain',
            'size' => 10240,
            'uploaded_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('documents.download', $document))
            ->assertOk();
    }

    public function test_uploader_can_delete_own_document(): void
    {
        Storage::fake('local');

        $user = $this->userWithRole(UserRole::User);
        $ticket = $this->makeTicket($user);
        $path = UploadedFile::fake()->create('a-supprimer.pdf', 30, 'application/pdf')->store('tickets/'.$ticket->id, 'local');

        $document = $ticket->documents()->create([
            'original_name' => 'a-supprimer.pdf',
            'disk' => 'local',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'size' => 30720,
            'uploaded_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->delete(route('documents.destroy', $document))
            ->assertRedirect();

        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_user_cannot_delete_document_uploaded_by_someone_else(): void
    {
        Storage::fake('local');

        $user = $this->userWithRole(UserRole::User);
        $technician = $this->userWithRole(UserRole::Technician);
        $ticket = $this->makeTicket($user);
        $path = UploadedFile::fake()->create('rapport.pdf', 40, 'application/pdf')->store('tickets/'.$ticket->id, 'local');

        $document = $ticket->documents()->create([
            'original_name' => 'rapport.pdf',
            'disk' => 'local',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'size' => 40960,
            'uploaded_by' => $technician->id,
        ]);

        $this->actingAs($user)
            ->delete(route('documents.destroy', $document))
            ->assertForbidden();
    }

    public function test_rejects_disallowed_file_type(): void
    {
        Storage::fake('local');

        $technician = $this->userWithRole(UserRole::Technician);
        $asset = $this->makeAsset();

        $this->actingAs($technician)->post('/documents', [
            'documentable_type' => 'asset',
            'documentable_id' => $asset->id,
            'file' => UploadedFile::fake()->create('malware.exe', 10, 'application/octet-stream'),
        ])->assertSessionHasErrors('file');
    }
}
