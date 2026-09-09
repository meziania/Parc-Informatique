<?php

namespace App\Services\Ai;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class TicketResolutionAssistService
{
    public function __construct(
        private AiClient $ai,
        private FaqRagService $faqRag,
    ) {}

    /**
     * @return array{
     *     summary: string,
     *     checklist: list<string>,
     *     draft_solution: string,
     *     similar_tickets: list<array{id: int, number: string, title: string, solution: string|null}>,
     *     faq_suggestions: list<array{id: int, title: string, category_label: string}>,
     *     provider: string
     * }
     */
    public function assist(Ticket $ticket): array
    {
        $ticket->loadMissing(['asset:id,name,inventory_number,type,status', 'comments.user:id,name']);

        $contextText = $this->contextText($ticket);
        $heuristic = $this->heuristic($ticket, $contextText);
        $faq = $this->faqRag->search($contextText);
        $heuristic['faq_suggestions'] = $this->mapFaq($faq);

        if ($this->llmAvailable()) {
            try {
                $llm = $this->fromLlm($ticket, $contextText, $heuristic['similar_tickets'], $faq);
                $merged = [
                    'summary' => filled($llm['summary'] ?? null) ? (string) $llm['summary'] : $heuristic['summary'],
                    'checklist' => $this->normalizeList($llm['checklist'] ?? null) ?: $heuristic['checklist'],
                    'draft_solution' => filled($llm['draft_solution'] ?? null)
                        ? (string) $llm['draft_solution']
                        : $heuristic['draft_solution'],
                    'similar_tickets' => $heuristic['similar_tickets'],
                    'faq_suggestions' => $heuristic['faq_suggestions'],
                    'provider' => $this->ai->providerLabel(),
                ];

                return $merged;
            } catch (Throwable $e) {
                Log::warning('AI resolution assist failed, using heuristic.', [
                    'ticket_id' => $ticket->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

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
     * @param  list<array{id: int, number: string, title: string, solution: string|null}>  $similar
     * @param  list<array<string, mixed>>  $faq
     * @return array<string, mixed>
     */
    private function fromLlm(Ticket $ticket, string $contextText, array $similar, array $faq): array
    {
        $system = <<<'PROMPT'
Tu es un assistant pour techniciens ITSM (parc informatique), en français.
À partir du ticket, des tickets similaires et des articles FAQ (RAG), propose une aide à la résolution.
Réponds UNIQUEMENT en JSON valide avec :
- summary (string, 2-3 phrases)
- checklist (array de 3 à 6 actions concrètes courtes)
- draft_solution (string, texte prêt à coller comme solution une fois validé)
Ne invente pas de faits absents du ticket. Reste prudent et opérationnel. Cite la FAQ si elle aide.
PROMPT;

        return $this->ai->chatJson([
            ['role' => 'system', 'content' => $system],
            [
                'role' => 'user',
                'content' => json_encode([
                    'ticket' => [
                        'number' => $ticket->number,
                        'title' => $ticket->title,
                        'type' => $ticket->type?->value,
                        'priority' => $ticket->priority?->value,
                        'status' => $ticket->status?->value,
                        'description' => $ticket->description,
                        'asset' => $ticket->asset?->only(['name', 'inventory_number', 'type', 'status']),
                        'comments' => $ticket->comments->take(8)->map(fn ($c) => [
                            'author' => $c->user?->name,
                            'body' => $c->body,
                        ])->all(),
                    ],
                    'similar_resolved_tickets' => $similar,
                    'faq_context' => $faq,
                    'context' => $contextText,
                ], JSON_UNESCAPED_UNICODE),
            ],
        ], 0.3);
    }

    /**
     * @return array{
     *     summary: string,
     *     checklist: list<string>,
     *     draft_solution: string,
     *     similar_tickets: list<array{id: int, number: string, title: string, solution: string|null}>,
     *     faq_suggestions: list<array{id: int, title: string, category_label: string}>
     * }
     */
    private function heuristic(Ticket $ticket, string $contextText): array
    {
        $text = Str::lower($contextText);
        $checklist = $this->checklistFor($text);
        $similar = $this->similarTickets($ticket, $contextText);

        $assetLine = $ticket->asset
            ? "Équipement : {$ticket->asset->name} ({$ticket->asset->inventory_number})."
            : 'Aucun équipement lié.';

        $summary = trim(sprintf(
            'Ticket %s — %s (%s, priorité %s). %s %s',
            $ticket->number,
            $ticket->type_label,
            $ticket->status_label,
            $ticket->priority_label,
            $assetLine,
            Str::limit(strip_tags($ticket->description), 180)
        ));

        $draft = "Diagnostic réalisé sur le ticket {$ticket->number}.\n\n";
        $draft .= "Actions effectuées :\n";
        foreach ($checklist as $index => $step) {
            $draft .= ($index + 1).'. '.$step."\n";
        }
        $draft .= "\nRésultat : [à compléter — problème résolu / partiellement résolu].\n";
        $draft .= 'Conseils utilisateur : redémarrer le poste et retester le scénario initial.';

        if ($similar !== [] && filled($similar[0]['solution'] ?? null)) {
            $draft .= "\n\nRéférence ticket similaire {$similar[0]['number']} : "
                .Str::limit((string) $similar[0]['solution'], 220);
        }

        return [
            'summary' => $summary,
            'checklist' => $checklist,
            'draft_solution' => $draft,
            'similar_tickets' => $similar,
            'faq_suggestions' => [],
        ];
    }

    /** @return list<string> */
    private function checklistFor(string $text): array
    {
        if ($this->containsAny($text, ['écran bleu', 'bsod', 'blue screen', 'critical_process'])) {
            return [
                'Noter le code d’arrêt / message BSOD',
                'Démarrer en mode sans échec si possible',
                'Exécuter sfc /scannow et DISM RestoreHealth',
                'Mettre à jour ou réinstaller le pilote GPU',
                'Vérifier l’espace disque et les erreurs (chkdsk)',
                'Tester un démarrage propre après correction',
            ];
        }

        if ($this->containsAny($text, ['imprimante', 'print', 'bourrage', 'toner'])) {
            return [
                'Vérifier alimentation, câbles et état du papier',
                'Purger la file d’attente d’impression Windows',
                'Réinstaller / mettre à jour le pilote',
                'Tester une page de test depuis le panneau imprimante',
                'Vérifier le partage réseau / droits si imprimante partagée',
            ];
        }

        if ($this->containsAny($text, ['réseau', 'wifi', 'vpn', 'internet', 'connexion'])) {
            return [
                'Tester ping passerelle / DNS',
                'Vérifier Wi‑Fi / câble / VPN',
                'Renouveler bail IP (ipconfig /renew)',
                'Contrôler proxy / pare-feu local',
                'Comparer avec un autre poste du même VLAN',
            ];
        }

        if ($this->containsAny($text, ['outlook', 'mail', 'e-mail', 'email', 'messagerie'])) {
            return [
                'Tester webmail vs client Outlook',
                'Réparer le profil Outlook / nouveau profil',
                'Vérifier espace boîte et authentification',
                'Contrôler certificats / MFA',
                'Tester en mode sans complément Outlook',
            ];
        }

        if ($this->containsAny($text, ['demande', 'installer', 'accès', 'nouveau', 'licence'])) {
            return [
                'Confirmer le besoin et le périmètre (qui, quoi, jusqu’à quand)',
                'Vérifier les droits / licences disponibles',
                'Planifier l’intervention ou la livraison',
                'Documenter la configuration réalisée',
                'Faire valider par le demandeur',
            ];
        }

        return [
            'Recueillir les symptômes et la chronologie',
            'Reproduire le problème sur le poste concerné',
            'Vérifier les changements récents (MAJ, logiciels)',
            'Consulter la FAQ et les tickets similaires',
            'Appliquer le correctif puis faire retester l’utilisateur',
            'Documenter la solution dans le ticket',
        ];
    }

    /**
     * @return list<array{id: int, number: string, title: string, solution: string|null}>
     */
    private function similarTickets(Ticket $ticket, string $contextText): array
    {
        $words = Str::of($contextText)
            ->lower()
            ->replaceMatches('/[^\p{L}\p{N}\s]+/u', ' ')
            ->split('/\s+/')
            ->filter(fn (string $word) => mb_strlen($word) >= 4)
            ->unique()
            ->take(6)
            ->values();

        if ($words->isEmpty()) {
            return [];
        }

        return Ticket::query()
            ->whereKeyNot($ticket->id)
            ->whereIn('status', [TicketStatus::Resolved, TicketStatus::Closed])
            ->where(function ($query) use ($words) {
                foreach ($words as $word) {
                    $query->orWhere('title', 'ilike', "%{$word}%")
                        ->orWhere('description', 'ilike', "%{$word}%")
                        ->orWhere('solution', 'ilike', "%{$word}%");
                }
            })
            ->latest('resolved_at')
            ->limit(3)
            ->get(['id', 'number', 'title', 'solution'])
            ->map(fn (Ticket $item) => [
                'id' => $item->id,
                'number' => $item->number,
                'title' => $item->title,
                'solution' => $item->solution,
            ])
            ->all();
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

    private function contextText(Ticket $ticket): string
    {
        $parts = [
            $ticket->title,
            $ticket->description,
            $ticket->type?->value,
            $ticket->asset?->name,
            $ticket->asset?->inventory_number,
        ];

        foreach ($ticket->comments as $comment) {
            $parts[] = $comment->body;
        }

        return implode("\n", array_filter($parts));
    }

    /** @param  list<string>  $needles */
    private function containsAny(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($text, $needle)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    private function normalizeList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($item) => is_string($item) ? trim($item) : null,
            $value
        )));
    }
}
