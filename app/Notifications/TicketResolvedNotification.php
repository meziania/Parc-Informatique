<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketResolvedNotification extends Notification
{
    public function __construct(public Ticket $ticket) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("Ticket {$this->ticket->number} résolu")
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line("Votre ticket « {$this->ticket->title} » a été marqué comme résolu.");

        if (filled($this->ticket->solution)) {
            $mail->line('Solution : '.$this->ticket->solution);
        }

        return $mail
            ->action('Voir le ticket', $this->ticketUrl())
            ->line('Vous pouvez fermer le ticket si la solution vous convient.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->number,
            'title' => "Ticket {$this->ticket->number} résolu",
            'body' => $this->ticket->title,
            'url' => $this->ticketUrl(),
        ];
    }

    private function ticketUrl(): string
    {
        return url(route('tickets.show', $this->ticket, false));
    }
}
