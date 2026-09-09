<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketTask extends Model
{
    protected $fillable = [
        'ticket_id',
        'title',
        'is_done',
        'assignee_id',
        'due_at',
        'completed_at',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_done' => 'boolean',
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }
}
