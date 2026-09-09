<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    protected $fillable = [
        'user_id',
        'asset_id',
        'location_id',
        'title',
        'purpose',
        'starts_at',
        'ends_at',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    protected $appends = ['status_label', 'status_color', 'resource_label'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'status' => ReservationStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status instanceof ReservationStatus
            ? $this->status->label()
            : (string) $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status instanceof ReservationStatus
            ? $this->status->color()
            : 'gray';
    }

    public function getResourceLabelAttribute(): string
    {
        if ($this->asset) {
            return $this->asset->name.($this->asset->inventory_number ? ' ('.$this->asset->inventory_number.')' : '');
        }

        if ($this->location) {
            return 'Lieu : '.$this->location->name;
        }

        return '—';
    }

    public function scopeOverlapping($query, $startsAt, $endsAt, ?int $ignoreId = null)
    {
        return $query
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->whereIn('status', [ReservationStatus::Pending->value, ReservationStatus::Approved->value])
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt);
    }
}
