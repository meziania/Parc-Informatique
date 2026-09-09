<?php

namespace App\Services;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetEvent;
use App\Models\Location;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AssetHistoryLogger
{
    public function created(Asset $asset, ?User $actor = null): AssetEvent
    {
        $status = $this->asStatus($asset->status);

        return $this->log(
            $asset,
            'created',
            'Équipement créé',
            $asset->inventory_number.' — '.($status?->label() ?? '—'),
            ['status' => $status?->value],
            $actor,
        );
    }

    public function recordChanges(Asset $asset, ?User $actor = null): void
    {
        if ($asset->wasChanged('status')) {
            $old = $this->asStatus($asset->getOriginal('status'));
            $new = $this->asStatus($asset->status);

            $this->log(
                $asset,
                'status_changed',
                'Statut modifié',
                ($old?->label() ?? '—').' → '.($new?->label() ?? '—'),
                ['from' => $old?->value, 'to' => $new?->value],
                $actor,
            );
        }

        if ($asset->wasChanged('user_id')) {
            $fromId = $asset->getOriginal('user_id');
            $toId = $asset->user_id;
            $from = $fromId ? User::query()->find($fromId)?->name : 'Non affecté';
            $to = $toId ? User::query()->find($toId)?->name : 'Non affecté';

            $this->log(
                $asset,
                'assigned',
                'Affectation modifiée',
                ($from ?? '—').' → '.($to ?? '—'),
                ['from_user_id' => $fromId, 'to_user_id' => $toId],
                $actor,
            );
        }

        if ($asset->wasChanged('location_id')) {
            $fromId = $asset->getOriginal('location_id');
            $toId = $asset->location_id;
            $from = $fromId ? Location::query()->find($fromId)?->name : 'Aucun';
            $to = $toId ? Location::query()->find($toId)?->name : 'Aucun';

            $this->log(
                $asset,
                'location_changed',
                'Lieu modifié',
                ($from ?? '—').' → '.($to ?? '—'),
                ['from_location_id' => $fromId, 'to_location_id' => $toId],
                $actor,
            );
        }
    }

    public function ticketOpened(Ticket $ticket, ?User $actor = null): ?AssetEvent
    {
        if (! $ticket->asset_id) {
            return null;
        }

        $asset = $ticket->relationLoaded('asset')
            ? $ticket->asset
            : Asset::query()->find($ticket->asset_id);

        if (! $asset) {
            return null;
        }

        return $this->log(
            $asset,
            'ticket_opened',
            'Ticket ouvert : '.$ticket->number,
            $ticket->title,
            [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->number,
                'status' => $ticket->status?->value ?? $ticket->status,
            ],
            $actor ?? $ticket->requester,
            $ticket,
        );
    }

    public function ticketResolved(Ticket $ticket, ?User $actor = null): ?AssetEvent
    {
        if (! $ticket->asset_id) {
            return null;
        }

        $asset = $ticket->relationLoaded('asset')
            ? $ticket->asset
            : Asset::query()->find($ticket->asset_id);

        if (! $asset) {
            return null;
        }

        return $this->log(
            $asset,
            'ticket_resolved',
            'Ticket résolu : '.$ticket->number,
            $ticket->solution ?: $ticket->title,
            [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->number,
            ],
            $actor ?? Auth::user(),
            $ticket,
        );
    }

    public function log(
        Asset $asset,
        string $eventType,
        string $title,
        ?string $body = null,
        ?array $meta = null,
        ?User $actor = null,
        ?Ticket $related = null,
    ): AssetEvent {
        return AssetEvent::query()->create([
            'asset_id' => $asset->id,
            'actor_id' => ($actor ?? Auth::user())?->id,
            'event_type' => $eventType,
            'title' => $title,
            'body' => $body,
            'meta' => $meta,
            'related_type' => $related ? Ticket::class : null,
            'related_id' => $related?->id,
            'created_at' => now(),
        ]);
    }

    private function asStatus(mixed $value): ?AssetStatus
    {
        if ($value instanceof AssetStatus) {
            return $value;
        }

        if ($value === null || $value === '') {
            return null;
        }

        return AssetStatus::tryFrom((string) $value);
    }
}
