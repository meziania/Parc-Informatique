<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Contract extends Model
{
    protected $fillable = [
        'title',
        'reference',
        'supplier_id',
        'starts_on',
        'ends_on',
        'amount',
        'currency',
        'notes',
        'is_active',
        'entity_id',
    ];

    protected $appends = ['expiry_status', 'expiry_label'];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'asset_contract')
            ->withPivot('linked_at');
    }

    public function getExpiryStatusAttribute(): string
    {
        if ($this->ends_on === null) {
            return 'none';
        }

        if ($this->ends_on->isPast()) {
            return 'expired';
        }

        if ($this->ends_on->lte(now()->addDays(30))) {
            return 'expiring';
        }

        return 'ok';
    }

    public function getExpiryLabelAttribute(): string
    {
        return match ($this->expiry_status) {
            'expired' => 'Expiré',
            'expiring' => 'Expire sous 30 j',
            'ok' => 'En cours',
            default => 'Sans échéance',
        };
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->whereNotNull('ends_on')
            ->whereDate('ends_on', '>=', now()->toDateString())
            ->whereDate('ends_on', '<=', now()->addDays($days)->toDateString());
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('ends_on')
            ->whereDate('ends_on', '<', now()->toDateString());
    }
}
