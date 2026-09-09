<?php

namespace App\Http\Controllers;

use App\Enums\ConsumableCategory;
use App\Models\Consumable;
use App\Models\Location;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ConsumableController extends Controller
{
    public function index(Request $request): Response
    {
        $consumables = Consumable::query()
            ->with(['location:id,name', 'supplier:id,name'])
            ->when($request->string('search')->trim()->value(), function ($query, string $search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'ilike', "%{$search}%")
                        ->orWhere('sku', 'ilike', "%{$search}%");
                });
            })
            ->when($request->string('category')->value(), fn ($q, $cat) => $q->where('category', $cat))
            ->when($request->string('stock')->value() === 'low', fn ($q) => $q->lowStock())
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Consumables/Index', [
            'consumables' => $consumables,
            'filters' => $request->only(['search', 'category', 'stock']),
            'categories' => $this->categoryOptions(),
            'canManage' => $request->user()->isTechnician(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Consumables/Form', [
            'consumable' => null,
            ...$this->formData(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Consumable::create([
            ...$this->validated($request),
            'entity_id' => $request->user()->entity_id,
        ]);

        return redirect()->route('consumables.index')->with('status', 'Consommable créé.');
    }

    public function edit(Consumable $consumable): Response
    {
        return Inertia::render('Consumables/Form', [
            'consumable' => $consumable,
            ...$this->formData(),
        ]);
    }

    public function update(Request $request, Consumable $consumable): RedirectResponse
    {
        $consumable->update($this->validated($request));

        return redirect()->route('consumables.index')->with('status', 'Consommable mis à jour.');
    }

    public function destroy(Consumable $consumable): RedirectResponse
    {
        $consumable->delete();

        return redirect()->route('consumables.index')->with('status', 'Consommable supprimé.');
    }

    public function adjust(Request $request, Consumable $consumable): RedirectResponse
    {
        $validated = $request->validate([
            'delta' => ['required', 'integer', 'not_in:0'],
        ]);

        $newQty = max(0, $consumable->quantity + (int) $validated['delta']);
        $consumable->update(['quantity' => $newQty]);

        return back()->with('status', 'Stock mis à jour.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'category' => ['required', Rule::enum(ConsumableCategory::class)],
            'quantity' => ['required', 'integer', 'min:0'],
            'min_quantity' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:30'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
    }

    private function formData(): array
    {
        return [
            'categories' => $this->categoryOptions(),
            'locations' => Location::orderBy('name')->get(['id', 'name']),
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ];
    }

    private function categoryOptions(): array
    {
        return collect(ConsumableCategory::cases())
            ->map(fn (ConsumableCategory $c) => ['value' => $c->value, 'label' => $c->label()])
            ->all();
    }
}
