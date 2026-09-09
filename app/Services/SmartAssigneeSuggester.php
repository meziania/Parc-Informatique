<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Collection;

class SmartAssigneeSuggester
{
    /**
     * @return array{id: int, name: string, open_tickets_count: int, reason: string}|null
     */
    public function suggest(Ticket $ticket): ?array
    {
        $technicians = User::query()
            ->whereIn('role', [UserRole::Technician->value, UserRole::Admin->value])
            ->where('is_active', true)
            ->withCount([
                'assignedTickets as open_tickets_count' => fn ($query) => $query->whereIn('status', [
                    TicketStatus::New->value,
                    TicketStatus::Assigned->value,
                    TicketStatus::InProgress->value,
                ]),
            ])
            ->get(['id', 'name']);

        if ($technicians->isEmpty()) {
            return null;
        }

        $expertScores = $this->expertScores($ticket, $technicians->pluck('id'));

        $ranked = $technicians
            ->map(function (User $tech) use ($expertScores) {
                $expertise = (int) ($expertScores[$tech->id] ?? 0);

                return [
                    'id' => $tech->id,
                    'name' => $tech->name,
                    'open_tickets_count' => (int) $tech->open_tickets_count,
                    'expertise' => $expertise,
                ];
            })
            ->sort(function (array $a, array $b) {
                if ($a['expertise'] !== $b['expertise']) {
                    return $b['expertise'] <=> $a['expertise'];
                }

                if ($a['open_tickets_count'] !== $b['open_tickets_count']) {
                    return $a['open_tickets_count'] <=> $b['open_tickets_count'];
                }

                return strcmp($a['name'], $b['name']);
            })
            ->values();

        $best = $ranked->first();
        if ($best === null) {
            return null;
        }

        $reason = $best['expertise'] > 0
            ? sprintf(
                'Expert sur cet équipement (%d résolution%s) · charge %d ouvert%s',
                $best['expertise'],
                $best['expertise'] > 1 ? 's' : '',
                $best['open_tickets_count'],
                $best['open_tickets_count'] > 1 ? 's' : ''
            )
            : sprintf(
                'Charge la plus faible (%d ticket%s ouvert%s)',
                $best['open_tickets_count'],
                $best['open_tickets_count'] > 1 ? 's' : '',
                $best['open_tickets_count'] > 1 ? 's' : ''
            );

        return [
            'id' => $best['id'],
            'name' => $best['name'],
            'open_tickets_count' => $best['open_tickets_count'],
            'reason' => $reason,
        ];
    }

    /**
     * @param  Collection<int, int>  $technicianIds
     * @return array<int, int>
     */
    private function expertScores(Ticket $ticket, Collection $technicianIds): array
    {
        if ($technicianIds->isEmpty()) {
            return [];
        }

        $query = Ticket::query()
            ->selectRaw('assignee_id, COUNT(*) as resolved_count')
            ->whereIn('assignee_id', $technicianIds->all())
            ->whereIn('status', [TicketStatus::Resolved->value, TicketStatus::Closed->value])
            ->whereNotNull('assignee_id')
            ->groupBy('assignee_id');

        if ($ticket->asset_id) {
            $query->where('asset_id', $ticket->asset_id);
        } elseif ($ticket->asset?->type) {
            $query->whereHas('asset', fn ($q) => $q->where('type', $ticket->asset->type));
        } else {
            return [];
        }

        return $query->pluck('resolved_count', 'assignee_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }
}
