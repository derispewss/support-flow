<?php

namespace App\Modules\Support\Notifications;

use App\Modules\Support\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SlaBreachedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Ticket $ticket) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('SLA terlewati: '.$this->ticket->ticket_number)
            ->line('SLA tiket '.$this->ticket->ticket_number.' telah terlewati.')
            ->action('Buka tiket', route('admin.tickets.show', $this->ticket));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'event' => 'ticket.sla_breached',
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'subject' => $this->ticket->subject,
            'organization_id' => $this->ticket->organization_id,
        ];
    }
}
