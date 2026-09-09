<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SatisfactionReminderNotification extends Notification
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
            ->subject("Votre avis sur le ticket {$this->ticket->number}")
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line("Le ticket « {$this->ticket->title} » a été résolu il y a plus de 48 h.")
            ->line('Merci de noter votre satisfaction (1 à 5) pour nous aider à améliorer le support.')
            ->action('Noter ce ticket', $this->ticketUrl())
            ->line('Ce rappel ne sera envoyé qu’une fois.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->number,
            'title' => "Avis demandé — {$this->ticket->number}",
            'body' => 'Merci de noter votre satisfaction sur ce ticket résolu.',
            'url' => $this->ticketUrl(),
        ];
    }

    private function ticketUrl(): string
    {
        return url(route('tickets.show', $this->ticket, false));
    }
}
