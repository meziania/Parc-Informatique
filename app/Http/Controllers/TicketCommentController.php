<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketCommentedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class TicketCommentController extends Controller
{
    public function store(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless(
            $request->user()->isTechnician() || $ticket->requester_id === $request->user()->id,
            403
        );

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $ticket->comments()->create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        $author = $request->user();
        $notification = new TicketCommentedNotification($ticket, $author, $validated['body']);

        if ($author->id === $ticket->requester_id) {
            if ($ticket->assignee_id && $ticket->assignee_id !== $author->id) {
                $ticket->assignee?->notify($notification);
            } else {
                $recipients = User::query()
                    ->whereIn('role', [UserRole::Technician->value, UserRole::Admin->value])
                    ->whereKeyNot($author->id)
                    ->get();

                Notification::send($recipients, $notification);
            }
        } elseif ($ticket->requester_id !== $author->id) {
            $ticket->requester?->notify($notification);
        }

        return back();
    }
}
