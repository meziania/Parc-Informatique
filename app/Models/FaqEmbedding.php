<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaqEmbedding extends Model
{
    protected $fillable = [
        'faq_article_id',
        'model',
        'embedding',
        'content_hash',
    ];

    protected function casts(): array
    {
        return [
            'embedding' => 'array',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(FaqArticle::class, 'faq_article_id');
    }
}
