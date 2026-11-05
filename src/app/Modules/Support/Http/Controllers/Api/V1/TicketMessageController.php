<?php

namespace App\Modules\Support\Http\Controllers\Api\V1;

use App\Modules\Identity\Models\User;
use App\Modules\Support\Http\Requests\Api\StoreTicketMessageRequest;
use App\Modules\Support\Http\Resources\TicketMessageResource;
use App\Modules\Support\Models\Ticket;
use App\Modules\Support\Services\TicketMessageService;
use App\Modules\Tenancy\Models\Organization;
use App\Shared\Enums\MessageType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class TicketMessageController
{
    public function index(Request $request, int $ticket): AnonymousResourceCollection
    {
        $model = $this->findTicket($ticket);

        Gate::authorize('view', $model);

        $query = $model->messages()->with(['user', 'attachments']);

        if (! $this->isStaff($request, $model)) {
            $query->visibleToCustomer();
        }

        $perPage = max(1, min((int) $request->input('per_page', 50), 100));

        $results = $request->boolean('cursor')
            ? $query->orderByDesc('id')->cursorPaginate($perPage)->withQueryString()
            : $query->paginate($perPage)->withQueryString();

        return TicketMessageResource::collection($results);
    }

    public function store(StoreTicketMessageRequest $request, int $ticket, TicketMessageService $messages): JsonResponse
    {
        $model = $this->findTicket($ticket);

        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $type = MessageType::tryFrom($request->input('type', MessageType::Reply->value)) ?? MessageType::Reply;
        $internal = $type === MessageType::Note || $request->boolean('is_internal');

        if ($internal) {
            $type = MessageType::Note;
        }

        Gate::authorize('addMessage', [$model, $internal]);

        $files = $request->file('attachments');
        $files = is_array($files) ? array_values(array_filter($files)) : [];

        $message = $messages->post(
            $model,
            $user,
            $request->string('body')->toString(),
            $type,
            $files,
        );

        return (new TicketMessageResource($message))->response()->setStatusCode(201);
    }

    protected function findTicket(int $id): Ticket
    {
        $organization = tenant();

        abort_unless($organization instanceof Organization, 403);

        $ticket = Ticket::query()
            ->forOrganization($organization)
            ->whereKey($id)
            ->first();

        if ($ticket === null) {
            abort(404, 'Resource not found.');
        }

        return $ticket;
    }

    protected function isStaff(Request $request, Ticket $ticket): bool
    {
        $user = $request->user();

        return $user instanceof User && $user->isStaffIn($ticket->organization);
    }
}
