<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketAssignedNotification extends Notification
{
    public function __construct(public Ticket $ticket) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Ticket {$this->ticket->number} assigné")
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line("Le ticket « {$this->ticket->title} » vous a été assigné.")
            ->action('Voir le ticket', $this->ticketUrl())
            ->line('Merci de prendre en charge cette demande.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->number,
            'title' => "Ticket {$this->ticket->number} assigné",
            'body' => $this->ticket->title,
            'url' => $this->ticketUrl(),
        ];
    }

    private function ticketUrl(): string
    {
        return url(route('tickets.show', $this->ticket, false));
    }
}
