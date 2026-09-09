<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'title',
        'description',
        'type',
        'priority',
        'status',
        'approval_status',
        'approved_by',
        'approved_at',
        'approval_note',
        'requester_id',
        'assignee_id',
        'asset_id',
        'service_catalog_item_id',
        'entity_id',
        'solution',
        'resolved_at',
        'closed_at',
        'due_at',
        'satisfaction_rating',
        'satisfaction_comment',
        'satisfaction_rated_at',
        'satisfaction_reminded_at',
    ];

    protected $appends = [
        'type_label',
        'priority_label',
        'priority_color',
        'status_label',
        'status_color',
        'sla_status',
        'sla_label',
        'sla_color',
        'satisfaction_label',
        'approval_label',
        'approval_color',
    ];

    protected function casts(): array
    {
        return [
            'type' => TicketType::class,
            'priority' => TicketPriority::class,
            'status' => TicketStatus::class,
            'approval_status' => ApprovalStatus::class,
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'due_at' => 'datetime',
            'approved_at' => 'datetime',
            'satisfaction_rated_at' => 'datetime',
            'satisfaction_reminded_at' => 'datetime',
            'satisfaction_rating' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket) {
            if ($ticket->due_at !== null) {
                return;
            }

            $priority = $ticket->priority instanceof TicketPriority
                ? $ticket->priority
                : TicketPriority::tryFrom((string) $ticket->getAttribute('priority'));

            if ($priority) {
                $ticket->due_at = now()->addHours($priority->slaHours());
            }
        });

        static::created(function (Ticket $ticket) {
            if ($ticket->number === null) {
                $ticket->number = 'T-'.str_pad((string) $ticket->id, 6, '0', STR_PAD_LEFT);
                $ticket->saveQuietly();
            }
        });

        static::updating(function (Ticket $ticket) {
            if ($ticket->isDirty('priority') && $ticket->priority instanceof TicketPriority) {
                $base = $ticket->created_at ?? now();
                $ticket->due_at = $base->copy()->addHours($ticket->priority->slaHours());
            }
        });
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function serviceCatalogItem(): BelongsTo
    {
        return $this->belongsTo(ServiceCatalogItem::class);
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class)->oldest();
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable')->latest();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(TicketTask::class)->orderBy('sort_order')->orderBy('id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [
            TicketStatus::New,
            TicketStatus::Assigned,
            TicketStatus::InProgress,
        ]);
    }

    public function scopeSlaBreached(Builder $query): Builder
    {
        return $query->open()
            ->whereNotNull('due_at')
            ->where('due_at', '<', now());
    }

    public function scopeSlaAtRisk(Builder $query): Builder
    {
        return $query->open()
            ->whereNotNull('due_at')
            ->where('due_at', '>=', now())
            ->where('due_at', '<=', now()->addHours(4));
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type?->label() ?? '—';
    }

    public function getPriorityLabelAttribute(): string
    {
        return $this->priority?->label() ?? '—';
    }

    public function getPriorityColorAttribute(): string
    {
        return $this->priority?->color() ?? 'gray';
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status?->label() ?? '—';
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status?->color() ?? 'gray';
    }

    public function getSlaStatusAttribute(): string
    {
        if (! $this->due_at) {
            return 'none';
        }

        $reference = $this->resolved_at
            ?? $this->closed_at
            ?? Carbon::now();

        if ($this->status && ! $this->status->isOpen()) {
            return $reference->lte($this->due_at) ? 'met' : 'breached';
        }

        if (Carbon::now()->greaterThan($this->due_at)) {
            return 'breached';
        }

        if (now()->diffInMinutes($this->due_at) <= 4 * 60) {
            return 'at_risk';
        }

        return 'ok';
    }

    public function getSlaLabelAttribute(): string
    {
        return match ($this->sla_status) {
            'ok' => 'Dans les délais',
            'at_risk' => 'À risque',
            'breached' => 'SLA dépassé',
            'met' => 'SLA respecté',
            default => '—',
        };
    }

    public function getSlaColorAttribute(): string
    {
        return match ($this->sla_status) {
            'ok', 'met' => 'green',
            'at_risk' => 'orange',
            'breached' => 'red',
            default => 'gray',
        };
    }

    public function getSatisfactionLabelAttribute(): ?string
    {
        return match ($this->satisfaction_rating) {
            1 => 'Très insatisfait',
            2 => 'Insatisfait',
            3 => 'Neutre',
            4 => 'Satisfait',
            5 => 'Très satisfait',
            default => null,
        };
    }

    public function getApprovalLabelAttribute(): ?string
    {
        if ($this->approval_status === null) {
            return null;
        }

        return $this->approval_status instanceof ApprovalStatus
            ? $this->approval_status->label()
            : (string) $this->approval_status;
    }

    public function getApprovalColorAttribute(): ?string
    {
        if ($this->approval_status === null) {
            return null;
        }

        return $this->approval_status instanceof ApprovalStatus
            ? $this->approval_status->color()
            : 'gray';
    }

    public function isApprovalPending(): bool
    {
        return $this->approval_status === ApprovalStatus::Pending;
    }

    public function isApprovalBlocked(): bool
    {
        return in_array($this->approval_status, [ApprovalStatus::Pending, ApprovalStatus::Rejected], true);
    }

    public function isSatisfactionPending(): bool
    {
        return $this->satisfaction_rating === null
            && in_array($this->status, [TicketStatus::Resolved, TicketStatus::Closed], true);
    }
}
