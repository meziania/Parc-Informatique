<?php

namespace App\Services;

use App\Enums\AssetStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Asset;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Carbon;

class WeeklyReportBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(?Carbon $from = null, ?Carbon $to = null): array
    {
        $to = ($to ?? now())->copy()->endOfDay();
        $from = ($from ?? now()->subDays(7))->copy()->startOfDay();

        $openStatuses = [
            TicketStatus::New,
            TicketStatus::Assigned,
            TicketStatus::InProgress,
        ];

        $closedQuery = Ticket::query()
            ->whereIn('status', [TicketStatus::Resolved, TicketStatus::Closed])
            ->where(function ($query) use ($from, $to) {
                $query->whereBetween('resolved_at', [$from, $to])
                    ->orWhereBetween('closed_at', [$from, $to]);
            });

        $closedTotal = (clone $closedQuery)->count();
        $slaMet = (clone $closedQuery)
            ->whereNotNull('due_at')
            ->whereRaw('COALESCE(resolved_at, closed_at) <= due_at')
            ->count();

        $satisfaction = Ticket::query()
            ->whereNotNull('satisfaction_rating')
            ->whereBetween('satisfaction_rated_at', [$from, $to]);

        $satisfactionCount = (clone $satisfaction)->count();

        $topRequesters = Ticket::query()
            ->selectRaw('requester_id, COUNT(*) as tickets_count')
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('requester_id')
            ->groupBy('requester_id')
            ->orderByDesc('tickets_count')
            ->limit(5)
            ->with('requester:id,name,email')
            ->get()
            ->map(fn (Ticket $ticket) => [
                'name' => $ticket->requester?->name ?? '—',
                'email' => $ticket->requester?->email,
                'count' => (int) $ticket->tickets_count,
            ])
            ->all();

        return [
            'generated_at' => now(),
            'period' => [
                'from' => $from,
                'to' => $to,
                'label' => $from->format('d/m/Y').' → '.$to->format('d/m/Y'),
            ],
            'tickets' => [
                'opened' => Ticket::query()->whereBetween('created_at', [$from, $to])->count(),
                'resolved' => Ticket::query()
                    ->whereBetween('resolved_at', [$from, $to])
                    ->count(),
                'open_now' => Ticket::query()->whereIn('status', $openStatuses)->count(),
                'unassigned' => Ticket::query()->where('status', TicketStatus::New)->count(),
                'urgent_open' => Ticket::query()
                    ->whereIn('status', $openStatuses)
                    ->where('priority', TicketPriority::Urgent)
                    ->count(),
                'sla_breached' => Ticket::query()->slaBreached()->count(),
                'sla_at_risk' => Ticket::query()->slaAtRisk()->count(),
                'sla_met_rate' => $closedTotal > 0
                    ? round(($slaMet / $closedTotal) * 100, 1)
                    : null,
                'avg_satisfaction' => $satisfactionCount > 0
                    ? round((float) (clone $satisfaction)->avg('satisfaction_rating'), 1)
                    : null,
                'satisfaction_count' => $satisfactionCount,
            ],
            'assets' => [
                'total' => Asset::query()->count(),
                'broken' => Asset::query()->where('status', AssetStatus::Broken)->count(),
                'warranty_expired' => Asset::query()->warrantyExpired()->count(),
                'warranty_expiring_30d' => Asset::query()->warrantyExpiringSoon(30)->count(),
                'maintenance_due' => Asset::query()->maintenanceDue()->count(),
                'maintenance_soon' => Asset::query()->maintenanceSoon(30)->count(),
            ],
            'top_requesters' => $topRequesters,
            'technicians' => User::query()
                ->whereIn('role', ['technician', 'admin'])
                ->where('is_active', true)
                ->withCount([
                    'assignedTickets as open_tickets_count' => fn ($q) => $q->whereIn('status', [
                        TicketStatus::New->value,
                        TicketStatus::Assigned->value,
                        TicketStatus::InProgress->value,
                    ]),
                ])
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->map(fn (User $user) => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'open_tickets' => (int) $user->open_tickets_count,
                ])
                ->all(),
        ];
    }
}
