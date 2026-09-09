<?php

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\Ticket;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 40)->index();
            $table->string('title');
            $table->text('body')->nullable();
            $table->json('meta')->nullable();
            $table->nullableMorphs('related');
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Asset::query()->orderBy('id')->each(function (Asset $asset) {
            $status = $asset->status instanceof AssetStatus
                ? $asset->status
                : AssetStatus::tryFrom((string) $asset->status);

            DB::table('asset_events')->insert([
                'asset_id' => $asset->id,
                'actor_id' => null,
                'event_type' => 'created',
                'title' => 'Équipement créé',
                'body' => $asset->inventory_number.' — '.($status?->label() ?? (string) $asset->status),
                'meta' => json_encode([
                    'status' => $status?->value ?? $asset->status,
                ]),
                'related_type' => null,
                'related_id' => null,
                'created_at' => $asset->created_at ?? now(),
            ]);
        });

        Ticket::query()
            ->whereNotNull('asset_id')
            ->orderBy('id')
            ->each(function (Ticket $ticket) {
                $statusValue = $ticket->status?->value ?? (string) $ticket->status;

                DB::table('asset_events')->insert([
                    'asset_id' => $ticket->asset_id,
                    'actor_id' => $ticket->requester_id,
                    'event_type' => 'ticket_opened',
                    'title' => 'Ticket ouvert : '.$ticket->number,
                    'body' => $ticket->title,
                    'meta' => json_encode([
                        'ticket_id' => $ticket->id,
                        'ticket_number' => $ticket->number,
                        'status' => $statusValue,
                    ]),
                    'related_type' => Ticket::class,
                    'related_id' => $ticket->id,
                    'created_at' => $ticket->created_at ?? now(),
                ]);

                if (filled($ticket->solution) || in_array($statusValue, ['resolved', 'closed'], true)) {
                    DB::table('asset_events')->insert([
                        'asset_id' => $ticket->asset_id,
                        'actor_id' => $ticket->assignee_id,
                        'event_type' => 'ticket_resolved',
                        'title' => 'Ticket résolu : '.$ticket->number,
                        'body' => Str::limit($ticket->solution ?: $ticket->title, 200),
                        'meta' => json_encode([
                            'ticket_id' => $ticket->id,
                            'ticket_number' => $ticket->number,
                        ]),
                        'related_type' => Ticket::class,
                        'related_id' => $ticket->id,
                        'created_at' => $ticket->resolved_at ?? $ticket->updated_at ?? now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_events');
    }
};
