<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TicketTaskController extends Controller
{
    public function store(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorizeTicket($request, $ticket);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'due_at' => ['nullable', 'date'],
            'assignee_id' => ['nullable', 'exists:users,id'],
        ]);

        $maxOrder = (int) $ticket->tasks()->max('sort_order');

        $ticket->tasks()->create([
            ...$validated,
            'sort_order' => $maxOrder + 1,
            'is_done' => false,
        ]);

        return back()->with('status', 'Tâche ajoutée.');
    }

    public function update(Request $request, Ticket $ticket, TicketTask $task): RedirectResponse
    {
        $this->authorizeTask($request, $ticket, $task);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'due_at' => ['nullable', 'date'],
            'assignee_id' => ['nullable', 'exists:users,id'],
        ]);

        $task->update($validated);

        return back()->with('status', 'Tâche mise à jour.');
    }

    public function toggle(Request $request, Ticket $ticket, TicketTask $task): RedirectResponse
    {
        $this->authorizeTask($request, $ticket, $task);

        $done = ! $task->is_done;

        $task->update([
            'is_done' => $done,
            'completed_at' => $done ? now() : null,
        ]);

        return back();
    }

    public function destroy(Request $request, Ticket $ticket, TicketTask $task): RedirectResponse
    {
        $this->authorizeTask($request, $ticket, $task);

        $task->delete();

        return back()->with('status', 'Tâche supprimée.');
    }

    private function authorizeTicket(Request $request, Ticket $ticket): void
    {
        abort_unless($request->user()->isTechnician(), 403);
    }

    private function authorizeTask(Request $request, Ticket $ticket, TicketTask $task): void
    {
        abort_unless($request->user()->isTechnician(), 403);
        abort_unless($task->ticket_id === $ticket->id, 404);
    }
}
