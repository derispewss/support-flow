<?php

namespace App\Modules\Support\Http\Controllers\Api\V1;

use App\Modules\Identity\Models\User;
use App\Modules\Support\Http\Requests\Api\AssignTicketRequest;
use App\Modules\Support\Http\Requests\Api\StoreTicketRequest;
use App\Modules\Support\Http\Requests\Api\TicketStatusRequest;
use App\Modules\Support\Http\Requests\Api\UpdateTicketRequest;
use App\Modules\Support\Http\Resources\TicketResource;
use App\Modules\Support\Models\Ticket;
use App\Modules\Support\Services\TicketService;
use App\Modules\Support\Services\TicketStatusService;
use App\Modules\Tenancy\Models\Organization;
use App\Shared\Enums\TicketChannel;
use App\Shared\Enums\TicketPriority;
use App\Shared\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class TicketController
{
    /**
     * @var array<int, string>
     */
    protected array $allowedFilters = [
        'status',
        'priority',
        'channel',
        'category_id',
        'assignee_id',
        'requester_id',
        'team_id',
        'tag',
        'overdue',
        'search',
        'created_from',
        'created_to',
    ];

    /**
     * @var array<int, string>
     */
    protected array $allowedIncludes = [
        'category',
        'requester',
        'assignee',
        'tags',
        'team',
        'sla_policy',
        'attachments',
    ];

    /**
     * @var array<int, string>
     */
    protected array $allowedSorts = [
        'created_at',
        'updated_at',
        'priority',
        'status',
        'ticket_number',
        'last_activity_at',
        'due_at',
    ];

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $organization = tenant();

        abort_unless($user instanceof User && $organization instanceof Organization, 403);

        $filters = $this->validatedFilters($request);

        $query = Ticket::query()
            ->forOrganization($organization)
            ->with(['category', 'requester', 'assignee', 'tags']);

        if (! $user->isStaffIn($organization)) {
            $query->visibleTo($user);
        }

        $this->applyFilters($query, $filters);

        $this->applyIncludes($query, $request->input('include'));
        $this->applySort($query, $request->input('sort'));

        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(1, min($perPage, 100));

        return TicketResource::collection(
            $query->paginate($perPage)->withQueryString(),
        );
    }

    public function store(StoreTicketRequest $request, TicketService $tickets): JsonResponse
    {
        $user = $request->user();
        $organization = tenant();

        abort_unless($user instanceof User && $organization instanceof Organization, 403);

        Gate::authorize('create', [Ticket::class, $organization]);

        $data = $request->validated();
        $data['channel'] = $data['channel'] ?? TicketChannel::Api->value;

        $files = $request->file('attachments');
        $files = is_array($files) ? array_values(array_filter($files)) : [];

        $ticket = $tickets->create($user, $organization, $data, $files);

        if (is_array($request->input('tag_ids'))) {
            $ticket->tags()->sync($request->input('tag_ids'));
        }

        return (new TicketResource($ticket->load(['category', 'requester', 'assignee', 'tags', 'attachments', 'slaPolicy'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, int $ticket): TicketResource
    {
        $model = $this->findTicket($ticket);

        Gate::authorize('view', $model);

        $model->load(['category', 'requester', 'assignee', 'tags', 'team', 'slaPolicy', 'attachments']);

        return new TicketResource($model);
    }

    public function update(UpdateTicketRequest $request, int $ticket): TicketResource
    {
        $model = $this->findTicket($ticket);

        Gate::authorize('update', $model);

        $data = $request->validated();

        if ($request->has('assignee_id')) {
            Gate::authorize('assign', $model);
        }

        $model->fill($data);

        if ($request->has('assignee_id') && $model->status === TicketStatus::New && $model->assignee_id !== null) {
            $model->status = TicketStatus::Open;
        }

        $model->save();

        if (is_array($request->input('tag_ids'))) {
            $model->tags()->sync($request->input('tag_ids'));
        }

        return new TicketResource($model->load(['category', 'requester', 'assignee', 'tags', 'team', 'slaPolicy']));
    }

    public function destroy(int $ticket): JsonResponse
    {
        $model = $this->findTicket($ticket);

        Gate::authorize('delete', $model);

        $model->delete();

        return response()->json(['message' => 'Tiket dihapus.']);
    }

    public function assign(AssignTicketRequest $request, Ticket $ticket): TicketResource
    {
        abort_unless($ticket->organization_id === tenant()?->getKey(), 404);

        Gate::authorize('assign', $ticket);

        $ticket->fill($request->validated());
        $ticket->save();

        if ($ticket->assignee_id !== null && $ticket->status === TicketStatus::New) {
            app(TicketStatusService::class)->openIfNew($ticket, $request->user());
        }

        return new TicketResource($ticket->load(['category', 'requester', 'assignee', 'tags', 'team']));
    }

    public function status(TicketStatusRequest $request, Ticket $ticket, TicketStatusService $statuses): TicketResource
    {
        abort_unless($ticket->organization_id === tenant()?->getKey(), 404);

        Gate::authorize('changeStatus', $ticket);

        $status = TicketStatus::from($request->string('status')->toString());
        $statuses->transition($ticket, $status, $request->user(), $request->input('note'));

        return new TicketResource($ticket->load(['category', 'requester', 'assignee', 'tags', 'team', 'slaPolicy']));
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

    /**
     * @return array<string, mixed>
     */
    protected function validatedFilters(Request $request): array
    {
        $filters = $request->input('filter', []);

        if (! is_array($filters)) {
            throw ValidationException::withMessages(['filter' => 'Format filter tidak valid.']);
        }

        $unknown = array_diff(array_keys($filters), $this->allowedFilters);

        if ($unknown !== []) {
            throw ValidationException::withMessages([
                'filter' => 'Filter tidak dikenal: '.implode(', ', $unknown).'.',
            ]);
        }

        $request->validate([
            'filter.status' => ['sometimes', 'string'],
            'filter.priority' => ['sometimes', 'string'],
            'filter.channel' => ['sometimes', 'string'],
            'filter.category_id' => ['sometimes', 'integer'],
            'filter.assignee_id' => ['sometimes', 'integer'],
            'filter.requester_id' => ['sometimes', 'integer'],
            'filter.team_id' => ['sometimes', 'integer'],
            'filter.tag' => ['sometimes', 'string', 'max:120'],
            'filter.overdue' => ['sometimes', 'in:true,false,1,0'],
            'filter.search' => ['sometimes', 'string', 'max:120'],
            'filter.created_from' => ['sometimes', 'date'],
            'filter.created_to' => ['sometimes', 'date'],
            'sort' => ['sometimes', 'string'],
            'include' => ['sometimes', 'string'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $this->assertAllowedCsv($filters['status'] ?? null, TicketStatus::class, 'filter.status');
        $this->assertAllowedCsv($filters['priority'] ?? null, TicketPriority::class, 'filter.priority');
        $this->assertAllowedCsv($filters['channel'] ?? null, TicketChannel::class, 'filter.channel');
        $this->assertAllowedList((string) $request->input('sort', ''), $this->allowedSorts, 'sort');
        $this->assertAllowedList((string) $request->input('include', ''), $this->allowedIncludes, 'include');

        return $filters;
    }

    /**
     * @param  Builder<Ticket>  $query
     * @param  array<string, mixed>  $filters
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['status']) && is_string($filters['status'])) {
            $query->whereIn('status', explode(',', $filters['status']));
        }

        if (isset($filters['priority']) && is_string($filters['priority'])) {
            $query->whereIn('priority', explode(',', $filters['priority']));
        }

        if (isset($filters['channel']) && is_string($filters['channel'])) {
            $query->whereIn('channel', explode(',', $filters['channel']));
        }

        foreach (['category_id', 'assignee_id', 'requester_id', 'team_id'] as $column) {
            if (isset($filters[$column]) && is_numeric($filters[$column])) {
                $query->where($column, (int) $filters[$column]);
            }
        }

        if (isset($filters['tag']) && is_string($filters['tag'])) {
            $names = array_values(array_filter(array_map('trim', explode(',', $filters['tag']))));

            if ($names !== []) {
                $query->whereHas('tags', function (Builder $tags) use ($names): void {
                    $tags->whereIn('name', $names);
                });
            }
        }

        if (($filters['overdue'] ?? null) === 'true' || ($filters['overdue'] ?? null) === '1') {
            $query->whereNotNull('due_at')->where('due_at', '<', now());
        }

        if (isset($filters['search']) && is_string($filters['search']) && trim($filters['search']) !== '') {
            $term = trim($filters['search']);
            if (config('database.default') === 'mysql') {
                $query->whereFullText(['subject', 'description', 'ticket_number'], $term);
            } else {
                $search = '%'.addcslashes($term, '%_\\').'%';
                $query->where(function (Builder $builder) use ($search): void {
                    $builder->where('subject', 'like', $search)
                        ->orWhere('description', 'like', $search)
                        ->orWhere('ticket_number', 'like', $search);
                });
            }
        }

        if (isset($filters['created_from']) && is_string($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }

        if (isset($filters['created_to']) && is_string($filters['created_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }
    }

    /**
     * @param  Builder<Ticket>  $query
     */
    protected function applyIncludes(Builder $query, mixed $include): void
    {
        if (! is_string($include) || trim($include) === '') {
            return;
        }

        $relations = array_map(
            fn (string $relation): string => $relation === 'sla_policy' ? 'slaPolicy' : $relation,
            explode(',', $include),
        );

        $query->with($relations);
    }

    /**
     * @param  Builder<Ticket>  $query
     */
    protected function applySort(Builder $query, mixed $sort): void
    {
        if (! is_string($sort) || trim($sort) === '') {
            $query->orderByDesc('created_at');

            return;
        }

        foreach (explode(',', $sort) as $segment) {
            $direction = str_starts_with($segment, '-') ? 'desc' : 'asc';
            $column = ltrim($segment, '-');
            $query->orderBy($column, $direction);
        }
    }

    /**
     * @param  class-string<\BackedEnum>  $enum
     */
    protected function assertAllowedCsv(mixed $value, string $enum, string $field): void
    {
        if ($value === null) {
            return;
        }

        if (! is_string($value)) {
            throw ValidationException::withMessages([$field => 'Format filter tidak valid.']);
        }

        foreach (explode(',', $value) as $item) {
            if ($enum::tryFrom($item) === null) {
                throw ValidationException::withMessages([$field => 'Nilai filter tidak dikenal: '.$item.'.']);
            }
        }
    }

    /**
     * @param  array<int, string>  $allowed
     */
    protected function assertAllowedList(string $value, array $allowed, string $field): void
    {
        if (trim($value) === '') {
            return;
        }

        foreach (explode(',', $value) as $item) {
            if (! in_array(ltrim($item, '-'), $allowed, true)) {
                throw ValidationException::withMessages([
                    $field => 'Nilai tidak dikenal: '.$item.'. Pilihan: '.implode(', ', $allowed).'.',
                ]);
            }
        }
    }
}
