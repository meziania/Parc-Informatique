<?php

namespace App\Models;

use App\Enums\FaqCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaqArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'body',
        'category',
        'is_published',
        'views_count',
        'author_id',
    ];

    protected $appends = ['category_label'];

    protected function casts(): array
    {
        return [
            'category' => FaqCategory::class,
            'is_published' => 'boolean',
            'views_count' => 'integer',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function getCategoryLabelAttribute(): string
    {
        return $this->category->label();
    }
}
