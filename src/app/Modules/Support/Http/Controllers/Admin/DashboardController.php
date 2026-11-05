<?php

namespace App\Modules\Support\Http\Controllers\Admin;

use App\Modules\Support\Models\Ticket;
use App\Modules\Tenancy\Models\Organization;
use App\Shared\Enums\TicketStatus;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController
{
    /**
     * @var array<int, TicketStatus>
     */
    protected array $openStatuses = [
        TicketStatus::New,
        TicketStatus::Open,
        TicketStatus::Pending,
    ];

    public function __invoke(TenantContext $context): View
    {
        $organization = $context->organization();

        abort_unless($organization instanceof Organization, 403);

        Gate::authorize('viewAny', [Ticket::class, $organization]);

        $base = Ticket::query()->forOrganization($organization);

        $open = (clone $base)->whereIn('status', $this->openStatuses)->count();
        $unassigned = (clone $base)->whereIn('status', $this->openStatuses)->whereNull('assignee_id')->count();
        $overdue = (clone $base)
            ->whereIn('status', $this->openStatuses)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->count();
        $resolvedWeek = (clone $base)
            ->where('status', TicketStatus::Resolved)
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '>=', now()->subDays(7))
            ->count();

        $metrics = [
            [
                'label' => 'Tiket terbuka',
                'value' => (string) $open,
                'note' => 'Baru, terbuka, dan menunggu',
                'tone' => 'ink',
            ],
            [
                'label' => 'Belum ditugaskan',
                'value' => (string) $unassigned,
                'note' => 'Perlu triase agent',
                'tone' => $unassigned > 0 ? 'pending' : 'resolved',
            ],
            [
                'label' => 'Lewat SLA',
                'value' => (string) $overdue,
                'note' => 'Batas resolusi terlewati',
                'tone' => $overdue > 0 ? 'open' : 'resolved',
            ],
            [
                'label' => 'Resolved 7 hari',
                'value' => (string) $resolvedWeek,
                'note' => 'Selesai minggu ini',
                'tone' => 'resolved',
            ],
            [
                'label' => 'First response',
                'value' => $this->averageFirstResponse($base),
                'note' => 'Rata-rata 30 hari terakhir',
                'tone' => 'ink',
            ],
            [
                'label' => 'SLA compliance',
                'value' => $this->slaCompliance($base),
                'note' => 'Resolved 30 hari terakhir',
                'tone' => 'ink',
            ],
        ];

        $volume = $this->volume($base);

        $risk = (clone $base)
            ->whereIn('status', $this->openStatuses)
            ->whereNotNull('due_at')
            ->with('requester')
            ->orderBy('due_at')
            ->limit(5)
            ->get();

        $queue = (clone $base)
            ->with(['requester', 'assignee', 'category'])
            ->orderByDesc('last_activity_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return view('pages.admin.dashboard', [
            'organization' => $organization,
            'metrics' => $metrics,
            'volume' => $volume,
            'risk' => $risk,
            'queue' => $queue,
        ]);
    }

    /**
     * @param  Builder<Ticket>  $base
     */
    protected function averageFirstResponse(Builder $base): string
    {
        $tickets = (clone $base)
            ->whereNotNull('first_response_at')
            ->where('created_at', '>=', now()->subDays(30))
            ->get(['created_at', 'first_response_at']);

        if ($tickets->isEmpty()) {
            return '—';
        }

        $minutes = $tickets->avg(function (Ticket $ticket): float {
            $created = $ticket->created_at;
            $responded = $ticket->first_response_at;

            if ($created === null || $responded === null) {
                return 0.0;
            }

            return (float) $created->diffInMinutes($responded);
        });

        if ($minutes === null) {
            return '—';
        }

        return $minutes >= 60
            ? number_format($minutes / 60, 1, ',', '.').' j'
            : (string) (int) round($minutes).' mnt';
    }

    /**
     * @param  Builder<Ticket>  $base
     */
    protected function slaCompliance(Builder $base): string
    {
        $tickets = (clone $base)
            ->whereNotNull('resolved_at')
            ->whereNotNull('due_at')
            ->where('resolved_at', '>=', now()->subDays(30))
            ->get(['resolved_at', 'due_at']);

        if ($tickets->isEmpty()) {
            return '—';
        }

        $onTime = $tickets->filter(function (Ticket $ticket): bool {
            $resolved = $ticket->resolved_at;
            $due = $ticket->due_at;

            return $resolved !== null && $due !== null && $resolved->lessThanOrEqualTo($due);
        })->count();

        return (string) (int) round($onTime / $tickets->count() * 100).'%';
    }

    /**
     * @param  Builder<Ticket>  $base
     * @return array<int, array{day: Carbon, created: int, resolved: int, created_height: int, resolved_height: int}>
     */
    protected function volume(Builder $base): array
    {
        $since = now()->subDays(13)->startOfDay();

        $created = (clone $base)
            ->where('created_at', '>=', $since)
            ->selectRaw('date(created_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $resolved = (clone $base)
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '>=', $since)
            ->selectRaw('date(resolved_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        /** @var Collection<int, array{day: Carbon, created: int, resolved: int}> $rows */
        $rows = collect(range(13, 0))->map(function (int $offset) use ($created, $resolved): array {
            $day = now()->subDays($offset)->startOfDay();
            $key = $day->toDateString();

            return [
                'day' => $day,
                'created' => (int) ($created[$key] ?? 0),
                'resolved' => (int) ($resolved[$key] ?? 0),
            ];
        });

        $peak = max(1, $rows->max(fn (array $row): int => max($row['created'], $row['resolved'])));

        return $rows->map(fn (array $row): array => [
            ...$row,
            'created_height' => (int) round($row['created'] / $peak * 100),
            'resolved_height' => (int) round($row['resolved'] / $peak * 100),
        ])->all();
    }
}
