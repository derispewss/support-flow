<?php

namespace App\Modules\Support\Policies;

use App\Modules\Identity\Models\User;
use App\Modules\Support\Models\Attachment;
use App\Modules\Support\Models\Ticket;
use App\Modules\Support\Models\TicketMessage;

class AttachmentPolicy
{
    public function __construct(protected TicketPolicy $tickets) {}

    public function view(User $user, Attachment $attachment): bool
    {
        $ticket = $this->ticketFor($attachment);

        return $ticket !== null && $this->tickets->view($user, $ticket);
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        $ticket = $this->ticketFor($attachment);

        if ($ticket === null) {
            return false;
        }

        return $this->tickets->deleteAttachment($user, $ticket, $attachment);
    }

    protected function ticketFor(Attachment $attachment): ?Ticket
    {
        $attachable = $attachment->attachable;

        if ($attachable instanceof Ticket) {
            return $attachable;
        }

        if ($attachable instanceof TicketMessage) {
            return $attachable->ticket;
        }

        return null;
    }
}
