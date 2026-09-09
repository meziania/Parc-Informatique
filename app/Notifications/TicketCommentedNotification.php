<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketCommentedNotification extends Notification
{
    public function __construct(
        public Ticket $ticket,
        public User $author,
        public string $commentBody,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $excerpt = str($this->commentBody)->limit(160)->toString();

        return (new MailMessage)
            ->subject("Nouveau commentaire sur {$this->ticket->number}")
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line("{$this->author->name} a commenté le ticket « {$this->ticket->title} ».")
            ->line('"'.$excerpt.'"')
            ->action('Voir le ticket', $this->ticketUrl());
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->number,
            'title' => "Commentaire sur {$this->ticket->number}",
            'body' => $this->author->name.' : '.str($this->commentBody)->limit(100)->toString(),
            'url' => $this->ticketUrl(),
        ];
    }

    private function ticketUrl(): string
    {
        return url(route('tickets.show', $this->ticket, false));
    }
}
