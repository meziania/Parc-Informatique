<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceCatalogItem extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'default_priority',
        'sla_hours',
        'is_active',
        'requires_approval',
        'sort_order',
    ];

    protected $appends = ['type_label', 'priority_label', 'sla_label'];

    protected function casts(): array
    {
        return [
            'type' => TicketType::class,
            'default_priority' => TicketPriority::class,
            'sla_hours' => 'integer',
            'is_active' => 'boolean',
            'requires_approval' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type instanceof TicketType
            ? $this->type->label()
            : (string) $this->type;
    }

    public function getPriorityLabelAttribute(): string
    {
        return $this->default_priority instanceof TicketPriority
            ? $this->default_priority->label()
            : (string) $this->default_priority;
    }

    public function getSlaLabelAttribute(): string
    {
        $hours = (int) $this->sla_hours;

        if ($hours < 24) {
            return "SLA {$hours} h";
        }

        $days = intdiv($hours, 24);

        return $days === 1 ? 'SLA 1 jour' : "SLA {$days} jours";
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }
}
