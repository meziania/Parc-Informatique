<?php

namespace App\Observers;

use App\Models\FaqArticle;
use App\Services\Ai\FaqRagService;
use Illuminate\Support\Facades\Log;
use Throwable;

class FaqArticleObserver
{
    public function __construct(private FaqRagService $rag) {}

    public function saved(FaqArticle $article): void
    {
        try {
            $this->rag->indexArticle($article);
        } catch (Throwable $e) {
            Log::warning('FAQ auto-index skipped.', [
                'faq_id' => $article->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function deleted(FaqArticle $article): void
    {
        $this->rag->forgetArticle($article->id);
    }
}
