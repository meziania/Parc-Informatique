<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function index(Request $request): Response
    {
        $suppliers = Supplier::query()
            ->withCount('contracts')
            ->when($request->string('search')->trim()->value(), function ($query, string $search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'ilike', "%{$search}%")
                        ->orWhere('contact_name', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Suppliers/Index', [
            'suppliers' => $suppliers,
            'filters' => $request->only(['search']),
            'canManage' => $request->user()->isTechnician(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Suppliers/Form', ['supplier' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $supplier = Supplier::create([
            ...$this->validated($request),
            'entity_id' => $request->user()->entity_id,
        ]);

        return redirect()->route('suppliers.show', $supplier)->with('status', 'Fournisseur créé.');
    }

    public function show(Supplier $supplier): Response
    {
        $supplier->load(['contracts' => fn ($q) => $q->latest()->limit(20)]);

        return Inertia::render('Suppliers/Show', [
            'supplier' => $supplier->loadCount('contracts'),
            'canManage' => request()->user()->isTechnician(),
        ]);
    }

    public function edit(Supplier $supplier): Response
    {
        return Inertia::render('Suppliers/Form', ['supplier' => $supplier]);
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validated($request));

        return redirect()->route('suppliers.show', $supplier)->with('status', 'Fournisseur mis à jour.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->delete();

        return redirect()->route('suppliers.index')->with('status', 'Fournisseur supprimé.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        return $validated;
    }
}
