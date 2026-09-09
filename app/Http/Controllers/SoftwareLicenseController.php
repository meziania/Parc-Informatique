<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\SoftwareLicense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SoftwareLicenseController extends Controller
{
    public function index(Request $request): Response
    {
        $licenses = SoftwareLicense::query()
            ->withCount('assets')
            ->when($request->string('search')->trim()->value(), function ($query, string $search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'ilike', "%{$search}%")
                        ->orWhere('vendor', 'ilike', "%{$search}%");
                });
            })
            ->when($request->string('expiry')->value() === 'expired', fn ($q) => $q->expired())
            ->when($request->string('expiry')->value() === 'expiring', fn ($q) => $q->expiringSoon(30))
            ->orderByRaw('expiry_date ASC NULLS LAST')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Licenses/Index', [
            'licenses' => $licenses,
            'filters' => $request->only(['search', 'expiry']),
            'canManage' => $request->user()->isTechnician(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Licenses/Form', [
            'license' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $license = SoftwareLicense::create([
            ...$this->validated($request),
            'entity_id' => $request->user()->entity_id,
        ]);

        return redirect()
            ->route('licenses.show', $license)
            ->with('status', 'Licence créée.');
    }

    public function show(Request $request, SoftwareLicense $license): Response
    {
        $license->load([
            'assets:id,name,inventory_number,type,status',
        ])->loadCount('assets');

        return Inertia::render('Licenses/Show', [
            'license' => $license,
            'availableAssets' => $request->user()->isTechnician()
                ? Asset::query()
                    ->whereDoesntHave('licenses', fn ($q) => $q->where('software_licenses.id', $license->id))
                    ->orderBy('name')
                    ->get(['id', 'name', 'inventory_number'])
                : [],
            'canManage' => $request->user()->isTechnician(),
        ]);
    }

    public function edit(SoftwareLicense $license): Response
    {
        return Inertia::render('Licenses/Form', [
            'license' => $license,
        ]);
    }

    public function update(Request $request, SoftwareLicense $license): RedirectResponse
    {
        $license->update($this->validated($request));

        return redirect()
            ->route('licenses.show', $license)
            ->with('status', 'Licence mise à jour.');
    }

    public function destroy(SoftwareLicense $license): RedirectResponse
    {
        $license->delete();

        return redirect()
            ->route('licenses.index')
            ->with('status', 'Licence supprimée.');
    }

    public function attachAsset(Request $request, SoftwareLicense $license): RedirectResponse
    {
        $validated = $request->validate([
            'asset_id' => ['required', 'exists:assets,id'],
        ]);

        if ($license->seats_available < 1) {
            throw ValidationException::withMessages([
                'asset_id' => 'Plus de sièges disponibles pour cette licence.',
            ]);
        }

        if ($license->assets()->where('assets.id', $validated['asset_id'])->exists()) {
            throw ValidationException::withMessages([
                'asset_id' => 'Cet équipement a déjà cette licence.',
            ]);
        }

        $license->assets()->attach($validated['asset_id'], [
            'assigned_at' => now(),
        ]);

        return back()->with('status', 'Licence affectée à l’équipement.');
    }

    public function detachAsset(SoftwareLicense $license, Asset $asset): RedirectResponse
    {
        $license->assets()->detach($asset->id);

        return back()->with('status', 'Affectation retirée.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'product_key' => ['nullable', 'string', 'max:255'],
            'seats' => ['required', 'integer', 'min:1', 'max:100000'],
            'purchase_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:purchase_date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        return $validated;
    }
}
