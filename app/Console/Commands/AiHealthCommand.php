<?php

namespace App\Console\Commands;

use App\Services\Ai\AiClient;
use Illuminate\Console\Command;

class AiHealthCommand extends Command
{
    protected $signature = 'ai:health {--fresh : Ignore le cache et re-teste immédiatement}';

    protected $description = 'Vérifie Ollama / le provider IA (joignabilité + modèles chat & embeddings)';

    public function handle(AiClient $ai): int
    {
        $health = $ai->health(useCache: false, force: (bool) $this->option('fresh'));

        $this->table(
            ['Clé', 'Valeur'],
            collect($health)->map(fn ($value, $key) => [
                $key,
                is_bool($value) ? ($value ? 'oui' : 'non') : ($value ?? '—'),
            ])->values()->all()
        );

        if (! $health['enabled']) {
            $this->warn('IA désactivée.');

            return self::FAILURE;
        }

        if ($health['provider'] === 'ollama' && ! $health['reachable']) {
            $this->error($health['message']);
            if ($health['hint']) {
                $this->line('→ '.$health['hint']);
            }

            return self::FAILURE;
        }

        if ($health['provider'] === 'ollama' && ! $health['chat_model_ready']) {
            $this->warn($health['message']);
            if ($health['hint']) {
                $this->line('→ '.$health['hint']);
            }

            return self::FAILURE;
        }

        $this->info($health['message']);
        if ($health['hint']) {
            $this->line('Astuce : '.$health['hint']);
        }

        return self::SUCCESS;
    }
}
