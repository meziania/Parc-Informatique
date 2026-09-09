<?php

namespace App\Services\Ai;

use App\Enums\FaqCategory;
use App\Enums\TicketType;
use App\Models\Ticket;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class FaqDraftFromTicketService
{
    public function __construct(private AiClient $ai) {}

    /**
     * @return array{
     *     title: string,
     *     body: string,
     *     category: string,
     *     rationale: string,
     *     provider: string
     * }
     */
    public function draft(Ticket $ticket): array
    {
        abort_unless(
            filled($ticket->solution),
            422,
            'Le ticket doit contenir une solution pour proposer une FAQ.'
        );

        $heuristic = $this->heuristic($ticket);

        if ($this->llmAvailable()) {
            try {
                $llm = $this->fromLlm($ticket);
                $category = in_array($llm['category'] ?? null, array_column(FaqCategory::cases(), 'value'), true)
                    ? $llm['category']
                    : $heuristic['category'];

                return [
                    'title' => filled($llm['title'] ?? null) ? (string) $llm['title'] : $heuristic['title'],
                    'body' => filled($llm['body'] ?? null) ? (string) $llm['body'] : $heuristic['body'],
                    'category' => $category,
                    'rationale' => filled($llm['rationale'] ?? null)
                        ? (string) $llm['rationale']
                        : $heuristic['rationale'],
                    'provider' => $this->ai->providerLabel(),
                ];
            } catch (Throwable $e) {
                Log::warning('AI FAQ draft failed, using heuristic.', [
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

    /** @return array<string, mixed> */
    private function fromLlm(Ticket $ticket): array
    {
        $categories = collect(FaqCategory::cases())
            ->map(fn (FaqCategory $c) => ['value' => $c->value, 'label' => $c->label()])
            ->all();

        $system = <<<'PROMPT'
Tu es un rédacteur de base de connaissances ITSM francophone.
À partir d'un ticket résolu, propose une fiche FAQ claire et réutilisable.
Réponds UNIQUEMENT en JSON valide avec :
- title (string, question claire)
- body (string, markdown simple : symptômes, causes possibles, étapes de résolution, prévention)
- category (une de : account, hardware, software, network, other)
- rationale (string, 1 phrase)
Ne copie pas brutalement le ticket : reformule pour un public utilisateur.
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
                        'description' => $ticket->description,
                        'solution' => $ticket->solution,
                        'asset' => $ticket->asset?->only(['name', 'inventory_number', 'type']),
                    ],
                    'categories' => $categories,
                ], JSON_UNESCAPED_UNICODE),
            ],
        ], 0.3);
    }

    /**
     * @return array{
     *     title: string,
     *     body: string,
     *     category: string,
     *     rationale: string
     * }
     */
    private function heuristic(Ticket $ticket): array
    {
        $text = Str::lower(($ticket->title ?? '').' '.($ticket->description ?? '').' '.($ticket->solution ?? ''));
        $category = $this->guessCategory($text, $ticket);

        $question = $ticket->title;
        if (! str_ends_with(trim($question), '?')) {
            $question = 'Que faire en cas de : '.Str::of($ticket->title)->lower()->trim().' ?';
            $question = Str::ucfirst($question);
        }

        $solution = trim((string) $ticket->solution) ?: 'Solution à documenter.';

        $body = "## Symptômes\n\n"
            .Str::limit(trim((string) $ticket->description), 500)."\n\n"
            ."## Résolution\n\n"
            .$solution."\n\n"
            ."## Conseils\n\n"
            ."- Vérifier que le correctif fonctionne après redémarrage.\n"
            ."- Contacter le support si le problème réapparaît.\n\n"
            ."_Fiche proposée à partir du ticket {$ticket->number}._";

        return [
            'title' => Str::limit($question, 120, ''),
            'body' => $body,
            'category' => $category->value,
            'rationale' => 'Brouillon local généré depuis le titre, la description et la solution du ticket.',
        ];
    }

    private function guessCategory(string $text, Ticket $ticket): FaqCategory
    {
        if ($this->containsAny($text, ['wifi', 'vpn', 'réseau', 'reseau', 'internet', 'connexion'])) {
            return FaqCategory::Network;
        }

        if ($this->containsAny($text, ['outlook', 'office', 'logiciel', 'installer', 'windows', 'application'])) {
            return FaqCategory::Software;
        }

        if ($this->containsAny($text, ['écran', 'ecran', 'bsod', 'imprimante', 'pc', 'ordinateur', 'matériel', 'materiel', 'disque'])) {
            return FaqCategory::Hardware;
        }

        if ($this->containsAny($text, ['mot de passe', 'compte', 'accès', 'acces', 'login', 'authentification'])) {
            return FaqCategory::Account;
        }

        if ($ticket->type === TicketType::Request) {
            return FaqCategory::Other;
        }

        return FaqCategory::Hardware;
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
}
