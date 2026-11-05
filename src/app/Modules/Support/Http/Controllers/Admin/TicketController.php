<?php

namespace App\Modules\Support\Http\Controllers\Admin;

use App\Modules\Support\Models\Ticket;
use App\Modules\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TicketController
{
    public function index(): View
    {
        return view('pages.admin.tickets.index');
    }

    public function show(Ticket $ticket, TenantContext $context): View
    {
        $organization = $context->organization();

        abort_unless($organization instanceof Organization, 403);
        abort_unless($ticket->organization_id === $organization->getKey(), 404);

        Gate::authorize('view', $ticket);

        return view('pages.admin.tickets.show', [
            'ticket' => $ticket,
        ]);
    }
}
