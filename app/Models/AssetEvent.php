<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AssetEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'asset_id',
        'actor_id',
        'event_type',
        'title',
        'body',
        'meta',
        'related_type',
        'related_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }
}
