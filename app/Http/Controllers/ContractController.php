<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Contract;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContractController extends Controller
{
    public function index(Request $request): Response
    {
        $contracts = Contract::query()
            ->with(['supplier:id,name'])
            ->withCount('assets')
            ->when($request->string('search')->trim()->value(), function ($query, string $search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('title', 'ilike', "%{$search}%")
                        ->orWhere('reference', 'ilike', "%{$search}%");
                });
            })
            ->when($request->string('expiry')->value() === 'expired', fn ($q) => $q->expired())
            ->when($request->string('expiry')->value() === 'expiring', fn ($q) => $q->expiringSoon(30))
            ->orderByRaw('ends_on ASC NULLS LAST')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Contracts/Index', [
            'contracts' => $contracts,
            'filters' => $request->only(['search', 'expiry']),
            'canManage' => $request->user()->isTechnician(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Contracts/Form', [
            'contract' => null,
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $contract = Contract::create([
            ...$this->validated($request),
            'entity_id' => $request->user()->entity_id,
        ]);

        return redirect()->route('contracts.show', $contract)->with('status', 'Contrat créé.');
    }

    public function show(Request $request, Contract $contract): Response
    {
        $contract->load(['supplier:id,name,email,phone', 'assets:id,name,inventory_number'])->loadCount('assets');

        return Inertia::render('Contracts/Show', [
            'contract' => $contract,
            'availableAssets' => $request->user()->isTechnician()
                ? Asset::query()
                    ->whereDoesntHave('contracts', fn ($q) => $q->where('contracts.id', $contract->id))
                    ->orderBy('name')
                    ->get(['id', 'name', 'inventory_number'])
                : [],
            'canManage' => $request->user()->isTechnician(),
        ]);
    }

    public function edit(Contract $contract): Response
    {
        return Inertia::render('Contracts/Form', [
            'contract' => $contract,
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Contract $contract): RedirectResponse
    {
        $contract->update($this->validated($request));

        return redirect()->route('contracts.show', $contract)->with('status', 'Contrat mis à jour.');
    }

    public function destroy(Contract $contract): RedirectResponse
    {
        $contract->delete();

        return redirect()->route('contracts.index')->with('status', 'Contrat supprimé.');
    }

    public function attachAsset(Request $request, Contract $contract): RedirectResponse
    {
        $validated = $request->validate([
            'asset_id' => ['required', 'exists:assets,id'],
        ]);

        $contract->assets()->syncWithoutDetaching([
            $validated['asset_id'] => ['linked_at' => now()],
        ]);

        return back()->with('status', 'Équipement lié au contrat.');
    }

    public function detachAsset(Contract $contract, Asset $asset): RedirectResponse
    {
        $contract->assets()->detach($asset->id);

        return back()->with('status', 'Lien retiré.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['currency'] = strtoupper($validated['currency'] ?? 'MAD');

        return $validated;
    }
}
