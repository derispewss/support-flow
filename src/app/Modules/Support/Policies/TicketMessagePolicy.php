<?php

namespace App\Modules\Support\Policies;

use App\Modules\Identity\Models\User;
use App\Modules\Support\Models\TicketMessage;
use App\Shared\Enums\MessageType;

class TicketMessagePolicy
{
    public function __construct(protected TicketPolicy $tickets) {}

    public function view(User $user, TicketMessage $message): bool
    {
        $ticket = $message->ticket;

        if ($ticket === null || ! $this->tickets->view($user, $ticket)) {
            return false;
        }

        if ($message->is_internal || $message->type === MessageType::Note) {
            return $user->isStaffIn($ticket->organization);
        }

        return true;
    }

    public function create(User $user, TicketMessage $message): bool
    {
        $ticket = $message->ticket;

        return $ticket !== null && $this->tickets->addMessage($user, $ticket, $message->is_internal);
    }
}
