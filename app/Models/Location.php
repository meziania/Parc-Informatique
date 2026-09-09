<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'building', 'room', 'entity_id'];

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function fullName(): string
    {
        return collect([$this->name, $this->building, $this->room])
            ->filter()
            ->implode(' — ');
    }
}
