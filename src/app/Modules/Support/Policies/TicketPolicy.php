<?php

namespace App\Modules\Support\Policies;

use App\Modules\Identity\Models\User;
use App\Modules\Support\Models\Attachment;
use App\Modules\Support\Models\Ticket;
use App\Modules\Tenancy\Models\Organization;
use App\Shared\Enums\UserRole;

class TicketPolicy
{
    public function viewAny(User $user, Organization $organization): bool
    {
        return $this->role($user, $organization) !== null;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        $role = $this->roleInTicket($user, $ticket);

        if ($role === null) {
            return false;
        }

        if ($role->isStaff()) {
            return true;
        }

        return $ticket->requester_id === $user->getKey()
            || $ticket->watchers()->whereKey($user->getKey())->exists();
    }

    public function create(User $user, Organization $organization): bool
    {
        return $this->role($user, $organization) !== null;
    }

    public function update(User $user, Ticket $ticket): bool
    {
        $role = $this->roleInTicket($user, $ticket);

        if ($role === null) {
            return false;
        }

        if ($role->isStaff()) {
            return true;
        }

        return $ticket->requester_id === $user->getKey() && $ticket->isOpen();
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $this->roleInTicket($user, $ticket)?->canManageOrganization() ?? false;
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $this->roleInTicket($user, $ticket)?->canManageOrganization() ?? false;
    }

    public function changeStatus(User $user, Ticket $ticket): bool
    {
        return $this->roleInTicket($user, $ticket)?->isStaff() ?? false;
    }

    public function addMessage(User $user, Ticket $ticket, bool $internal = false): bool
    {
        if (! $this->view($user, $ticket)) {
            return false;
        }

        if (! $internal) {
            return true;
        }

        return $this->roleInTicket($user, $ticket)?->isStaff() ?? false;
    }

    public function addAttachment(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }

    public function deleteAttachment(User $user, Ticket $ticket, Attachment $attachment): bool
    {
        $role = $this->roleInTicket($user, $ticket);

        if ($role === null) {
            return false;
        }

        if ($role->isStaff()) {
            return true;
        }

        return $attachment->user_id === $user->getKey() && $ticket->requester_id === $user->getKey();
    }

    public function manageWatchers(User $user, Ticket $ticket): bool
    {
        return $this->roleInTicket($user, $ticket)?->canManageOrganization() ?? false;
    }

    protected function roleInTicket(User $user, Ticket $ticket): ?UserRole
    {
        return $user->roleIn($ticket->organization);
    }

    protected function role(User $user, Organization $organization): ?UserRole
    {
        return $user->roleIn($organization);
    }
}
