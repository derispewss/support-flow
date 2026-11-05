<?php

namespace App\Modules\Support\Http\Controllers\Admin;

use App\Modules\Support\Models\Ticket;
use App\Modules\Support\Models\TicketDailyStat;
use App\Modules\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController
{
    public function index(Request $request, TenantContext $context): View
    {
        $organization = $this->organization($context);
        $days = min(max($request->integer('days', 14), 7), 30);
        $stats = TicketDailyStat::query()
            ->where('organization_id', $organization->id)
            ->whereBetween('stat_date', [now()->subDays($days - 1)->toDateString(), now()->toDateString()])
            ->orderBy('stat_date')
            ->get();

        $tickets = Ticket::query()->forOrganization($organization);
        $summary = [
            'created' => (clone $tickets)->where('created_at', '>=', now()->subDays($days))->count(),
            'resolved' => (clone $tickets)->where('resolved_at', '>=', now()->subDays($days))->count(),
            'resolution' => $this->averageMinutes((clone $tickets)->whereNotNull('resolved_at')->where('resolved_at', '>=', now()->subDays($days)), 'resolved_at'),
            'first_response' => $this->averageMinutes((clone $tickets)->whereNotNull('first_response_at')->where('first_response_at', '>=', now()->subDays($days)), 'first_response_at'),
        ];

        $workload = (clone $tickets)
            ->where('created_at', '>=', now()->subDays($days))
            ->with('assignee')
            ->selectRaw('assignee_id, count(*) as total')
            ->groupBy('assignee_id')
            ->orderByDesc('total')
            ->get();

        return view('pages.admin.reports', compact('organization', 'stats', 'summary', 'workload', 'days'));
    }

    public function export(Request $request, TenantContext $context): StreamedResponse
    {
        $organization = $this->organization($context);
        $from = Carbon::parse($request->input('from', now()->subDays(30)->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->toDateString()))->endOfDay();
        $tickets = Ticket::query()
            ->forOrganization($organization)
            ->with(['requester', 'assignee', 'category'])
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('id')
            ->cursor();

        return response()->streamDownload(function () use ($tickets): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['ticket_number', 'subject', 'status', 'priority', 'requester', 'assignee', 'category', 'created_at', 'resolved_at', 'sla_breached']);
            foreach ($tickets as $ticket) {
                fputcsv($handle, [
                    $ticket->ticket_number,
                    $ticket->subject,
                    $ticket->status->value,
                    $ticket->priority->value,
                    $ticket->requester?->email,
                    $ticket->assignee?->email,
                    $ticket->category?->name,
                    $ticket->created_at?->toIso8601String(),
                    $ticket->resolved_at?->toIso8601String(),
                    $ticket->sla_breached_at !== null ? 'yes' : 'no',
                ]);
            }
            fclose($handle);
        }, 'supportflow-tickets.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function organization(TenantContext $context): Organization
    {
        $organization = $context->organization();
        abort_unless($organization instanceof Organization, 403);
        Gate::authorize('viewAny', [Ticket::class, $organization]);

        return $organization;
    }

    /** @param Builder<Ticket> $query */
    private function averageMinutes(Builder $query, string $endColumn): ?int
    {
        $tickets = $query->get(['created_at', $endColumn]);
        $values = $tickets->map(fn (Ticket $ticket): int => $ticket->created_at !== null && $ticket->{$endColumn} !== null
            ? $ticket->created_at->diffInMinutes($ticket->{$endColumn})
            : 0)->filter(fn (int $minutes): bool => $minutes > 0);

        return $values->isEmpty() ? null : (int) round($values->avg());
    }
}
