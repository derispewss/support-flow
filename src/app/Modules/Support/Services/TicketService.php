<?php

namespace App\Modules\Support\Services;

use App\Modules\Identity\Models\User;
use App\Modules\Support\Models\SlaPolicy;
use App\Modules\Support\Models\Ticket;
use App\Modules\Support\Models\TicketMessage;
use App\Modules\Support\Notifications\TicketCreatedNotification;
use App\Modules\Tenancy\Models\Organization;
use App\Shared\Enums\MessageType;
use App\Shared\Enums\TicketChannel;
use App\Shared\Enums\TicketPriority;
use App\Shared\Enums\TicketStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class TicketService
{
    public function __construct(
        protected AttachmentService $attachments,
        protected AuditLogger $audit,
        protected WebhookService $webhooks,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $files
     */
    public function create(User $requester, Organization $organization, array $data, array $files = []): Ticket
    {
        return DB::transaction(function () use ($requester, $organization, $data, $files): Ticket {
            $priority = $data['priority'] ?? TicketPriority::Normal;
            $priority = $priority instanceof TicketPriority ? $priority : TicketPriority::from((string) $priority);

            $channel = $data['channel'] ?? TicketChannel::Portal;
            $channel = $channel instanceof TicketChannel ? $channel : TicketChannel::from((string) $channel);

            $sla = SlaPolicy::query()
                ->where('organization_id', $organization->getKey())
                ->where('is_default', true)
                ->first();

            $ticket = Ticket::query()->create([
                'organization_id' => $organization->getKey(),
                'subject' => (string) $data['subject'],
                'description' => (string) $data['description'],
                'status' => TicketStatus::New,
                'priority' => $priority,
                'channel' => $channel,
                'category_id' => $data['category_id'] ?? null,
                'requester_id' => $requester->getKey(),
                'team_id' => $data['team_id'] ?? null,
                'sla_policy_id' => $sla?->getKey(),
            ]);

            if ($sla instanceof SlaPolicy) {
                $ticket->forceFill([
                    'first_response_due_at' => now()->addMinutes($sla->first_response_minutes),
                    'due_at' => now()->addMinutes($sla->resolution_minutes),
                ])->save();
            }

            TicketMessage::query()->create([
                'ticket_id' => $ticket->getKey(),
                'user_id' => $requester->getKey(),
                'type' => MessageType::Reply,
                'body' => (string) $data['description'],
                'is_internal' => false,
            ]);

            foreach ($files as $file) {
                $this->attachments->store($organization, $requester, $ticket, $file);
            }

            $ticket->watchers()->syncWithoutDetaching([$requester->getKey()]);

            $this->audit->log(
                'ticket.created',
                "Tiket {$ticket->ticket_number} dibuat melalui {$channel->label()}.",
                $ticket,
                ['ticket_number' => $ticket->ticket_number, 'channel' => $channel->value],
                $organization,
                $requester,
            );

            $organization->users()
                ->wherePivotIn('role', ['owner', 'admin', 'agent'])
                ->where('users.id', '!=', $requester->getKey())
                ->get()
                ->each(fn (User $user) => $user->notify(new TicketCreatedNotification($ticket)));

            $this->webhooks->dispatch($ticket, 'ticket.created');

            return $ticket->load(['category', 'requester', 'assignee', 'tags', 'slaPolicy']);
        });
    }
}
