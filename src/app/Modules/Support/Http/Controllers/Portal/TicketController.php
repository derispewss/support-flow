<?php

namespace App\Modules\Support\Http\Controllers\Portal;

use App\Modules\Identity\Models\User;
use App\Modules\Support\Http\Requests\Portal\StoreTicketRequest;
use App\Modules\Support\Models\Attachment;
use App\Modules\Support\Models\Category;
use App\Modules\Support\Models\SlaPolicy;
use App\Modules\Support\Models\Ticket;
use App\Modules\Support\Models\TicketMessage;
use App\Modules\Support\Services\TicketService;
use App\Modules\Tenancy\Models\Organization;
use App\Shared\Enums\TicketChannel;
use App\Shared\Enums\TicketPriority;
use App\Shared\Enums\TicketStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketController
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $organization = tenant();

        abort_unless($user instanceof User && $organization instanceof Organization, 403);

        $status = $request->query('status');
        $status = is_string($status) ? TicketStatus::tryFrom($status) : null;

        $search = $request->query('search');
        $search = is_string($search) ? trim($search) : '';

        $query = Ticket::query()
            ->forOrganization($organization)
            ->visibleTo($user)
            ->with('category');

        if ($status instanceof TicketStatus) {
            $query->where('status', $status->value);
        }

        if ($search !== '') {
            $term = '%'.addcslashes($search, '%_\\').'%';
            $query->where(function ($builder) use ($term): void {
                $builder->where('subject', 'like', $term)
                    ->orWhere('ticket_number', 'like', $term);
            });
        }

        $tickets = $query
            ->orderByDesc('last_activity_at')
            ->paginate(10)
            ->withQueryString();

        return view('pages.portal.tickets', [
            'tickets' => $tickets,
            'status' => $status,
            'search' => $search,
            'statuses' => TicketStatus::cases(),
        ]);
    }

    public function create(): View
    {
        $organization = tenant();

        abort_unless($organization instanceof Organization, 403);

        $categories = Category::query()
            ->where('organization_id', $organization->getKey())
            ->active()
            ->get();

        $sla = SlaPolicy::query()
            ->where('organization_id', $organization->getKey())
            ->where('is_default', true)
            ->first();

        return view('pages.portal.ticket-create', [
            'categories' => $categories,
            'priorities' => TicketPriority::cases(),
            'sla' => $sla,
        ]);
    }

    public function store(StoreTicketRequest $request, TicketService $tickets): RedirectResponse
    {
        $user = $request->user();
        $organization = tenant();

        abort_unless($user instanceof User && $organization instanceof Organization, 403);

        Gate::authorize('create', [Ticket::class, $organization]);

        $files = $request->file('attachments');
        $files = is_array($files) ? array_values(array_filter($files)) : [];

        $ticket = $tickets->create($user, $organization, [
            ...$request->validated(),
            'channel' => TicketChannel::Portal->value,
        ], $files);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'Tiket '.$ticket->ticket_number.' berhasil dibuat.');
    }

    public function show(Request $request, int $ticket): View
    {
        $model = $this->findVisibleTicket($request, $ticket);

        $isStaff = $this->isStaff($request, $model);

        $messages = $model->messages()
            ->with(['user', 'attachments'])
            ->when(! $isStaff, fn ($query) => $query->visibleToCustomer())
            ->get();

        $model->load(['category', 'assignee', 'tags', 'attachments']);

        return view('pages.portal.ticket-show', [
            'ticket' => $model,
            'messages' => $messages,
            'isStaff' => $isStaff,
        ]);
    }

    public function download(Request $request, int $ticket, int $attachment): StreamedResponse
    {
        $model = $this->findVisibleTicket($request, $ticket);

        $file = Attachment::query()
            ->where('organization_id', $model->organization_id)
            ->whereKey($attachment)
            ->first();

        if ($file === null || ! $this->belongsToTicket($file, $model)) {
            abort(404);
        }

        Gate::authorize('view', $file);

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    public function preview(Request $request, int $ticket, int $attachment): StreamedResponse
    {
        $model = $this->findVisibleTicket($request, $ticket);

        $file = Attachment::query()
            ->where('organization_id', $model->organization_id)
            ->whereKey($attachment)
            ->first();

        if ($file === null || ! $this->belongsToTicket($file, $model)) {
            abort(404);
        }

        Gate::authorize('view', $file);

        return Storage::disk($file->disk)->response($file->path, $file->original_name, [
            'Content-Disposition' => 'inline; filename="'.addslashes($file->original_name).'"',
        ]);
    }

    protected function findVisibleTicket(Request $request, int $id): Ticket
    {
        $organization = tenant();

        abort_unless($organization instanceof Organization, 403);

        $ticket = Ticket::query()
            ->forOrganization($organization)
            ->whereKey($id)
            ->first();

        if ($ticket === null) {
            abort(404);
        }

        Gate::authorize('view', $ticket);

        return $ticket;
    }

    protected function isStaff(Request $request, Ticket $ticket): bool
    {
        $user = $request->user();

        return $user instanceof User && $user->isStaffIn($ticket->organization);
    }

    protected function belongsToTicket(Attachment $attachment, Ticket $ticket): bool
    {
        $attachable = $attachment->attachable;

        if ($attachable instanceof Ticket) {
            return $attachable->getKey() === $ticket->getKey();
        }

        if ($attachable instanceof TicketMessage) {
            return $attachable->ticket_id === $ticket->getKey();
        }

        return false;
    }
}
