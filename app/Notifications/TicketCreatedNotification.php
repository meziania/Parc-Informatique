<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketCreatedNotification extends Notification
{
    public function __construct(public Ticket $ticket) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $requester = $this->ticket->requester;
        $requesterLine = $requester
            ? "Demandeur : {$requester->name}".($requester->email ? " ({$requester->email})" : '')
            : 'Demandeur : inconnu';

        $mail = (new MailMessage)
            ->subject("Nouveau ticket {$this->ticket->number} — {$this->requesterName()}")
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line("Un nouveau ticket a été ouvert par {$this->requesterName()}.")
            ->line("Titre : {$this->ticket->title}.")
            ->line($requesterLine)
            ->line('Priorité : '.$this->ticket->priority_label.'.');

        if ($this->ticket->asset) {
            $mail->line('Équipement : '.$this->ticket->asset->name);
        }

        return $mail
            ->action('Voir le ticket', $this->ticketUrl())
            ->line('Merci de traiter cette demande dans les meilleurs délais.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $requester = $this->ticket->requester;

        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->number,
            'requester_id' => $requester?->id,
            'requester_name' => $requester?->name,
            'title' => "Nouveau ticket {$this->ticket->number} — {$this->requesterName()}",
            'body' => "{$this->ticket->title} · Demandeur : {$this->requesterName()}",
            'url' => $this->ticketUrl(),
        ];
    }

    private function requesterName(): string
    {
        return $this->ticket->requester?->name ?? 'Utilisateur';
    }

    private function ticketUrl(): string
    {
        return url(route('tickets.show', $this->ticket, false));
    }
}
