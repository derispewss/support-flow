<?php

namespace App\Console\Commands;

use App\Modules\Support\Models\Ticket;
use App\Modules\Support\Models\TicketDailyStat;
use App\Modules\Tenancy\Models\Organization;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class AggregateTicketStatsCommand extends Command
{
    protected $signature = 'support:daily-stats {date? : Date to aggregate in YYYY-MM-DD format}';

    protected $description = 'Aggregate daily ticket metrics for every organization';

    public function handle(): int
    {
        $date = $this->argument('date')
            ? Carbon::createFromFormat('Y-m-d', (string) $this->argument('date'))->startOfDay()
            : now()->subDay()->startOfDay();

        Organization::query()->each(function (Organization $organization) use ($date): void {
            $tickets = Ticket::query()
                ->where('organization_id', $organization->id)
                ->where(function ($query) use ($date): void {
                    $query->whereBetween('created_at', [$date, $date->copy()->endOfDay()])
                        ->orWhereBetween('resolved_at', [$date, $date->copy()->endOfDay()]);
                })
                ->get([
                    'created_at',
                    'resolved_at',
                    'first_response_at',
                    'due_at',
                ]);

            $created = $tickets->filter(fn (Ticket $ticket): bool => $ticket->created_at?->isSameDay($date) ?? false);
            $resolved = $tickets->filter(fn (Ticket $ticket): bool => $ticket->resolved_at?->isSameDay($date) ?? false);
            $firstResponses = $tickets->filter(fn (Ticket $ticket): bool => $ticket->first_response_at?->isSameDay($date) ?? false);
            $sla = $resolved->filter(fn (Ticket $ticket): bool => $ticket->due_at !== null);
            $onTime = $sla->filter(fn (Ticket $ticket): bool => $ticket->resolved_at?->lessThanOrEqualTo($ticket->due_at) ?? false);

            TicketDailyStat::query()->updateOrCreate(
                ['organization_id' => $organization->id, 'stat_date' => $date->toDateString()],
                [
                    'created_count' => $created->count(),
                    'resolved_count' => $resolved->count(),
                    'first_response_count' => $firstResponses->count(),
                    'sla_on_time_count' => $onTime->count(),
                    'sla_evaluated_count' => $sla->count(),
                    'avg_first_response_minutes' => $this->averageMinutes($firstResponses, 'first_response_at'),
                    'avg_resolution_minutes' => $this->averageMinutes($resolved, 'resolved_at'),
                ],
            );
        });

        $this->info('Aggregated ticket stats for '.$date->toDateString().'.');

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, Ticket>  $tickets
     */
    private function averageMinutes(Collection $tickets, string $endColumn): ?int
    {
        $values = $tickets->map(function (Ticket $ticket) use ($endColumn): int {
            $start = $ticket->created_at;
            $end = $ticket->{$endColumn};

            return $start !== null && $end !== null ? $start->diffInMinutes($end) : 0;
        })->filter(fn (int $minutes): bool => $minutes > 0);

        return $values->isEmpty() ? null : (int) round($values->avg());
    }
}
