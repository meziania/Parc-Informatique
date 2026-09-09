<?php

namespace App\Models;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Services\AssetHistoryLogger;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Asset extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::created(function (Asset $asset) {
            app(AssetHistoryLogger::class)->created($asset);
        });

        static::updated(function (Asset $asset) {
            app(AssetHistoryLogger::class)->recordChanges($asset);
        });
    }

    protected $fillable = [
        'name',
        'type',
        'status',
        'inventory_number',
        'serial_number',
        'manufacturer',
        'model',
        'purchase_date',
        'warranty_end',
        'next_maintenance_at',
        'location_id',
        'user_id',
        'entity_id',
        'notes',
    ];

    protected $appends = ['type_label', 'status_label', 'status_color', 'maintenance_label'];

    protected function casts(): array
    {
        return [
            'type' => AssetType::class,
            'status' => AssetStatus::class,
            'purchase_date' => 'date',
            'warranty_end' => 'date',
            'next_maintenance_at' => 'date',
        ];
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type?->label() ?? '—';
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status?->label() ?? '—';
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status?->color() ?? 'gray';
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable')->latest();
    }

    public function events(): HasMany
    {
        return $this->hasMany(AssetEvent::class)->latest('created_at')->latest('id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class)->latest();
    }

    public function licenses(): BelongsToMany
    {
        return $this->belongsToMany(SoftwareLicense::class, 'asset_license')
            ->withPivot('assigned_at');
    }

    public function contracts(): BelongsToMany
    {
        return $this->belongsToMany(Contract::class, 'asset_contract')
            ->withPivot('linked_at');
    }

    public function installedSoftwares(): HasMany
    {
        return $this->hasMany(AssetInstalledSoftware::class)->orderBy('name');
    }

    public function isUnderWarranty(): bool
    {
        return $this->warranty_end !== null && $this->warranty_end->isFuture();
    }

    public function getMaintenanceLabelAttribute(): string
    {
        if ($this->next_maintenance_at === null) {
            return 'Non planifiée';
        }

        if ($this->next_maintenance_at->isPast()) {
            return 'En retard';
        }

        if ($this->next_maintenance_at->lte(now()->addDays(30))) {
            return 'Sous 30 j';
        }

        return 'Planifiée';
    }

    /** @param  \Illuminate\Database\Eloquent\Builder<Asset>  $query */
    public function scopeWarrantyExpired($query)
    {
        return $query->whereNotNull('warranty_end')
            ->whereDate('warranty_end', '<', now()->toDateString());
    }

    /** @param  \Illuminate\Database\Eloquent\Builder<Asset>  $query */
    public function scopeWarrantyExpiringSoon($query, int $days = 30)
    {
        return $query->whereNotNull('warranty_end')
            ->whereDate('warranty_end', '>=', now()->toDateString())
            ->whereDate('warranty_end', '<=', now()->addDays($days)->toDateString());
    }

    /** @param  \Illuminate\Database\Eloquent\Builder<Asset>  $query */
    public function scopeMaintenanceDue($query)
    {
        return $query->whereNotNull('next_maintenance_at')
            ->whereDate('next_maintenance_at', '<=', now()->toDateString());
    }

    /** @param  \Illuminate\Database\Eloquent\Builder<Asset>  $query */
    public function scopeMaintenanceSoon($query, int $days = 30)
    {
        return $query->whereNotNull('next_maintenance_at')
            ->whereDate('next_maintenance_at', '>', now()->toDateString())
            ->whereDate('next_maintenance_at', '<=', now()->addDays($days)->toDateString());
    }
}
