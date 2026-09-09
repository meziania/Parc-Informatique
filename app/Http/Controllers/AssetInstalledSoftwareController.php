<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetInstalledSoftware;
use App\Models\SoftwareLicense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AssetInstalledSoftwareController extends Controller
{
    public function store(Request $request, Asset $asset): RedirectResponse
    {
        abort_unless($request->user()->isTechnician(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'version' => ['nullable', 'string', 'max:100'],
            'software_license_id' => ['nullable', 'exists:software_licenses,id'],
            'installed_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $asset->installedSoftwares()->create($validated);

        return back()->with('status', 'Logiciel ajouté à l’inventaire.');
    }

    public function destroy(Request $request, Asset $asset, AssetInstalledSoftware $software): RedirectResponse
    {
        abort_unless($request->user()->isTechnician(), 403);
        abort_unless($software->asset_id === $asset->id, 404);

        $software->delete();

        return back()->with('status', 'Logiciel retiré.');
    }
}
