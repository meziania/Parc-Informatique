<?php

namespace App\Http\Controllers;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\Location;
use App\Models\Ticket;
use App\Models\User;
use App\Support\CsvExporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $assets = $this->filteredQuery($request)
            ->with(['location:id,name,building,room', 'user:id,name'])
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Assets/Index', [
            'assets' => $assets,
            'filters' => $request->only(['search', 'type', 'status']),
            'types' => $this->typeOptions(),
            'statuses' => $this->statusOptions(),
            'canManage' => $user->isTechnician(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filename = 'parc_'.now()->format('Y-m-d_His').'.csv';

        $rows = $this->filteredQuery($request)
            ->with(['location:id,name,building,room', 'user:id,name'])
            ->orderBy('id')
            ->lazyById(200)
            ->map(fn (Asset $asset) => [
                $asset->inventory_number,
                $asset->name,
                $asset->type_label,
                $asset->status_label,
                $asset->serial_number,
                $asset->manufacturer,
                $asset->model,
                $asset->purchase_date?->format('Y-m-d'),
                $asset->warranty_end?->format('Y-m-d'),
                $asset->next_maintenance_at?->format('Y-m-d'),
                $asset->user?->name,
                $asset->location
                    ? collect([$asset->location->name, $asset->location->building, $asset->location->room])
                        ->filter()
                        ->implode(' — ')
                    : null,
                $asset->notes,
            ]);

        return CsvExporter::download($filename, [
            'N° inventaire',
            'Nom',
            'Type',
            'Statut',
            'N° série',
            'Fabricant',
            'Modèle',
            'Date achat',
            'Fin garantie',
            'Prochaine maintenance',
            'Affecté à',
            'Lieu',
            'Notes',
        ], $rows);
    }

    public function create(): Response
    {
        return Inertia::render('Assets/Form', [
            'asset' => null,
            ...$this->formData(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $asset = Asset::create([
            ...$this->validated($request),
            'entity_id' => $request->user()->entity_id,
        ]);

        return redirect()->route('assets.show', $asset);
    }

    public function show(Request $request, Asset $asset): Response
    {
        $this->authorizeView($request, $asset);

        return Inertia::render('Assets/Show', [
            'asset' => $asset->load([
                'location:id,name,building,room',
                'user:id,name,email',
                'documents.uploader:id,name',
                'licenses:id,name,vendor,expiry_date,seats',
                'contracts:id,title,reference,ends_on',
                'installedSoftwares.license:id,name',
            ]),
            'events' => $asset->events()
                ->with('actor:id,name')
                ->limit(50)
                ->get()
                ->map(fn ($event) => [
                    'id' => $event->id,
                    'event_type' => $event->event_type,
                    'title' => $event->title,
                    'body' => $event->body,
                    'meta' => $event->meta,
                    'created_at' => $event->created_at?->toIso8601String(),
                    'actor' => $event->actor
                        ? ['id' => $event->actor->id, 'name' => $event->actor->name]
                        : null,
                    'ticket_id' => ($event->related_type === Ticket::class)
                        ? $event->related_id
                        : ($event->meta['ticket_id'] ?? null),
                ]),
            'availableLicenses' => $request->user()->isTechnician()
                ? \App\Models\SoftwareLicense::query()
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name'])
                : [],
            'canManage' => $request->user()->isTechnician(),
        ]);
    }

    public function edit(Asset $asset): Response
    {
        return Inertia::render('Assets/Form', [
            'asset' => $asset,
            ...$this->formData(),
        ]);
    }

    public function update(Request $request, Asset $asset): RedirectResponse
    {
        $asset->update($this->validated($request, $asset));

        return redirect()->route('assets.show', $asset);
    }

    public function destroy(Asset $asset): RedirectResponse
    {
        $asset->delete();

        return redirect()->route('assets.index');
    }

    private function authorizeView(Request $request, Asset $asset): void
    {
        abort_unless(
            $request->user()->isTechnician() || $asset->user_id === $request->user()->id,
            403
        );
    }

    /** @return Builder<Asset> */
    private function filteredQuery(Request $request): Builder
    {
        $user = $request->user();

        return Asset::query()
            ->when(! $user->isTechnician(), fn ($query) => $query->where('user_id', $user->id))
            ->when($request->string('search')->trim()->value(), function ($query, string $search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'ilike', "%{$search}%")
                        ->orWhere('inventory_number', 'ilike', "%{$search}%")
                        ->orWhere('serial_number', 'ilike', "%{$search}%");
                });
            })
            ->when($request->string('type')->value(), fn ($query, string $type) => $query->where('type', $type))
            ->when($request->string('status')->value(), fn ($query, string $status) => $query->where('status', $status));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Asset $asset = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(AssetType::class)],
            'status' => ['required', Rule::enum(AssetStatus::class)],
            'inventory_number' => [
                'required', 'string', 'max:255',
                Rule::unique('assets')->ignore($asset),
            ],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['nullable', 'date'],
            'warranty_end' => ['nullable', 'date', 'after_or_equal:purchase_date'],
            'next_maintenance_at' => ['nullable', 'date'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'user_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'locations' => Location::orderBy('name')->get(['id', 'name', 'building', 'room']),
            'users' => User::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'types' => $this->typeOptions(),
            'statuses' => $this->statusOptions(),
        ];
    }

    private function typeOptions(): array
    {
        return collect(AssetType::cases())
            ->map(fn (AssetType $type) => ['value' => $type->value, 'label' => $type->label()])
            ->all();
    }

    private function statusOptions(): array
    {
        return collect(AssetStatus::cases())
            ->map(fn (AssetStatus $status) => ['value' => $status->value, 'label' => $status->label()])
            ->all();
    }
}
