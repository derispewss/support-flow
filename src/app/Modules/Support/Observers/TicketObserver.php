<?php

namespace App\Modules\Support\Observers;

use App\Modules\Support\Models\Ticket;
use App\Modules\Support\Services\TicketNumberGenerator;
use App\Shared\Enums\TicketStatus;

class TicketObserver
{
    public function __construct(protected TicketNumberGenerator $numbers) {}

    public function creating(Ticket $ticket): void
    {
        if ($ticket->ticket_number === null || $ticket->ticket_number === '') {
            $ticket->ticket_number = $this->numbers->generate((int) $ticket->organization_id);
        }

        if ($ticket->last_activity_at === null) {
            $ticket->last_activity_at = now();
        }
    }

    public function updating(Ticket $ticket): void
    {
        if (! $ticket->isDirty('status')) {
            return;
        }

        $status = $ticket->status;
        $previous = TicketStatus::tryFrom((string) $ticket->getRawOriginal('status'));

        if ($status === TicketStatus::Resolved) {
            $ticket->resolved_at ??= now();
        }

        if ($status === TicketStatus::Closed) {
            $ticket->resolved_at ??= now();
            $ticket->closed_at ??= now();
        }

        if ($previous === TicketStatus::Resolved && $status !== TicketStatus::Resolved) {
            $ticket->resolved_at = null;
        }

        if ($previous === TicketStatus::Closed && $status !== TicketStatus::Closed) {
            $ticket->closed_at = null;
        }

        $ticket->last_activity_at = now();
    }
}
