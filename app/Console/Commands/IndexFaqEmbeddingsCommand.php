<?php

namespace App\Console\Commands;

use App\Services\Ai\FaqRagService;
use Illuminate\Console\Command;

class IndexFaqEmbeddingsCommand extends Command
{
    protected $signature = 'ai:index-faq';

    protected $description = 'Indexe les articles FAQ publiés pour la recherche sémantique (RAG / Ollama embeddings)';

    public function handle(FaqRagService $rag): int
    {
        if (! config('ai.rag.enabled')) {
            $this->warn('AI_RAG_ENABLED=false — rien à faire.');

            return self::SUCCESS;
        }

        $this->info('Indexation FAQ (modèle: '.config('ai.embedding_model').')…');
        $result = $rag->reindexAll();

        $this->table(
            ['Indexés', 'Ignorés', 'Échecs'],
            [[$result['indexed'], $result['skipped'], $result['failed']]]
        );

        if ($result['failed'] > 0) {
            $this->warn('Des échecs sont survenus (Ollama démarré ? modèle `ollama pull '.config('ai.embedding_model').'` ?).');

            return self::FAILURE;
        }

        $this->info('OK.');

        return self::SUCCESS;
    }
}
