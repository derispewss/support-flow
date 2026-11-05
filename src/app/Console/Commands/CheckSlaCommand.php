<?php

namespace App\Console\Commands;

use App\Modules\Identity\Models\User;
use App\Modules\Support\Models\Ticket;
use App\Modules\Support\Notifications\SlaBreachedNotification;
use App\Shared\Enums\TicketStatus;
use Illuminate\Console\Command;

class CheckSlaCommand extends Command
{
    protected $signature = 'support:sla-check';

    protected $description = 'Mark overdue tickets and notify responsible users';

    public function handle(): int
    {
        $count = 0;

        Ticket::query()
            ->with(['requester', 'assignee'])
            ->whereNull('sla_breached_at')
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->whereNotIn('status', [TicketStatus::Resolved->value, TicketStatus::Closed->value])
            ->chunkById(100, function ($tickets) use (&$count): void {
                foreach ($tickets as $ticket) {
                    $ticket->forceFill(['sla_breached_at' => now()])->save();
                    $recipients = collect([$ticket->requester, $ticket->assignee])
                        ->filter(fn ($user): bool => $user instanceof User)
                        ->unique('id');
                    $recipients->each(fn (User $user) => $user->notify(new SlaBreachedNotification($ticket)));
                    $count++;
                }
            });

        $this->info("Marked {$count} SLA breach(es).");

        return self::SUCCESS;
    }
}
