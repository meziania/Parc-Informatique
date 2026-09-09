<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class AiClient
{
    public function llmAvailable(): bool
    {
        if (! config('ai.enabled')) {
            return false;
        }

        if ($this->isOllama()) {
            return (bool) data_get($this->health(useCache: true), 'reachable');
        }

        return filled(config('ai.api_key')) && config('ai.api_key') !== 'ollama';
    }

    public function embeddingsAvailable(): bool
    {
        if (! config('ai.enabled') || ! config('ai.rag.enabled')) {
            return false;
        }

        if ($this->isOllama()) {
            $health = $this->health(useCache: true);

            return (bool) data_get($health, 'reachable')
                && (bool) data_get($health, 'embedding_model_ready', true);
        }

        return $this->llmAvailable();
    }

    public function isOllama(): bool
    {
        return config('ai.provider') === 'ollama'
            || str_contains((string) config('ai.base_url'), '11434');
    }

    public function providerLabel(): string
    {
        return $this->isOllama() ? 'ollama' : 'llm';
    }

    /**
     * @return array{
     *     enabled: bool,
     *     provider: string,
     *     reachable: bool,
     *     mode: string,
     *     chat_model: string,
     *     embedding_model: string,
     *     chat_model_ready: bool|null,
     *     embedding_model_ready: bool|null,
     *     message: string,
     *     hint: string|null,
     *     latency_ms: int|null
     * }
     */
    public function health(bool $useCache = false, bool $force = false): array
    {
        if ($force) {
            Cache::forget($this->healthCacheKey());
        }

        if ($useCache) {
            return Cache::remember($this->healthCacheKey(), now()->addSeconds(45), fn () => $this->probeHealth());
        }

        $result = $this->probeHealth();
        Cache::put($this->healthCacheKey(), $result, now()->addSeconds(45));

        return $result;
    }

    /**
     * @param  list<array{role: string, content: string}>  $messages
     * @return array<string, mixed>
     */
    public function chatJson(array $messages, float $temperature = 0.2): array
    {
        $payload = [
            'model' => config('ai.model'),
            'temperature' => $temperature,
            'messages' => $messages,
        ];

        if (! $this->isOllama()) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $response = $this->http()
            ->post('/chat/completions', $payload)
            ->throw();

        $content = (string) data_get($response->json(), 'choices.0.message.content', '');

        return $this->decodeJsonObject($content);
    }

    /**
     * @return list<float>
     */
    public function embed(string $text): array
    {
        $text = trim(Str::limit($text, 8000, ''));
        if ($text === '') {
            throw new RuntimeException('Empty text for embedding.');
        }

        $response = $this->http()
            ->timeout((int) config('ai.timeout'))
            ->post('/embeddings', [
                'model' => config('ai.embedding_model'),
                'input' => $text,
            ])
            ->throw();

        $vector = data_get($response->json(), 'data.0.embedding');

        if (! is_array($vector) || $vector === []) {
            throw new RuntimeException('Invalid embedding response.');
        }

        return array_map('floatval', $vector);
    }

    /**
     * @return array<string, mixed>
     */
    private function probeHealth(): array
    {
        $provider = $this->isOllama() ? 'ollama' : (string) config('ai.provider', 'openai');
        $chatModel = (string) config('ai.model');
        $embedModel = (string) config('ai.embedding_model');

        $base = [
            'enabled' => (bool) config('ai.enabled'),
            'provider' => $provider,
            'reachable' => false,
            'mode' => 'heuristic',
            'chat_model' => $chatModel,
            'embedding_model' => $embedModel,
            'chat_model_ready' => null,
            'embedding_model_ready' => null,
            'message' => 'Assistant désactivé (AI_ENABLED=false).',
            'hint' => null,
            'latency_ms' => null,
        ];

        if (! config('ai.enabled')) {
            return $base;
        }

        if ($this->isOllama()) {
            return $this->probeOllama($base, $chatModel, $embedModel);
        }

        if (! filled(config('ai.api_key')) || config('ai.api_key') === 'ollama') {
            return [
                ...$base,
                'message' => 'Mode heuristique : aucune clé API cloud configurée.',
                'hint' => 'Définissez AI_API_KEY, ou passez en AI_PROVIDER=ollama.',
            ];
        }

        return [
            ...$base,
            'reachable' => true,
            'mode' => 'llm',
            'message' => 'Provider cloud configuré ('.$provider.' / '.$chatModel.').',
            'hint' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $base
     * @return array<string, mixed>
     */
    private function probeOllama(array $base, string $chatModel, string $embedModel): array
    {
        $started = hrtime(true);

        try {
            $response = Http::baseUrl($this->ollamaNativeBase())
                ->timeout(3)
                ->acceptJson()
                ->get('/api/tags')
                ->throw();

            $latency = (int) round((hrtime(true) - $started) / 1e6);
            $names = collect($response->json('models') ?? [])
                ->map(fn ($model) => (string) data_get($model, 'name', ''))
                ->filter()
                ->values();

            $chatReady = $this->modelIsPresent($names->all(), $chatModel);
            $embedReady = $this->modelIsPresent($names->all(), $embedModel);

            $hints = [];
            if (! $chatReady) {
                $hints[] = "ollama pull {$chatModel}";
            }
            if (config('ai.rag.enabled') && ! $embedReady) {
                $hints[] = "ollama pull {$embedModel}";
            }

            $reachable = true;
            $mode = $chatReady ? 'ollama' : 'heuristic';
            $message = $chatReady
                ? "Ollama joignable — modèle chat « {$chatModel} » prêt."
                : "Ollama joignable, mais le modèle « {$chatModel} » est absent → mode heuristique.";

            if (config('ai.rag.enabled') && ! $embedReady) {
                $message .= " Embeddings « {$embedModel} » manquants (RAG en fallback mots-clés).";
            }

            return [
                ...$base,
                'reachable' => $reachable,
                'mode' => $mode,
                'chat_model_ready' => $chatReady,
                'embedding_model_ready' => $embedReady,
                'message' => $message,
                'hint' => $hints !== [] ? implode(' && ', $hints) : null,
                'latency_ms' => $latency,
            ];
        } catch (Throwable $e) {
            return [
                ...$base,
                'reachable' => false,
                'mode' => 'heuristic',
                'chat_model_ready' => false,
                'embedding_model_ready' => false,
                'message' => 'Ollama injoignable → suggestions en mode heuristique.',
                'hint' => 'Lancez Ollama, puis : ollama pull '.$chatModel.' && ollama pull '.$embedModel,
                'latency_ms' => null,
            ];
        }
    }

    /** @param  list<string>  $installed */
    private function modelIsPresent(array $installed, string $wanted): bool
    {
        $wanted = Str::lower($wanted);

        foreach ($installed as $name) {
            $name = Str::lower($name);
            if ($name === $wanted || Str::startsWith($name, $wanted.':')) {
                return true;
            }
        }

        return false;
    }

    private function ollamaNativeBase(): string
    {
        $base = (string) config('ai.base_url');
        $base = preg_replace('#/v1/?$#', '', $base) ?: $base;

        return rtrim($base, '/');
    }

    private function healthCacheKey(): string
    {
        return 'ai.health.'.md5(implode('|', [
            config('ai.provider'),
            config('ai.base_url'),
            config('ai.model'),
            config('ai.embedding_model'),
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonObject(string $content): array
    {
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        throw new RuntimeException('Invalid AI JSON response.');
    }

    private function http()
    {
        $request = Http::baseUrl((string) config('ai.base_url'))
            ->timeout((int) config('ai.timeout'))
            ->acceptJson();

        $key = (string) config('ai.api_key');
        if ($key !== '') {
            $request = $request->withToken($key);
        }

        return $request;
    }
}
