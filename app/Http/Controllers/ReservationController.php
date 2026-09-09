<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Models\Asset;
use App\Models\Location;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ReservationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $canManage = $user->isTechnician();

        $reservations = Reservation::query()
            ->with([
                'user:id,name',
                'asset:id,name,inventory_number',
                'location:id,name,building,room',
                'reviewer:id,name',
            ])
            ->when(! $canManage, fn ($q) => $q->where('user_id', $user->id))
            ->when($request->string('status')->value(), fn ($q, string $status) => $q->where('status', $status))
            ->when($request->string('scope')->value() === 'upcoming', function ($q) {
                $q->where('ends_at', '>=', now())
                    ->whereIn('status', [
                        ReservationStatus::Pending->value,
                        ReservationStatus::Approved->value,
                    ]);
            })
            ->latest('starts_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Reservations/Index', [
            'reservations' => $reservations,
            'filters' => $request->only(['status', 'scope']),
            'statuses' => $this->statusOptions(),
            'canManage' => $canManage,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Reservations/Form', [
            'assets' => Asset::query()
                ->whereIn('type', ['computer', 'peripheral'])
                ->orderBy('name')
                ->get(['id', 'name', 'inventory_number', 'type']),
            'locations' => Location::orderBy('name')->get(['id', 'name', 'building', 'room']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $this->assertNoConflict(
            $validated['asset_id'] ?? null,
            $validated['location_id'] ?? null,
            $validated['starts_at'],
            $validated['ends_at'],
        );

        $reservation = Reservation::create([
            ...$validated,
            'user_id' => $request->user()->id,
            'status' => ReservationStatus::Pending,
        ]);

        return redirect()
            ->route('reservations.show', $reservation)
            ->with('status', 'Demande de réservation envoyée.');
    }

    public function show(Request $request, Reservation $reservation): Response
    {
        $this->authorizeView($request, $reservation);

        $reservation->load([
            'user:id,name,email',
            'asset:id,name,inventory_number,type,status',
            'location:id,name,building,room',
            'reviewer:id,name',
        ]);

        return Inertia::render('Reservations/Show', [
            'reservation' => $reservation,
            'canManage' => $request->user()->isTechnician(),
            'isOwner' => $reservation->user_id === $request->user()->id,
        ]);
    }

    public function approve(Request $request, Reservation $reservation): RedirectResponse
    {
        abort_unless($reservation->status === ReservationStatus::Pending, 422);

        $this->assertNoConflict(
            $reservation->asset_id,
            $reservation->location_id,
            $reservation->starts_at,
            $reservation->ends_at,
            $reservation->id,
        );

        $reservation->update([
            'status' => ReservationStatus::Approved,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_note' => $request->validate([
                'review_note' => ['nullable', 'string', 'max:2000'],
            ])['review_note'] ?? null,
        ]);

        return back()->with('status', 'Réservation approuvée.');
    }

    public function reject(Request $request, Reservation $reservation): RedirectResponse
    {
        abort_unless($reservation->status === ReservationStatus::Pending, 422);

        $validated = $request->validate([
            'review_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $reservation->update([
            'status' => ReservationStatus::Rejected,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_note' => $validated['review_note'] ?? null,
        ]);

        return back()->with('status', 'Réservation refusée.');
    }

    public function cancel(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->authorizeView($request, $reservation);

        abort_unless(
            in_array($reservation->status, [ReservationStatus::Pending, ReservationStatus::Approved], true),
            422
        );

        $isOwner = $reservation->user_id === $request->user()->id;
        abort_unless($isOwner || $request->user()->isTechnician(), 403);

        $reservation->update([
            'status' => ReservationStatus::Cancelled,
            'reviewed_by' => $request->user()->isTechnician() ? $request->user()->id : $reservation->reviewed_by,
            'reviewed_at' => now(),
        ]);

        return back()->with('status', 'Réservation annulée.');
    }

    private function authorizeView(Request $request, Reservation $reservation): void
    {
        abort_unless(
            $request->user()->isTechnician() || $reservation->user_id === $request->user()->id,
            403
        );
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'purpose' => ['nullable', 'string', 'max:2000'],
            'asset_id' => ['nullable', 'exists:assets,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ]);

        if (empty($validated['asset_id']) && empty($validated['location_id'])) {
            throw ValidationException::withMessages([
                'asset_id' => 'Choisissez un équipement ou un lieu.',
            ]);
        }

        return $validated;
    }

    private function assertNoConflict(
        ?int $assetId,
        ?int $locationId,
        mixed $startsAt,
        mixed $endsAt,
        ?int $ignoreId = null,
    ): void {
        $query = Reservation::query()->overlapping($startsAt, $endsAt, $ignoreId);

        if ($assetId) {
            $query->where('asset_id', $assetId);
        } elseif ($locationId) {
            $query->where('location_id', $locationId)->whereNull('asset_id');
        } else {
            return;
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'starts_at' => 'Conflit : une réservation existe déjà sur ce créneau.',
            ]);
        }
    }

    private function statusOptions(): array
    {
        return collect(ReservationStatus::cases())
            ->map(fn (ReservationStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ])
            ->all();
    }
}
