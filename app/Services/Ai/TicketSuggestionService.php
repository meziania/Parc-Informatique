<?php

namespace App\Services\Ai;

use App\Enums\TicketPriority;
use App\Enums\TicketType;
use App\Models\Asset;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class TicketSuggestionService
{
    public function __construct(
        private AiClient $ai,
        private FaqRagService $faqRag,
    ) {}

    /**
     * @param  Collection<int, Asset>|list<Asset>  $assets
     * @return array{
     *     title: string,
     *     description: string,
     *     type: string,
     *     priority: string,
     *     asset_id: int|null,
     *     rationale: string,
     *     faq_suggestions: list<array{id: int, title: string, category_label: string}>,
     *     provider: string
     * }
     */
    public function suggest(string $message, iterable $assets = []): array
    {
        $assets = Collection::make($assets)->values();
        $heuristic = $this->heuristic($message, $assets);
        $faq = $this->faqRag->search($message);

        if ($this->llmAvailable()) {
            try {
                $llm = $this->fromLlm($message, $assets, $faq);
                $merged = $this->merge($heuristic, $llm, $assets);
                $merged['faq_suggestions'] = $this->mapFaq($faq);
                $merged['provider'] = $this->ai->providerLabel();

                return $merged;
            } catch (Throwable $e) {
                Log::warning('AI ticket suggestion failed, using heuristic.', [
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $heuristic['faq_suggestions'] = $this->mapFaq($faq);
        $heuristic['provider'] = $this->ai->llmAvailable() ? 'heuristic_fallback' : 'heuristic';

        return $heuristic;
    }

    public function isConfigured(): bool
    {
        return (bool) config('ai.enabled');
    }

    public function llmAvailable(): bool
    {
        return $this->ai->llmAvailable();
    }

    /**
     * @param  Collection<int, Asset>  $assets
     * @param  list<array<string, mixed>>  $faq
     * @return array<string, mixed>
     */
    private function fromLlm(string $message, Collection $assets, array $faq): array
    {
        $assetList = $assets->map(fn (Asset $asset) => [
            'id' => $asset->id,
            'name' => $asset->name,
            'inventory_number' => $asset->inventory_number,
        ])->all();

        $system = <<<'PROMPT'
Tu es un assistant ITSM pour une plateforme de parc informatique francophone.
À partir du message utilisateur et des articles FAQ pertinents (RAG), propose un ticket structuré.
Réponds UNIQUEMENT en JSON valide avec les clés :
- title (string, court, clair)
- description (string, reformulée, structurée, en français)
- type ("incident" ou "request")
- priority ("low", "medium", "high", "urgent")
- asset_id (number|null, uniquement un id de la liste fournie)
- rationale (string, 1-2 phrases expliquant tes choix ; mentionne la FAQ si utile)
Ne invente pas d'équipement hors liste. Si aucun ne correspond, asset_id = null.
PROMPT;

        return $this->ai->chatJson([
            ['role' => 'system', 'content' => $system],
            [
                'role' => 'user',
                'content' => json_encode([
                    'message' => $message,
                    'assets' => $assetList,
                    'faq_context' => $faq,
                    'types' => ['incident', 'request'],
                    'priorities' => ['low', 'medium', 'high', 'urgent'],
                ], JSON_UNESCAPED_UNICODE),
            ],
        ], 0.2);
    }

    /**
     * @param  Collection<int, Asset>  $assets
     * @return array<string, mixed>
     */
    private function heuristic(string $message, Collection $assets): array
    {
        $text = Str::of($message)->lower()->toString();

        $incidentScore = $this->score($text, [
            'panne', 'erreur', 'bug', 'plante', 'planté', 'crash', 'lent', 'ne marche',
            'ne fonctionne', 'écran', 'imprimante', 'réseau', 'wifi', 'vpn', 'virus',
            'bloqué', 'indisponible', 'coupure', 'hs',
        ]);
        $requestScore = $this->score($text, [
            'besoin', 'demande', 'installer', 'installation', 'accès', 'nouveau',
            'créer', 'compte', 'licence', 'matériel', 'commander', 'demande de',
        ]);

        $type = $requestScore > $incidentScore
            ? TicketType::Request->value
            : TicketType::Incident->value;

        $priority = TicketPriority::Medium->value;
        if ($this->score($text, ['urgent', 'urgence', 'critique', 'bloqué', 'production', 'immédiat']) > 0) {
            $priority = TicketPriority::Urgent->value;
        } elseif ($this->score($text, ['important', 'grave', 'rapidement', 'asap']) > 0) {
            $priority = TicketPriority::High->value;
        } elseif ($this->score($text, ['quand possible', 'pas pressé', 'faible']) > 0) {
            $priority = TicketPriority::Low->value;
        }

        $title = Str::of($message)
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->explode('.')
            ->first();
        $title = Str::limit((string) $title, 80, '');

        if ($title === '') {
            $title = $type === TicketType::Request->value
                ? 'Nouvelle demande'
                : 'Nouvel incident';
        }

        $description = trim($message);
        if (! str_contains($description, "\n")) {
            $description = "Contexte signalé par l'utilisateur :\n\n".$description;
        }

        $assetId = $this->matchAsset($text, $assets);

        $typeLabel = TicketType::from($type)->label();
        $priorityLabel = TicketPriority::from($priority)->label();

        return [
            'title' => $title,
            'description' => $description,
            'type' => $type,
            'priority' => $priority,
            'asset_id' => $assetId,
            'rationale' => "Suggestion locale : type « {$typeLabel} », priorité « {$priorityLabel} » d'après les termes détectés.",
        ];
    }

    /**
     * @param  array<string, mixed>  $heuristic
     * @param  array<string, mixed>  $llm
     * @param  Collection<int, Asset>  $assets
     * @return array<string, mixed>
     */
    private function merge(array $heuristic, array $llm, Collection $assets): array
    {
        $type = in_array($llm['type'] ?? null, ['incident', 'request'], true)
            ? $llm['type']
            : $heuristic['type'];

        $priority = in_array($llm['priority'] ?? null, ['low', 'medium', 'high', 'urgent'], true)
            ? $llm['priority']
            : $heuristic['priority'];

        $assetId = $llm['asset_id'] ?? null;
        if ($assetId !== null && ! $assets->contains(fn (Asset $asset) => $asset->id === (int) $assetId)) {
            $assetId = $heuristic['asset_id'];
        }

        return [
            'title' => filled($llm['title'] ?? null) ? (string) $llm['title'] : $heuristic['title'],
            'description' => filled($llm['description'] ?? null) ? (string) $llm['description'] : $heuristic['description'],
            'type' => $type,
            'priority' => $priority,
            'asset_id' => $assetId !== null ? (int) $assetId : null,
            'rationale' => filled($llm['rationale'] ?? null)
                ? (string) $llm['rationale']
                : $heuristic['rationale'],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $faq
     * @return list<array{id: int, title: string, category_label: string}>
     */
    private function mapFaq(array $faq): array
    {
        return array_map(fn (array $item) => [
            'id' => (int) $item['id'],
            'title' => (string) $item['title'],
            'category_label' => (string) ($item['category_label'] ?? ''),
        ], $faq);
    }

    /** @param  Collection<int, Asset>  $assets */
    private function matchAsset(string $text, Collection $assets): ?int
    {
        $normalizedText = $this->normalizeInventoryToken($text);

        foreach ($assets as $asset) {
            $candidates = [
                Str::lower($asset->inventory_number),
                Str::lower($asset->name),
                $this->normalizeInventoryToken($asset->inventory_number),
            ];

            foreach ($candidates as $needle) {
                if ($needle !== '' && str_contains($normalizedText, $needle)) {
                    return $asset->id;
                }
            }

            if (preg_match_all('/inv[\s\-]*0*(\d+)/i', $text, $matches)) {
                $assetDigits = preg_replace('/\D+/', '', $asset->inventory_number) ?? '';
                $assetDigits = ltrim($assetDigits, '0');

                foreach ($matches[1] as $digits) {
                    if ($assetDigits !== '' && ltrim($digits, '0') === $assetDigits) {
                        return $asset->id;
                    }
                }
            }
        }

        return null;
    }

    private function normalizeInventoryToken(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->replaceMatches('/[\s_\-]+/', '')
            ->toString();
    }

    /** @param  list<string>  $keywords */
    private function score(string $text, array $keywords): int
    {
        $score = 0;
        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                $score++;
            }
        }

        return $score;
    }
}
