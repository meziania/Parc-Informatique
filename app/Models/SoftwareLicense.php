<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SoftwareLicense extends Model
{
    protected $fillable = [
        'name',
        'vendor',
        'product_key',
        'seats',
        'purchase_date',
        'expiry_date',
        'notes',
        'is_active',
        'entity_id',
    ];

    protected $appends = ['seats_used', 'seats_available', 'expiry_status', 'expiry_label'];

    protected function casts(): array
    {
        return [
            'seats' => 'integer',
            'purchase_date' => 'date',
            'expiry_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'asset_license')
            ->withPivot('assigned_at');
    }

    public function getSeatsUsedAttribute(): int
    {
        if (array_key_exists('assets_count', $this->attributes)) {
            return (int) $this->attributes['assets_count'];
        }

        return $this->assets()->count();
    }

    public function getSeatsAvailableAttribute(): int
    {
        return max(0, (int) $this->seats - $this->seats_used);
    }

    public function getExpiryStatusAttribute(): string
    {
        if ($this->expiry_date === null) {
            return 'none';
        }

        if ($this->expiry_date->isPast()) {
            return 'expired';
        }

        if ($this->expiry_date->lte(now()->addDays(30))) {
            return 'expiring';
        }

        return 'ok';
    }

    public function getExpiryLabelAttribute(): string
    {
        return match ($this->expiry_status) {
            'expired' => 'Expirée',
            'expiring' => 'Expire sous 30 j',
            'ok' => 'Valide',
            default => 'Sans échéance',
        };
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>=', now()->toDateString())
            ->whereDate('expiry_date', '<=', now()->addDays($days)->toDateString());
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', now()->toDateString());
    }
}
