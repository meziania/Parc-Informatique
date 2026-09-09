<?php

namespace App\Services\Ai;

use App\Models\FaqArticle;
use App\Models\FaqEmbedding;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class FaqRagService
{
    public function __construct(private AiClient $ai) {}

    /**
     * @return list<array{id: int, title: string, category_label: string, excerpt?: string, score?: float}>
     */
    public function search(string $query, ?int $limit = null): array
    {
        $limit ??= (int) config('ai.rag.top_k', 3);

        if ($this->ai->embeddingsAvailable()) {
            try {
                $semantic = $this->semanticSearch($query, $limit);
                if ($semantic !== []) {
                    return $semantic;
                }
            } catch (Throwable $e) {
                Log::warning('FAQ RAG semantic search failed, falling back to keywords.', [
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $this->keywordSearch($query, $limit);
    }

    public function indexArticle(FaqArticle $article): bool
    {
        if (! $article->is_published) {
            FaqEmbedding::query()->where('faq_article_id', $article->id)->delete();

            return false;
        }

        if (! $this->ai->embeddingsAvailable()) {
            return false;
        }

        $text = $this->articleText($article);
        $hash = hash('sha256', $text.'|'.config('ai.embedding_model'));

        $existing = FaqEmbedding::query()->where('faq_article_id', $article->id)->first();
        if ($existing && $existing->content_hash === $hash && $existing->model === config('ai.embedding_model')) {
            return true;
        }

        $vector = $this->ai->embed($text);

        FaqEmbedding::query()->updateOrCreate(
            ['faq_article_id' => $article->id],
            [
                'model' => (string) config('ai.embedding_model'),
                'embedding' => $vector,
                'content_hash' => $hash,
            ]
        );

        return true;
    }

    public function forgetArticle(int $articleId): void
    {
        FaqEmbedding::query()->where('faq_article_id', $articleId)->delete();
    }

    /**
     * @return array{indexed: int, skipped: int, failed: int}
     */
    public function reindexAll(): array
    {
        $indexed = 0;
        $skipped = 0;
        $failed = 0;

        FaqArticle::query()
            ->orderBy('id')
            ->each(function (FaqArticle $article) use (&$indexed, &$skipped, &$failed) {
                try {
                    if ($this->indexArticle($article)) {
                        $indexed++;
                    } else {
                        $skipped++;
                    }
                } catch (Throwable $e) {
                    $failed++;
                    Log::warning('FAQ embedding failed.', [
                        'faq_id' => $article->id,
                        'message' => $e->getMessage(),
                    ]);
                }
            });

        return compact('indexed', 'skipped', 'failed');
    }

    /**
     * @return list<array{id: int, title: string, category_label: string, excerpt: string, score: float}>
     */
    private function semanticSearch(string $query, int $limit): array
    {
        $queryVector = $this->ai->embed($query);
        $minScore = (float) config('ai.rag.min_score', 0.28);

        $scored = FaqEmbedding::query()
            ->with(['article:id,title,body,category,is_published'])
            ->where('model', config('ai.embedding_model'))
            ->get()
            ->filter(fn (FaqEmbedding $row) => $row->article?->is_published)
            ->map(function (FaqEmbedding $row) use ($queryVector) {
                $score = $this->cosineSimilarity($queryVector, $row->embedding ?? []);

                return [
                    'article' => $row->article,
                    'score' => $score,
                ];
            })
            ->filter(fn (array $row) => $row['score'] >= $minScore)
            ->sortByDesc('score')
            ->take($limit)
            ->values();

        return $scored->map(fn (array $row) => [
            'id' => $row['article']->id,
            'title' => $row['article']->title,
            'category_label' => $row['article']->category_label,
            'excerpt' => Str::limit(strip_tags($row['article']->body), 220),
            'score' => round($row['score'], 4),
        ])->all();
    }

    /**
     * @return list<array{id: int, title: string, category_label: string}>
     */
    private function keywordSearch(string $query, int $limit): array
    {
        $words = Str::of($query)
            ->lower()
            ->replaceMatches('/[^\p{L}\p{N}\s]+/u', ' ')
            ->split('/\s+/')
            ->filter(fn (string $word) => mb_strlen($word) >= 3)
            ->unique()
            ->take(8)
            ->values();

        if ($words->isEmpty()) {
            return [];
        }

        return FaqArticle::query()
            ->where('is_published', true)
            ->where(function ($q) use ($words) {
                foreach ($words as $word) {
                    $q->orWhere('title', 'ilike', "%{$word}%")
                        ->orWhere('body', 'ilike', "%{$word}%");
                }
            })
            ->orderByDesc('views_count')
            ->limit($limit)
            ->get(['id', 'title', 'category'])
            ->map(fn (FaqArticle $article) => [
                'id' => $article->id,
                'title' => $article->title,
                'category_label' => $article->category_label,
            ])
            ->all();
    }

    private function articleText(FaqArticle $article): string
    {
        return trim($article->title."\n\n".strip_tags((string) $article->body));
    }

    /**
     * @param  list<float>  $a
     * @param  list<float>  $b
     */
    private function cosineSimilarity(array $a, array $b): float
    {
        $n = min(count($a), count($b));
        if ($n === 0) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
