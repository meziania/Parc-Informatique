<?php

namespace App\Http\Controllers;

use App\Enums\ApprovalStatus;
use App\Enums\FaqCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\ServiceCatalogItem;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketAssignedNotification;
use App\Notifications\TicketCreatedNotification;
use App\Notifications\TicketResolvedNotification;
use App\Services\Ai\TicketResolutionAssistService;
use App\Services\Ai\TicketSuggestionService;
use App\Services\AssetHistoryLogger;
use App\Services\SmartAssigneeSuggester;
use App\Support\CsvExporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketController extends Controller
{
    public function index(Request $request): Response
    {
        $tickets = $this->filteredQuery($request)
            ->with(['requester:id,name,email', 'assignee:id,name', 'asset:id,name,inventory_number,type,status'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Tickets/Index', [
            'tickets' => $tickets,
            'filters' => $request->only(['search', 'type', 'priority', 'status', 'sla']),
            'types' => $this->typeOptions(),
            'priorities' => $this->priorityOptions(),
            'statuses' => $this->statusOptions(),
            'slaFilters' => [
                ['value' => 'breached', 'label' => 'SLA dépassé'],
                ['value' => 'at_risk', 'label' => 'À risque (< 4 h)'],
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filename = 'tickets_'.now()->format('Y-m-d_His').'.csv';

        $rows = $this->filteredQuery($request)
            ->with(['requester:id,name', 'assignee:id,name', 'asset:id,name,inventory_number'])
            ->orderBy('id')
            ->lazyById(200)
            ->map(fn (Ticket $ticket) => [
                $ticket->number,
                $ticket->title,
                $ticket->type_label,
                $ticket->priority_label,
                $ticket->status_label,
                $ticket->sla_label,
                $ticket->due_at?->timezone(config('app.timezone'))->format('Y-m-d H:i'),
                $ticket->requester?->name,
                $ticket->assignee?->name,
                $ticket->asset
                    ? $ticket->asset->inventory_number.' — '.$ticket->asset->name
                    : null,
                $ticket->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i'),
                $ticket->resolved_at?->timezone(config('app.timezone'))->format('Y-m-d H:i'),
                $ticket->closed_at?->timezone(config('app.timezone'))->format('Y-m-d H:i'),
                $ticket->satisfaction_rating,
                $ticket->satisfaction_label,
                $ticket->satisfaction_comment,
            ]);

        return CsvExporter::download($filename, [
            'Numéro',
            'Titre',
            'Type',
            'Priorité',
            'Statut',
            'SLA',
            'Échéance',
            'Demandeur',
            'Assigné à',
            'Équipement',
            'Créé le',
            'Résolu le',
            'Clôturé le',
            'Satisfaction (1-5)',
            'Satisfaction libellé',
            'Commentaire satisfaction',
        ], $rows);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();

        $assets = ($user->isTechnician()
            ? Asset::query()->orderBy('name')
            : $user->assets()->orderBy('name'))
            ->get(['id', 'name', 'inventory_number'])
            ->map(fn (Asset $asset) => $asset->setAppends([]));

        return Inertia::render('Tickets/Create', [
            'types' => collect(TicketType::cases())->map(fn (TicketType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
                'description' => $type->description(),
            ]),
            'priorities' => $this->priorityOptions(),
            'catalog' => ServiceCatalogItem::query()
                ->active()
                ->get()
                ->map(fn (ServiceCatalogItem $item) => [
                    'id' => $item->id,
                    'code' => $item->code,
                    'name' => $item->name,
                    'description' => $item->description,
                    'type' => $item->type->value,
                    'default_priority' => $item->default_priority->value,
                    'sla_hours' => $item->sla_hours,
                    'type_label' => $item->type_label,
                    'priority_label' => $item->priority_label,
                    'sla_label' => $item->sla_label,
                    'requires_approval' => $item->requires_approval,
                ]),
            'assets' => $assets,
            'aiEnabled' => app(TicketSuggestionService::class)->isConfigured(),
            'aiLlmEnabled' => app(TicketSuggestionService::class)->llmAvailable(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $assetRule = Rule::exists('assets', 'id');
        if (! $user->isTechnician()) {
            $assetRule = $assetRule->where('user_id', $user->id);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'type' => ['required', Rule::enum(TicketType::class)],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'asset_id' => ['nullable', $assetRule],
            'service_catalog_item_id' => [
                'nullable',
                Rule::exists('service_catalog_items', 'id')->where('is_active', true),
            ],
        ]);

        $catalog = null;
        if (! empty($validated['service_catalog_item_id'])) {
            $catalog = ServiceCatalogItem::query()->find($validated['service_catalog_item_id']);
            if ($catalog) {
                $validated['type'] = $catalog->type->value;
                $validated['priority'] = $catalog->default_priority->value;
            }
        }

        $ticket = new Ticket([
            ...$validated,
            'requester_id' => $user->id,
            'entity_id' => $user->entity_id,
        ]);

        if ($catalog) {
            $ticket->due_at = now()->addHours((int) $catalog->sla_hours);
            if ($catalog->requires_approval) {
                $ticket->approval_status = ApprovalStatus::Pending;
            }
        }

        $ticket->save();

        $ticket->load(['requester:id,name,email', 'asset:id,name']);

        app(AssetHistoryLogger::class)->ticketOpened($ticket, $user);

        $recipients = User::query()
            ->whereIn('role', [UserRole::Technician->value, UserRole::Admin->value])
            ->whereKeyNot($user->id)
            ->get();

        Notification::send($recipients, new TicketCreatedNotification($ticket));

        return redirect()->route('tickets.show', $ticket);
    }

    public function show(Request $request, Ticket $ticket): Response
    {
        $this->authorizeView($request, $ticket);

        $ticket->load([
            'requester:id,name,email',
            'assignee:id,name,email',
            'asset:id,name,inventory_number,type,status',
            'comments.user:id,name',
            'documents.uploader:id,name',
            'tasks.assignee:id,name',
            'approver:id,name',
            'serviceCatalogItem:id,name,code,requires_approval',
        ]);

        $aiAssist = app(TicketResolutionAssistService::class);
        $suggestedAssignee = app(SmartAssigneeSuggester::class)->suggest($ticket);

        return Inertia::render('Tickets/Show', [
            'ticket' => $ticket,
            'technicians' => User::query()
                ->whereIn('role', [UserRole::Technician->value, UserRole::Admin->value])
                ->where('is_active', true)
                ->withCount([
                    'assignedTickets as open_tickets_count' => fn ($query) => $query->whereIn('status', [
                        TicketStatus::New->value,
                        TicketStatus::Assigned->value,
                        TicketStatus::InProgress->value,
                    ]),
                ])
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (User $tech) => [
                    'id' => $tech->id,
                    'name' => $tech->name,
                    'open_tickets_count' => $tech->open_tickets_count,
                ]),
            'suggested_assignee' => $suggestedAssignee,
            'canManage' => $request->user()->isTechnician(),
            'isRequester' => $ticket->requester_id === $request->user()->id,
            'canRateSatisfaction' => $ticket->requester_id === $request->user()->id
                && $ticket->isSatisfactionPending(),
            'aiEnabled' => $aiAssist->isConfigured(),
            'aiLlmEnabled' => $aiAssist->llmAvailable(),
            'faqCategories' => collect(FaqCategory::cases())->map(
                fn (FaqCategory $category) => [
                    'value' => $category->value,
                    'label' => $category->label(),
                ]
            )->all(),
        ]);
    }

    public function assign(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->guardApproval($ticket);
        $this->guardStatus($ticket, [TicketStatus::New, TicketStatus::Assigned]);

        $validated = $request->validate([
            'assignee_id' => [
                'required',
                Rule::exists('users', 'id')
                    ->whereIn('role', [UserRole::Technician->value, UserRole::Admin->value])
                    ->where('is_active', true),
            ],
        ]);

        $ticket->update([
            ...$validated,
            'status' => TicketStatus::Assigned,
        ]);

        $assignee = User::find($validated['assignee_id']);
        if ($assignee && $assignee->id !== $request->user()->id) {
            $assignee->notify(new TicketAssignedNotification($ticket->fresh()));
        }

        return back();
    }

    public function start(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->guardApproval($ticket);
        $this->guardStatus($ticket, [TicketStatus::New, TicketStatus::Assigned]);

        $previousAssigneeId = $ticket->assignee_id;
        $assigneeId = $ticket->assignee_id ?? $request->user()->id;

        $ticket->update([
            'assignee_id' => $assigneeId,
            'status' => TicketStatus::InProgress,
        ]);

        if ($previousAssigneeId === null && $assigneeId !== $request->user()->id) {
            User::find($assigneeId)?->notify(new TicketAssignedNotification($ticket->fresh()));
        }

        return back();
    }

    public function resolve(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->guardApproval($ticket);
        $this->guardStatus($ticket, [TicketStatus::Assigned, TicketStatus::InProgress]);

        $validated = $request->validate([
            'solution' => ['required', 'string', 'max:10000'],
        ]);

        $ticket->update([
            ...$validated,
            'status' => TicketStatus::Resolved,
            'resolved_at' => now(),
        ]);

        app(AssetHistoryLogger::class)->ticketResolved($ticket->fresh(), $request->user());

        $requester = $ticket->requester;
        if ($requester && $requester->id !== $request->user()->id) {
            $requester->notify(new TicketResolvedNotification($ticket->fresh()));
        }

        return back();
    }

    public function close(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorizeView($request, $ticket);
        $this->guardStatus($ticket, [TicketStatus::Resolved]);

        $ticket->update([
            'status' => TicketStatus::Closed,
            'closed_at' => now(),
        ]);

        $message = $ticket->requester_id === $request->user()->id
            && $ticket->satisfaction_rating === null
            ? 'Ticket clôturé. Merci de noter votre satisfaction ci-dessous.'
            : 'Ticket clôturé.';

        return back()->with('status', $message);
    }

    public function rate(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->requester_id === $request->user()->id, 403);
        abort_unless(
            in_array($ticket->status, [TicketStatus::Resolved, TicketStatus::Closed], true),
            403,
            'La satisfaction ne peut être notée qu’après résolution.'
        );
        abort_if($ticket->satisfaction_rating !== null, 422, 'Ce ticket a déjà été noté.');

        $validated = $request->validate([
            'satisfaction_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'satisfaction_comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $ticket->update([
            'satisfaction_rating' => $validated['satisfaction_rating'],
            'satisfaction_comment' => $validated['satisfaction_comment'] ?? null,
            'satisfaction_rated_at' => now(),
        ]);

        return back()->with('status', 'Merci pour votre retour.');
    }

    public function reopen(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorizeView($request, $ticket);
        $this->guardStatus($ticket, [TicketStatus::Resolved, TicketStatus::Closed]);

        $ticket->update([
            'status' => TicketStatus::InProgress,
            'resolved_at' => null,
            'closed_at' => null,
            'satisfaction_rating' => null,
            'satisfaction_comment' => null,
            'satisfaction_rated_at' => null,
            'satisfaction_reminded_at' => null,
        ]);

        return back();
    }

    private function authorizeView(Request $request, Ticket $ticket): void
    {
        abort_unless(
            $request->user()->isTechnician() || $ticket->requester_id === $request->user()->id,
            403
        );
    }

    /** @return Builder<Ticket> */
    private function filteredQuery(Request $request): Builder
    {
        $user = $request->user();

        return Ticket::query()
            ->when(! $user->isTechnician(), fn ($query) => $query->where('requester_id', $user->id))
            ->when($request->string('search')->trim()->value(), function ($query, string $search) use ($user) {
                $query->where(function ($sub) use ($search, $user) {
                    $sub->where('title', 'ilike', "%{$search}%")
                        ->orWhere('number', 'ilike', "%{$search}%");

                    if ($user->isTechnician()) {
                        $sub->orWhereHas('requester', function ($requester) use ($search) {
                            $requester->where('name', 'ilike', "%{$search}%")
                                ->orWhere('email', 'ilike', "%{$search}%");
                        });
                    }
                });
            })
            ->when($request->string('type')->value(), fn ($query, string $type) => $query->where('type', $type))
            ->when($request->string('priority')->value(), fn ($query, string $priority) => $query->where('priority', $priority))
            ->when($request->string('status')->value(), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->string('sla')->value(), function ($query, string $sla) {
                match ($sla) {
                    'breached' => $query->slaBreached(),
                    'at_risk' => $query->slaAtRisk(),
                    default => null,
                };
            });
    }

    /** @param TicketStatus[] $allowed */
    private function guardStatus(Ticket $ticket, array $allowed): void
    {
        abort_unless(in_array($ticket->status, $allowed), 403, 'Action impossible dans l\'état actuel du ticket.');
    }

    private function guardApproval(Ticket $ticket): void
    {
        abort_unless(
            ! $ticket->isApprovalBlocked(),
            422,
            'Ce ticket doit d’abord être validé (ou a été refusé).'
        );
    }

    public function approve(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->isApprovalPending(), 422);

        $validated = $request->validate([
            'approval_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $ticket->update([
            'approval_status' => ApprovalStatus::Approved,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'approval_note' => $validated['approval_note'] ?? null,
        ]);

        return back()->with('status', 'Demande validée — le ticket peut être traité.');
    }

    public function reject(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->isApprovalPending(), 422);

        $validated = $request->validate([
            'approval_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $ticket->update([
            'approval_status' => ApprovalStatus::Rejected,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'approval_note' => $validated['approval_note'] ?? null,
        ]);

        return back()->with('status', 'Demande refusée.');
    }

    private function typeOptions(): array
    {
        return collect(TicketType::cases())
            ->map(fn (TicketType $type) => ['value' => $type->value, 'label' => $type->label()])
            ->all();
    }

    private function priorityOptions(): array
    {
        return collect(TicketPriority::cases())
            ->map(fn (TicketPriority $priority) => [
                'value' => $priority->value,
                'label' => $priority->label(),
                'sla_hours' => $priority->slaHours(),
                'sla_label' => $priority->slaLabel(),
            ])
            ->all();
    }

    private function statusOptions(): array
    {
        return collect(TicketStatus::cases())
            ->map(fn (TicketStatus $status) => ['value' => $status->value, 'label' => $status->label()])
            ->all();
    }
}
