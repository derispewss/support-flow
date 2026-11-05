<?php

namespace App\Modules\Support\Http\Controllers\Portal;

use App\Modules\Identity\Models\User;
use App\Modules\Support\Http\Requests\Portal\StoreTicketMessageRequest;
use App\Modules\Support\Models\Ticket;
use App\Modules\Support\Services\TicketMessageService;
use App\Modules\Tenancy\Models\Organization;
use App\Shared\Enums\MessageType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TicketMessageController
{
    public function store(
        StoreTicketMessageRequest $request,
        int $ticket,
        TicketMessageService $messages,
    ): RedirectResponse {
        $organization = tenant();
        $user = $request->user();

        abort_unless($organization instanceof Organization && $user instanceof User, 403);

        $model = Ticket::query()
            ->forOrganization($organization)
            ->whereKey($ticket)
            ->first();

        if ($model === null) {
            abort(404);
        }

        Gate::authorize('view', $model);
        Gate::authorize('addMessage', [$model, false]);

        $files = $request->file('attachments');
        $files = is_array($files) ? array_values(array_filter($files)) : [];

        $messages->post(
            $model,
            $user,
            $request->string('body')->toString(),
            MessageType::Reply,
            $files,
        );

        return redirect()
            ->route('tickets.show', $model)
            ->with('status', 'Balasan Anda sudah dikirim.');
    }
}
