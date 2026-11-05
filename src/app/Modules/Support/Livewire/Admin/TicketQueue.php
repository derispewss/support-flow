<?php

namespace App\Modules\Support\Livewire\Admin;

use App\Modules\Support\Models\Ticket;
use App\Modules\Tenancy\Models\Membership;
use App\Modules\Tenancy\Models\Organization;
use App\Shared\Enums\TicketPriority;
use App\Shared\Enums\TicketStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TicketQueue extends Component
{
    use WithPagination;

    #[Locked]
    public int $organizationId;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $priority = '';

    #[Url(except: 'all')]
    public string $assignee = 'all';

    #[Url(except: '')]
    public string $category = '';

    #[Url(except: '')]
    public string $tag = '';

    #[Url(except: 'all')]
    public string $view = 'all';

    #[Url(except: 'recent')]
    public string $sort = 'recent';

    public int $perPage = 15;

    public function mount(): void
    {
        $organization = tenant();

        abort_unless($organization instanceof Organization, 403);

        $this->organizationId = (int) $organization->getKey();

        $this->authorizeAccess();
        $this->normalizeFilters();
    }

    public function hydrate(): void
    {
        $this->authorizeAccess();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status', 'priority', 'assignee', 'category', 'tag', 'view', 'sort'], true)) {
            $this->resetPage();
        }
    }

    public function setView(string $view): void
    {
        $this->view = $view;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'status', 'priority', 'category', 'tag']);
        $this->assignee = 'all';
        $this->view = 'all';
        $this->sort = 'recent';
        $this->resetPage();
    }

    public function render(): View
    {
        $organization = $this->organization();

        $tickets = $this->query($organization)
            ->with(['requester', 'assignee', 'category', 'tags'])
            ->paginate($this->perPage)
            ->withQueryString();

        return view('livewire.admin.ticket-queue', [
            'tickets' => $tickets,
            'statuses' => TicketStatus::cases(),
            'priorities' => TicketPriority::cases(),
            'agents' => $this->staff($organization),
            'categories' => $organization->categories()->orderBy('name')->get(),
            'tags' => $organization->tags()->orderBy('name')->get(),
            'counts' => $this->counts($organization),
        ]);
    }

    /**
     * @return Builder<Ticket>
     */
    protected function query(Organization $organization): Builder
    {
        $query = Ticket::query()->forOrganization($organization);

        $this->applyView($query);
        $this->applyFilters($query);
        $this->applySort($query);

        return $query;
    }

    /**
     * @param  Builder<Ticket>  $query
     */
    protected function applyView(Builder $query): void
    {
        $user = auth()->user();

        match ($this->view) {
            'unassigned' => $query->open()->whereNull('assignee_id'),
            'mine' => $query->open()->where('assignee_id', $user?->getKey()),
            'overdue' => $query->open()->whereNotNull('due_at')->where('due_at', '<', now()),
            default => $query,
        };
    }

    /**
     * @param  Builder<Ticket>  $query
     */
    protected function applyFilters(Builder $query): void
    {
        if ($this->status !== '' && TicketStatus::tryFrom($this->status) !== null) {
            $query->where('status', $this->status);
        }

        if ($this->priority !== '' && TicketPriority::tryFrom($this->priority) !== null) {
            $query->where('priority', $this->priority);
        }

        if ($this->assignee === 'unassigned') {
            $query->whereNull('assignee_id');
        } elseif ($this->assignee === 'me') {
            $query->where('assignee_id', auth()->id());
        } elseif (ctype_digit($this->assignee)) {
            $query->where('assignee_id', (int) $this->assignee);
        }

        if (ctype_digit($this->category)) {
            $query->where('category_id', (int) $this->category);
        }

        if (ctype_digit($this->tag)) {
            $query->whereHas('tags', fn (Builder $tags) => $tags->whereKey((int) $this->tag));
        }

        $search = trim($this->search);

        if ($search !== '') {
            $needle = '%'.addcslashes($search, '%_\\').'%';

            $query->where(function (Builder $builder) use ($needle): void {
                $builder->where('ticket_number', 'like', $needle)
                    ->orWhere('subject', 'like', $needle)
                    ->orWhere('description', 'like', $needle)
                    ->orWhereHas('requester', function (Builder $requester) use ($needle): void {
                        $requester->where('name', 'like', $needle)
                            ->orWhere('email', 'like', $needle);
                    });
            });
        }
    }

    /**
     * @param  Builder<Ticket>  $query
     */
    protected function applySort(Builder $query): void
    {
        match ($this->sort) {
            'oldest' => $query->orderBy('created_at'),
            'priority' => $query
                ->orderByRaw("case priority when 'urgent' then 4 when 'high' then 3 when 'normal' then 2 else 1 end desc")
                ->orderByDesc('created_at'),
            'due' => $query->orderByRaw('due_at is null')->orderBy('due_at'),
            default => $query->orderByDesc('last_activity_at')->orderByDesc('id'),
        };
    }

    /**
     * @return array<string, int>
     */
    protected function counts(Organization $organization): array
    {
        $base = Ticket::query()->forOrganization($organization);

        return [
            'all' => (clone $base)->count(),
            'unassigned' => (clone $base)->open()->whereNull('assignee_id')->count(),
            'mine' => (clone $base)->open()->where('assignee_id', auth()->id())->count(),
            'overdue' => (clone $base)->open()->whereNotNull('due_at')->where('due_at', '<', now())->count(),
        ];
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

    protected function organization(): Organization
    {
        $organization = Organization::query()->find($this->organizationId);

        abort_unless($organization instanceof Organization, 403);

        return $organization;
    }

    protected function authorizeAccess(): void
    {
        Gate::authorize('viewAny', [Ticket::class, $this->organization()]);
    }

    protected function normalizeFilters(): void
    {
        if (! in_array($this->view, ['all', 'unassigned', 'mine', 'overdue'], true)) {
            $this->view = 'all';
        }

        if (! in_array($this->sort, ['recent', 'oldest', 'priority', 'due'], true)) {
            $this->sort = 'recent';
        }

        $this->perPage = max(5, min(50, $this->perPage));
    }
}
