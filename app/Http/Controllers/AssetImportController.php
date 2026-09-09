<?php

namespace App\Http\Controllers;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Models\Asset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetImportController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Assets/Import', [
            'types' => collect(AssetType::cases())->map(fn (AssetType $t) => [
                'value' => $t->value,
                'label' => $t->label(),
            ]),
            'statuses' => collect(AssetStatus::cases())->map(fn (AssetStatus $s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
        ]);
    }

    public function template(): StreamedResponse
    {
        $headers = [
            'N° inventaire',
            'Nom',
            'Type',
            'Statut',
            'N° série',
            'Fabricant',
            'Modèle',
            'Date achat',
            'Fin garantie',
            'Notes',
        ];

        $example = [
            'INV-IMPORT-001',
            'PC Portable Demo',
            'computer',
            'in_stock',
            'SN-DEMO-001',
            'Dell',
            'Latitude 5550',
            '2025-01-15',
            '2028-01-14',
            'Import CSV démo',
        ];

        return response()->streamDownload(function () use ($headers, $example) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers, ';');
            fputcsv($out, $example, ';');
            fclose($out);
        }, 'modele_import_parc.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            throw ValidationException::withMessages([
                'file' => 'Impossible de lire le fichier.',
            ]);
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            throw ValidationException::withMessages(['file' => 'Fichier vide.']);
        }

        // Skip BOM
        $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine) ?? $firstLine;
        $delimiter = substr_count($firstLine, ';') >= substr_count($firstLine, ',') ? ';' : ',';
        $headers = str_getcsv(trim($firstLine), $delimiter);

        $map = $this->headerMap($headers);
        if (! isset($map['inventory_number'], $map['name'], $map['type'], $map['status'])) {
            fclose($handle);
            throw ValidationException::withMessages([
                'file' => 'Colonnes obligatoires manquantes : N° inventaire, Nom, Type, Statut.',
            ]);
        }

        $created = 0;
        $updated = 0;
        $errors = [];
        $rowNum = 1;
        $entityId = $request->user()->entity_id;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rowNum++;
                if ($this->rowEmpty($row)) {
                    continue;
                }

                try {
                    $payload = [
                        'inventory_number' => trim((string) ($row[$map['inventory_number']] ?? '')),
                        'name' => trim((string) ($row[$map['name']] ?? '')),
                        'type' => $this->normalizeType(trim((string) ($row[$map['type']] ?? ''))),
                        'status' => $this->normalizeStatus(trim((string) ($row[$map['status']] ?? ''))),
                        'serial_number' => $this->optional($row, $map, 'serial_number'),
                        'manufacturer' => $this->optional($row, $map, 'manufacturer'),
                        'model' => $this->optional($row, $map, 'model'),
                        'purchase_date' => $this->optional($row, $map, 'purchase_date'),
                        'warranty_end' => $this->optional($row, $map, 'warranty_end'),
                        'notes' => $this->optional($row, $map, 'notes'),
                        'entity_id' => $entityId,
                    ];

                    validator($payload, [
                        'inventory_number' => ['required', 'string', 'max:255'],
                        'name' => ['required', 'string', 'max:255'],
                        'type' => ['required', Rule::enum(AssetType::class)],
                        'status' => ['required', Rule::enum(AssetStatus::class)],
                        'serial_number' => ['nullable', 'string', 'max:255'],
                        'manufacturer' => ['nullable', 'string', 'max:255'],
                        'model' => ['nullable', 'string', 'max:255'],
                        'purchase_date' => ['nullable', 'date'],
                        'warranty_end' => ['nullable', 'date'],
                        'notes' => ['nullable', 'string', 'max:5000'],
                    ])->validate();

                    $existing = Asset::query()
                        ->where('inventory_number', $payload['inventory_number'])
                        ->first();

                    if ($existing) {
                        $existing->update($payload);
                        $updated++;
                    } else {
                        Asset::create($payload);
                        $created++;
                    }
                } catch (\Throwable $e) {
                    $errors[] = "Ligne {$rowNum} : ".$e->getMessage();
                    if (count($errors) >= 20) {
                        break;
                    }
                }
            }

            if (count($errors) > 0 && $created === 0 && $updated === 0) {
                DB::rollBack();
                fclose($handle);
                throw ValidationException::withMessages([
                    'file' => implode(' | ', array_slice($errors, 0, 5)),
                ]);
            }

            DB::commit();
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            throw ValidationException::withMessages([
                'file' => 'Import interrompu : '.$e->getMessage(),
            ]);
        }

        fclose($handle);

        $message = "Import terminé : {$created} créé(s), {$updated} mis à jour.";
        if ($errors !== []) {
            $message .= ' '.count($errors).' ligne(s) en erreur.';
        }

        return redirect()->route('assets.index')->with('status', $message);
    }

    /**
     * @param  list<string|null>  $headers
     * @return array<string, int>
     */
    private function headerMap(array $headers): array
    {
        $aliases = [
            'inventory_number' => ['n° inventaire', 'n inventaire', 'inventaire', 'inventory_number', 'inventory'],
            'name' => ['nom', 'name'],
            'type' => ['type'],
            'status' => ['statut', 'status'],
            'serial_number' => ['n° série', 'n serie', 'serial', 'serial_number'],
            'manufacturer' => ['fabricant', 'manufacturer', 'marque'],
            'model' => ['modèle', 'modele', 'model'],
            'purchase_date' => ['date achat', 'purchase_date', 'achat'],
            'warranty_end' => ['fin garantie', 'warranty_end', 'garantie'],
            'notes' => ['notes', 'note', 'commentaire'],
        ];

        $map = [];
        foreach ($headers as $index => $header) {
            $normalized = mb_strtolower(trim((string) $header));
            foreach ($aliases as $field => $names) {
                if (in_array($normalized, $names, true)) {
                    $map[$field] = $index;
                }
            }
        }

        return $map;
    }

    private function optional(array $row, array $map, string $field): ?string
    {
        if (! isset($map[$field])) {
            return null;
        }

        $value = trim((string) ($row[$map[$field]] ?? ''));

        return $value === '' ? null : $value;
    }

    private function rowEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    private function normalizeType(string $value): string
    {
        $value = mb_strtolower($value);
        $labels = collect(AssetType::cases())->mapWithKeys(
            fn (AssetType $t) => [mb_strtolower($t->label()) => $t->value]
        );

        return $labels[$value] ?? $value;
    }

    private function normalizeStatus(string $value): string
    {
        $value = mb_strtolower($value);
        $labels = collect(AssetStatus::cases())->mapWithKeys(
            fn (AssetStatus $s) => [mb_strtolower($s->label()) => $s->value]
        );

        return $labels[$value] ?? $value;
    }
}
