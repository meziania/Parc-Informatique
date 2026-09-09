<?php

namespace App\Http\Controllers;

use App\Enums\AssetStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\ReservationStatus;
use App\Models\Asset;
use App\Models\Reservation;
use App\Models\SoftwareLicense;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /** @var list<TicketStatus> */
    private const OPEN_STATUSES = [
        TicketStatus::New,
        TicketStatus::Assigned,
        TicketStatus::InProgress,
    ];

    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Dashboard', $user->isTechnician()
            ? $this->technicianPayload($user->id)
            : $this->userPayload($user->id)
        );
    }

    /** @return array<string, mixed> */
    private function technicianPayload(int $userId): array
    {
        $openQuery = Ticket::query()->whereIn('status', self::OPEN_STATUSES);

        return [
            'view' => 'technician',
            'stats' => [
                'open_tickets' => (clone $openQuery)->count(),
                'unassigned_tickets' => Ticket::query()->where('status', TicketStatus::New)->count(),
                'urgent_tickets' => (clone $openQuery)
                    ->where('priority', TicketPriority::Urgent)
                    ->count(),
                'overdue_tickets' => Ticket::query()->slaBreached()->count(),
                'at_risk_tickets' => Ticket::query()->slaAtRisk()->count(),
                'my_tickets' => Ticket::query()
                    ->where('assignee_id', $userId)
                    ->whereIn('status', self::OPEN_STATUSES)
                    ->count(),
                'broken_assets' => Asset::query()->where('status', AssetStatus::Broken)->count(),
                'assets_total' => Asset::query()->count(),
                'assets_in_stock' => Asset::query()->where('status', AssetStatus::InStock)->count(),
                'avg_satisfaction' => round(
                    (float) Ticket::query()->whereNotNull('satisfaction_rating')->avg('satisfaction_rating'),
                    1
                ),
                'satisfaction_count' => Ticket::query()->whereNotNull('satisfaction_rating')->count(),
                'awaiting_satisfaction' => Ticket::query()
                    ->whereIn('status', [TicketStatus::Resolved, TicketStatus::Closed])
                    ->whereNull('satisfaction_rating')
                    ->count(),
                'warranty_expired' => Asset::query()->warrantyExpired()->count(),
                'warranty_expiring_30d' => Asset::query()->warrantyExpiringSoon(30)->count(),
                'maintenance_due' => Asset::query()->maintenanceDue()->count(),
                'maintenance_soon' => Asset::query()->maintenanceSoon(30)->count(),
                'licenses_expired' => SoftwareLicense::query()->expired()->count(),
                'licenses_expiring_30d' => SoftwareLicense::query()->expiringSoon(30)->count(),
                'pending_reservations' => Reservation::query()
                    ->where('status', ReservationStatus::Pending)
                    ->count(),
                ...$this->qualityStats(),
            ],
            'recent_tickets' => $this->ticketList(
                Ticket::query()
                    ->with(['requester:id,name', 'assignee:id,name', 'asset:id,name,inventory_number,type,status'])
                    ->whereIn('status', self::OPEN_STATUSES)
                    ->latest()
                    ->limit(8)
                    ->get()
            ),
            'overdue_tickets' => $this->ticketList(
                Ticket::query()
                    ->with(['requester:id,name', 'assignee:id,name', 'asset:id,name,inventory_number,type,status'])
                    ->slaBreached()
                    ->orderBy('due_at')
                    ->limit(5)
                    ->get()
            ),
            'urgent_tickets' => $this->ticketList(
                Ticket::query()
                    ->with(['requester:id,name', 'assignee:id,name', 'asset:id,name,inventory_number,type,status'])
                    ->whereIn('status', self::OPEN_STATUSES)
                    ->where('priority', TicketPriority::Urgent)
                    ->latest()
                    ->limit(5)
                    ->get()
            ),
            'broken_assets' => Asset::query()
                ->with(['user:id,name', 'location:id,name'])
                ->where('status', AssetStatus::Broken)
                ->orderBy('name')
                ->limit(8)
                ->get(['id', 'name', 'inventory_number', 'type', 'status', 'user_id', 'location_id']),
            'maintenance_assets' => Asset::query()
                ->with(['user:id,name', 'location:id,name'])
                ->where(function ($query) {
                    $query->warrantyExpired()
                        ->orWhere(fn ($q) => $q->warrantyExpiringSoon(30))
                        ->orWhere(fn ($q) => $q->maintenanceDue())
                        ->orWhere(fn ($q) => $q->maintenanceSoon(30));
                })
                ->orderByRaw('COALESCE(next_maintenance_at, warranty_end) ASC NULLS LAST')
                ->limit(8)
                ->get([
                    'id', 'name', 'inventory_number', 'type', 'status',
                    'user_id', 'location_id', 'warranty_end', 'next_maintenance_at',
                ]),
            'my_tickets' => $this->ticketList(
                Ticket::query()
                    ->with(['requester:id,name', 'assignee:id,name', 'asset:id,name,inventory_number,type,status'])
                    ->where('assignee_id', $userId)
                    ->whereIn('status', self::OPEN_STATUSES)
                    ->latest()
                    ->limit(5)
                    ->get()
            ),
        ];
    }

    /** @return array<string, mixed> */
    private function userPayload(int $userId): array
    {
        $myOpen = Ticket::query()
            ->where('requester_id', $userId)
            ->whereIn('status', self::OPEN_STATUSES);

        return [
            'view' => 'user',
            'stats' => [
                'open_tickets' => (clone $myOpen)->count(),
                'resolved_tickets' => Ticket::query()
                    ->where('requester_id', $userId)
                    ->where('status', TicketStatus::Resolved)
                    ->count(),
                'overdue_tickets' => (clone $myOpen)->slaBreached()->count(),
                'my_assets' => Asset::query()->where('user_id', $userId)->count(),
                'pending_satisfaction' => Ticket::query()
                    ->where('requester_id', $userId)
                    ->whereIn('status', [TicketStatus::Resolved, TicketStatus::Closed])
                    ->whereNull('satisfaction_rating')
                    ->count(),
                'my_reservations' => Reservation::query()
                    ->where('user_id', $userId)
                    ->whereIn('status', [
                        ReservationStatus::Pending,
                        ReservationStatus::Approved,
                    ])
                    ->where('ends_at', '>=', now())
                    ->count(),
            ],
            'recent_tickets' => $this->ticketList(
                Ticket::query()
                    ->with(['requester:id,name', 'assignee:id,name', 'asset:id,name,inventory_number,type,status'])
                    ->where('requester_id', $userId)
                    ->latest()
                    ->limit(8)
                    ->get()
            ),
            'my_assets' => Asset::query()
                ->with(['location:id,name'])
                ->where('user_id', $userId)
                ->orderBy('name')
                ->limit(8)
                ->get(['id', 'name', 'inventory_number', 'type', 'status', 'location_id']),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Ticket>  $tickets
     * @return list<array<string, mixed>>
     */
    private function ticketList($tickets): array
    {
        return $tickets->map(fn (Ticket $ticket) => [
            'id' => $ticket->id,
            'number' => $ticket->number,
            'title' => $ticket->title,
            'type_label' => $ticket->type_label,
            'priority_label' => $ticket->priority_label,
            'priority_color' => $ticket->priority_color,
            'status_label' => $ticket->status_label,
            'status_color' => $ticket->status_color,
            'due_at' => $ticket->due_at?->toIso8601String(),
            'sla_status' => $ticket->sla_status,
            'sla_label' => $ticket->sla_label,
            'sla_color' => $ticket->sla_color,
            'created_at' => $ticket->created_at?->toIso8601String(),
            'requester' => $ticket->requester
                ? ['id' => $ticket->requester->id, 'name' => $ticket->requester->name]
                : null,
            'assignee' => $ticket->assignee
                ? ['id' => $ticket->assignee->id, 'name' => $ticket->assignee->name]
                : null,
            'asset' => $ticket->asset
                ? [
                    'id' => $ticket->asset->id,
                    'name' => $ticket->asset->name,
                    'inventory_number' => $ticket->asset->inventory_number,
                ]
                : null,
        ])->all();
    }

    /** @return array{sla_met_rate_30d: float|null, avg_satisfaction_30d: float|null, satisfaction_count_30d: int} */
    private function qualityStats(): array
    {
        $since = now()->subDays(30);

        $closedQuery = Ticket::query()
            ->whereIn('status', [TicketStatus::Resolved, TicketStatus::Closed])
            ->where(function ($query) use ($since) {
                $query->where('resolved_at', '>=', $since)
                    ->orWhere('closed_at', '>=', $since);
            });

        $withSla = (clone $closedQuery)->whereNotNull('due_at');
        $withSlaTotal = (clone $withSla)->count();
        $slaMet = (clone $withSla)
            ->whereRaw('COALESCE(resolved_at, closed_at) <= due_at')
            ->count();

        $satisfactionQuery = Ticket::query()
            ->whereNotNull('satisfaction_rating')
            ->where('satisfaction_rated_at', '>=', $since);

        $satisfactionCount = (clone $satisfactionQuery)->count();

        return [
            'sla_met_rate_30d' => $withSlaTotal > 0
                ? round(($slaMet / $withSlaTotal) * 100, 1)
                : null,
            'avg_satisfaction_30d' => $satisfactionCount > 0
                ? round((float) (clone $satisfactionQuery)->avg('satisfaction_rating'), 1)
                : null,
            'satisfaction_count_30d' => $satisfactionCount,
        ];
    }
}
