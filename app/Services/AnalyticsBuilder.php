<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\ServiceCatalogItem;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Carbon;

class AnalyticsBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(int $days = 30): array
    {
        $days = max(7, min(90, $days));
        $to = now()->copy()->endOfDay();
        $from = now()->copy()->subDays($days - 1)->startOfDay();

        return [
            'period' => [
                'days' => $days,
                'from' => $from->toIso8601String(),
                'to' => $to->toIso8601String(),
                'label' => $from->format('d/m/Y').' → '.$to->format('d/m/Y'),
            ],
            'kpis' => $this->kpis($from, $to),
            'tickets_per_day' => $this->ticketsPerDay($from, $to),
            'satisfaction_per_day' => $this->satisfactionPerDay($from, $to),
            'by_catalog' => $this->byCatalog($from, $to),
            'by_priority' => $this->byPriority($from, $to),
            'technician_workload' => $this->technicianWorkload(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function kpis(Carbon $from, Carbon $to): array
    {
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

        return [
            'opened' => Ticket::query()->whereBetween('created_at', [$from, $to])->count(),
            'resolved' => Ticket::query()->whereBetween('resolved_at', [$from, $to])->count(),
            'open_now' => Ticket::query()->whereIn('status', [
                TicketStatus::New,
                TicketStatus::Assigned,
                TicketStatus::InProgress,
            ])->count(),
            'sla_met_rate' => $closedTotal > 0
                ? round(($slaMet / $closedTotal) * 100, 1)
                : null,
            'avg_satisfaction' => $satisfactionCount > 0
                ? round((float) (clone $satisfaction)->avg('satisfaction_rating'), 1)
                : null,
            'satisfaction_count' => $satisfactionCount,
        ];
    }

    /**
     * @return list<array{date: string, label: string, opened: int, resolved: int}>
     */
    private function ticketsPerDay(Carbon $from, Carbon $to): array
    {
        $opened = Ticket::query()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('day')
            ->pluck('total', 'day');

        $resolved = Ticket::query()
            ->selectRaw('DATE(resolved_at) as day, COUNT(*) as total')
            ->whereNotNull('resolved_at')
            ->whereBetween('resolved_at', [$from, $to])
            ->groupBy('day')
            ->pluck('total', 'day');

        $rows = [];
        $cursor = $from->copy();

        while ($cursor->lte($to)) {
            $key = $cursor->toDateString();
            $rows[] = [
                'date' => $key,
                'label' => $cursor->format('d/m'),
                'opened' => (int) ($opened[$key] ?? 0),
                'resolved' => (int) ($resolved[$key] ?? 0),
            ];
            $cursor->addDay();
        }

        return $rows;
    }

    /**
     * @return list<array{date: string, label: string, avg: float|null, count: int}>
     */
    private function satisfactionPerDay(Carbon $from, Carbon $to): array
    {
        $rated = Ticket::query()
            ->selectRaw('DATE(satisfaction_rated_at) as day, AVG(satisfaction_rating) as avg_rating, COUNT(*) as total')
            ->whereNotNull('satisfaction_rating')
            ->whereBetween('satisfaction_rated_at', [$from, $to])
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $rows = [];
        $cursor = $from->copy();

        while ($cursor->lte($to)) {
            $key = $cursor->toDateString();
            $row = $rated->get($key);
            $rows[] = [
                'date' => $key,
                'label' => $cursor->format('d/m'),
                'avg' => $row ? round((float) $row->avg_rating, 2) : null,
                'count' => $row ? (int) $row->total : 0,
            ];
            $cursor->addDay();
        }

        return $rows;
    }

    /**
     * @return list<array{name: string, count: int}>
     */
    private function byCatalog(Carbon $from, Carbon $to): array
    {
        $rows = Ticket::query()
            ->selectRaw('service_catalog_item_id, COUNT(*) as total')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('service_catalog_item_id')
            ->orderByDesc('total')
            ->get();

        $names = ServiceCatalogItem::query()
            ->whereIn('id', $rows->pluck('service_catalog_item_id')->filter())
            ->pluck('name', 'id');

        return $rows->map(fn ($row) => [
            'name' => $row->service_catalog_item_id
                ? (string) ($names[$row->service_catalog_item_id] ?? 'Service #'.$row->service_catalog_item_id)
                : 'Hors catalogue',
            'count' => (int) $row->total,
        ])->values()->all();
    }

    /**
     * @return list<array{name: string, count: int}>
     */
    private function byPriority(Carbon $from, Carbon $to): array
    {
        $labels = [
            'urgent' => 'Urgent',
            'high' => 'Haute',
            'medium' => 'Moyenne',
            'low' => 'Basse',
        ];

        return Ticket::query()
            ->selectRaw('priority, COUNT(*) as total')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('priority')
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->get()
            ->map(function ($row) use ($labels) {
                $priority = $row->priority instanceof \BackedEnum
                    ? $row->priority->value
                    : (string) $row->priority;

                return [
                    'name' => $labels[$priority] ?? $priority,
                    'count' => (int) $row->total,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{name: string, open_tickets: int}>
     */
    private function technicianWorkload(): array
    {
        return User::query()
            ->whereIn('role', ['technician', 'admin'])
            ->where('is_active', true)
            ->withCount([
                'assignedTickets as open_tickets_count' => fn ($query) => $query->whereIn('status', [
                    TicketStatus::New->value,
                    TicketStatus::Assigned->value,
                    TicketStatus::InProgress->value,
                ]),
            ])
            ->orderByDesc('open_tickets_count')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user) => [
                'name' => $user->name,
                'open_tickets' => (int) $user->open_tickets_count,
            ])
            ->all();
    }
}
