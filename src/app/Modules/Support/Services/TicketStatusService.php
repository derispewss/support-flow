<?php

namespace App\Modules\Support\Services;

use App\Modules\Identity\Models\User;
use App\Modules\Support\Models\Ticket;
use App\Modules\Support\Models\TicketMessage;
use App\Shared\Enums\MessageType;
use App\Shared\Enums\TicketStatus;
use Illuminate\Validation\ValidationException;

class TicketStatusService
{
    public function __construct(protected AuditLogger $audit) {}

    public function transition(Ticket $ticket, TicketStatus $target, ?User $actor = null, ?string $note = null): Ticket
    {
        if ($ticket->status === $target) {
            return $ticket;
        }

        if (! $ticket->status->canTransitionTo($target)) {
            throw ValidationException::withMessages([
                'status' => "Tidak dapat mengubah {$ticket->status->value} menjadi {$target->value}.",
            ]);
        }

        $from = $ticket->status;

        $ticket->status = $target;
        $ticket->last_activity_at = now();
        $ticket->save();

        $body = "Status diubah dari {$from->label()} menjadi {$target->label()}.";

        if ($note !== null && trim($note) !== '') {
            $body .= "\n\n".trim($note);
        }

        TicketMessage::query()->create([
            'ticket_id' => $ticket->getKey(),
            'user_id' => $actor?->getKey(),
            'type' => MessageType::Event,
            'body' => $body,
            'is_internal' => false,
        ]);

        $this->audit->log(
            'ticket.status_changed',
            "Status {$ticket->ticket_number} diubah dari {$from->label()} menjadi {$target->label()}.",
            $ticket,
            ['from' => $from->value, 'to' => $target->value],
            null,
            $actor,
        );

        return $ticket->refresh();
    }

    public function openIfNew(Ticket $ticket, ?User $actor = null): Ticket
    {
        if ($ticket->status !== TicketStatus::New) {
            return $ticket;
        }

        return $this->transition($ticket, TicketStatus::Open, $actor);
    }
}
