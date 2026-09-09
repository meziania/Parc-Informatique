<?php

namespace App\Console\Commands;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Notifications\SatisfactionReminderNotification;
use Illuminate\Console\Command;

class RemindPendingSatisfactionCommand extends Command
{
    protected $signature = 'tickets:remind-satisfaction {--hours=48 : Délai minimum après résolution}';

    protected $description = 'Rappelle aux demandeurs de noter leur satisfaction (tickets résolus/clos sans avis)';

    public function handle(): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $before = now()->subHours($hours);

        $tickets = Ticket::query()
            ->with('requester')
            ->whereIn('status', [TicketStatus::Resolved, TicketStatus::Closed])
            ->whereNull('satisfaction_rating')
            ->whereNull('satisfaction_reminded_at')
            ->where(function ($query) use ($before) {
                $query->where('resolved_at', '<=', $before)
                    ->orWhere(function ($inner) use ($before) {
                        $inner->whereNull('resolved_at')
                            ->where('closed_at', '<=', $before);
                    });
            })
            ->limit(200)
            ->get();

        $sent = 0;

        foreach ($tickets as $ticket) {
            if (! $ticket->requester) {
                continue;
            }

            $ticket->requester->notify(new SatisfactionReminderNotification($ticket));
            $ticket->forceFill(['satisfaction_reminded_at' => now()])->save();
            $sent++;
        }

        $this->info("Rappels envoyés : {$sent}");

        return self::SUCCESS;
    }
}
