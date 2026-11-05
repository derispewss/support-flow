<?php

namespace App\Modules\Support\Livewire\Admin;

use App\Modules\Identity\Models\User;
use App\Modules\Support\Models\CannedResponse;
use App\Modules\Support\Models\Ticket;
use App\Modules\Support\Services\AuditLogger;
use App\Modules\Support\Services\TicketMessageService;
use App\Modules\Support\Services\TicketStatusService;
use App\Modules\Tenancy\Models\Membership;
use App\Modules\Tenancy\Models\Organization;
use App\Shared\Enums\MessageType;
use App\Shared\Enums\TicketPriority;
use App\Shared\Enums\TicketStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

class TicketDetail extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $ticketId;

    #[Locked]
    public int $organizationId;

    public string $reply = '';

    public bool $internal = false;

    /** @var array<int, UploadedFile> */
    public array $files = [];

    public ?int $cannedResponseId = null;

    public string $status = '';

    public string $statusNote = '';

    public string $assigneeId = '';

    public string $priority = '';

    public string $categoryId = '';

    /** @var array<int, int|string> */
    public array $tagIds = [];

    public string $watcherId = '';

    public string $notice = '';

    public function mount(Ticket $ticket): void
    {
        $organization = tenant();

        abort_unless($organization instanceof Organization, 403);
        abort_unless($ticket->organization_id === $organization->getKey(), 404);

        Gate::authorize('view', $ticket);

        $this->organizationId = (int) $organization->getKey();
        $this->ticketId = (int) $ticket->getKey();

        $this->syncState($ticket);
    }

    public function hydrate(): void
    {
        Gate::authorize('view', $this->ticket());
    }

    public function postReply(): void
    {
        $user = auth()->user();
        $ticket = $this->ticket();

        abort_unless($user instanceof User, 403);

        Gate::authorize('addMessage', [$ticket, $this->internal]);

        $this->validate([
            'reply' => ['nullable', 'string', 'max:20000', 'required_without:files'],
            'files' => ['array', 'max:5'],
            'files.*' => [
                'file',
                'max:10240',
                'mimes:png,jpg,jpeg,gif,webp,pdf,txt,csv,zip,doc,docx,xls,xlsx',
            ],
        ], [
            'reply.required_without' => 'Tulis balasan atau lampirkan berkas.',
            'files.max' => 'Maksimal 5 lampiran per pesan.',
            'files.*.max' => 'Ukuran setiap lampiran maksimal 10 MB.',
        ]);

        $internal = $this->internal;
        $body = trim($this->reply) !== '' ? trim($this->reply) : 'Lampiran dikirim.';

        app(TicketMessageService::class)->post(
            $ticket,
            $user,
            $body,
            $internal ? MessageType::Note : MessageType::Reply,
            $this->files,
        );

        $this->reset(['reply', 'files', 'internal', 'cannedResponseId']);
        $this->refreshTicket();

        $this->notice = $internal
            ? 'Catatan internal tersimpan. Customer tidak melihatnya.'
            : 'Balasan terkirim.';
    }

    public function changeStatus(): void
    {
        $user = auth()->user();
        $ticket = $this->ticket();

        abort_unless($user instanceof User, 403);
        Gate::authorize('changeStatus', $ticket);

        $this->validate([
            'status' => ['required', Rule::enum(TicketStatus::class)],
            'statusNote' => ['nullable', 'string', 'max:500'],
        ]);

        app(TicketStatusService::class)->transition(
            $ticket,
            TicketStatus::from($this->status),
            $user,
            $this->statusNote !== '' ? $this->statusNote : null,
        );

        $this->status = '';
        $this->statusNote = '';
        $this->refreshTicket();
        $this->notice = 'Status tiket diperbarui.';
    }

    public function saveAssignment(): void
    {
        $ticket = $this->ticket();
        $organization = $this->organization();

        Gate::authorize('assign', $ticket);

        $assignee = $this->assigneeId === '' ? null : (int) $this->assigneeId;
        $staffIds = $this->staffIds($organization);

        if ($assignee !== null && ! in_array($assignee, $staffIds, true)) {
            throw ValidationException::withMessages([
                'assigneeId' => 'Pilih staf aktif dari organisasi ini.',
            ]);
        }

        $previous = $ticket->assignee_id;

        $ticket->forceFill([
            'assignee_id' => $assignee,
            'last_activity_at' => now(),
        ])->save();

        $assigneeName = $assignee === null ? null : $ticket->assignee()->first()?->name;

        $this->audit()->log(
            'ticket.assigned',
            $assignee === null
                ? "Penugasan {$ticket->ticket_number} dilepas."
                : "{$ticket->ticket_number} ditugaskan ke ".($assigneeName ?? 'staf').'.',
            $ticket,
            ['from' => $previous, 'to' => $assignee],
        );

        $this->refreshTicket();
        $this->notice = 'Penugasan diperbarui.';
    }

    public function saveClassification(): void
    {
        $ticket = $this->ticket();
        $organization = $this->organization();

        Gate::authorize('assign', $ticket);

        $this->validate([
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'categoryId' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where('organization_id', $organization->getKey()),
            ],
        ], [
            'categoryId.exists' => 'Kategori tidak tersedia untuk organisasi ini.',
        ]);

        $previousPriority = $ticket->priority->value;
        $previousCategory = $ticket->category_id;

        $ticket->forceFill([
            'priority' => TicketPriority::from($this->priority),
            'category_id' => $this->categoryId === '' ? null : (int) $this->categoryId,
            'last_activity_at' => now(),
        ])->save();

        $this->audit()->log(
            'ticket.classified',
            "Klasifikasi {$ticket->ticket_number} diperbarui.",
            $ticket,
            [
                'priority' => ['from' => $previousPriority, 'to' => $this->priority],
                'category_id' => ['from' => $previousCategory, 'to' => $ticket->category_id],
            ],
        );

        $this->refreshTicket();
        $this->notice = 'Klasifikasi tiket diperbarui.';
    }

    public function saveTags(): void
    {
        $ticket = $this->ticket();
        $organization = $this->organization();

        Gate::authorize('assign', $ticket);

        $this->validate([
            'tagIds' => ['array'],
            'tagIds.*' => ['integer'],
        ]);

        $requested = array_values(array_unique(array_map('intval', $this->tagIds)));
        $valid = $organization->tags()->whereKey($requested)->pluck('id')->all();

        if (count($valid) !== count($requested)) {
            throw ValidationException::withMessages([
                'tagIds' => 'Ada tag yang bukan milik organisasi ini.',
            ]);
        }

        $ticket->tags()->sync($valid);

        $this->audit()->log(
            'ticket.tags_updated',
            "Tag {$ticket->ticket_number} diperbarui.",
            $ticket,
            ['tags' => $valid],
        );

        $this->refreshTicket();
        $this->notice = 'Tag tiket disimpan.';
    }

    public function addWatcher(): void
    {
        $ticket = $this->ticket();
        $organization = $this->organization();

        Gate::authorize('manageWatchers', $ticket);

        $this->validate([
            'watcherId' => ['required', 'integer'],
        ], [
            'watcherId.required' => 'Pilih anggota yang ingin dipantau tiketnya.',
        ]);

        $watcherId = (int) $this->watcherId;
        $memberIds = $organization->memberships()->pluck('user_id')->all();

        if (! in_array($watcherId, $memberIds, true)) {
            throw ValidationException::withMessages([
                'watcherId' => 'Anggota tidak ditemukan pada organisasi ini.',
            ]);
        }

        $ticket->watchers()->syncWithoutDetaching([$watcherId]);

        $this->audit()->log(
            'ticket.watcher_added',
            "Watcher ditambahkan ke {$ticket->ticket_number}.",
            $ticket,
            ['user_id' => $watcherId],
        );

        $this->watcherId = '';
        $this->refreshTicket();
        $this->notice = 'Watcher ditambahkan.';
    }

    public function removeWatcher(int $userId): void
    {
        $ticket = $this->ticket();

        Gate::authorize('manageWatchers', $ticket);

        $ticket->watchers()->detach($userId);

        $this->audit()->log(
            'ticket.watcher_removed',
            "Watcher dihapus dari {$ticket->ticket_number}.",
            $ticket,
            ['user_id' => $userId],
        );

        $this->refreshTicket();
        $this->notice = 'Watcher dihapus.';
    }

    public function applyCanned(int $cannedResponseId): void
    {
        $canned = $this->organization()
            ->cannedResponses()
            ->whereKey($cannedResponseId)
            ->first();

        if ($canned instanceof CannedResponse) {
            $this->reply = $canned->body;
            $this->cannedResponseId = (int) $canned->getKey();
        }
    }

    public function deleteTicket(): mixed
    {
        $ticket = $this->ticket();

        Gate::authorize('delete', $ticket);

        $this->audit()->log(
            'ticket.deleted',
            "Tiket {$ticket->ticket_number} dihapus.",
            $ticket,
            ['ticket_number' => $ticket->ticket_number],
        );

        $ticket->delete();

        session()->flash('status', 'Tiket dihapus.');

        return $this->redirectRoute('admin.tickets.index', navigate: true);
    }

    public function render(): View
    {
        $ticket = $this->ticket();
        $organization = $this->organization();

        return view('livewire.admin.ticket-detail', [
            'ticket' => $ticket,
            'messages' => $ticket->messages()->with(['user', 'attachments'])->get(),
            'allowedTransitions' => $ticket->status->allowedTransitions(),
            'agents' => $this->staff($organization),
            'watchers' => $ticket->watchers()->orderBy('name')->get(),
            'categories' => $organization->categories()->orderBy('name')->get(),
            'tags' => $organization->tags()->orderBy('name')->get(),
            'cannedResponses' => $organization->cannedResponses()->orderBy('title')->get(),
            'canDelete' => Gate::allows('delete', $ticket),
            'canManageTicket' => Gate::allows('assign', $ticket),
            'hasInternalNotes' => $ticket->messages()->where('is_internal', true)->exists(),
        ]);
    }

    protected function syncState(Ticket $ticket): void
    {
        $this->assigneeId = (string) ($ticket->assignee_id ?? '');
        $this->priority = $ticket->priority->value;
        $this->categoryId = (string) ($ticket->category_id ?? '');
        $this->tagIds = $ticket->tags()->pluck('tags.id')->map(fn ($id) => (int) $id)->all();
    }

    protected function refreshTicket(): void
    {
        $ticket = $this->ticket()->refresh();
        $ticket->load(['requester', 'assignee', 'category', 'tags', 'slaPolicy']);

        $this->syncState($ticket);
    }

    protected function ticket(): Ticket
    {
        $ticket = Ticket::query()
            ->where('organization_id', $this->organizationId)
            ->with(['requester', 'assignee', 'category', 'tags', 'slaPolicy', 'team'])
            ->find($this->ticketId);

        abort_unless($ticket instanceof Ticket, 404);

        return $ticket;
    }

    protected function organization(): Organization
    {
        $organization = Organization::query()->find($this->organizationId);

        abort_unless($organization instanceof Organization, 403);

        return $organization;
    }

    /**
     * @return array<int, int>
     */
    protected function staffIds(Organization $organization): array
    {
        return $organization->memberships()
            ->active()
            ->whereIn('role', ['owner', 'admin', 'agent'])
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return Collection<int, Membership>
     */
    protected function staff(Organization $organization): Collection
    {
        return $organization->memberships()
            ->active()
            ->whereIn('role', ['owner', 'admin', 'agent'])
            ->with('user')
            ->get()
            ->sortBy(fn ($membership) => $membership->user?->name)
            ->values();
    }

    protected function audit(): AuditLogger
    {
        return app(AuditLogger::class);
    }
}
