<?php

namespace App\Modules\Support\Services;

use App\Modules\Identity\Models\User;
use App\Modules\Support\Models\Ticket;
use App\Modules\Support\Models\TicketMessage;
use App\Modules\Tenancy\Models\Organization;
use App\Shared\Enums\MessageType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class TicketMessageService
{
    public function __construct(
        protected AttachmentService $attachments,
        protected TicketStatusService $statuses,
        protected AuditLogger $audit,
    ) {}

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function post(
        Ticket $ticket,
        User $author,
        string $body,
        MessageType $type = MessageType::Reply,
        array $files = [],
    ): TicketMessage {
        $organization = $ticket->organization;

        if (! $organization instanceof Organization) {
            throw new \RuntimeException('Tiket tidak memiliki organisasi.');
        }

        $staff = $author->isStaffIn($organization);

        return DB::transaction(function () use ($ticket, $author, $body, $type, $files, $organization, $staff): TicketMessage {
            $message = TicketMessage::query()->create([
                'ticket_id' => $ticket->getKey(),
                'user_id' => $author->getKey(),
                'type' => $type,
                'body' => $body,
                'is_internal' => $type->isInternal(),
            ]);

            foreach ($files as $file) {
                $this->attachments->store($organization, $author, $message, $file);
            }

            $ticket->last_activity_at = now();

            if ($type === MessageType::Reply && $staff && $ticket->first_response_at === null) {
                $ticket->first_response_at = now();
            }

            $ticket->save();

            $ticket->watchers()->syncWithoutDetaching([$author->getKey()]);

            if ($type === MessageType::Reply && $staff) {
                $this->statuses->openIfNew($ticket, $author);
            }

            $this->audit->log(
                $type === MessageType::Note ? 'ticket.note_added' : 'ticket.replied',
                $type === MessageType::Note
                    ? "Catatan internal ditambahkan pada {$ticket->ticket_number}."
                    : "Balasan dikirim pada {$ticket->ticket_number}.",
                $ticket,
                ['message_id' => $message->getKey(), 'internal' => $type->isInternal()],
                $organization,
                $author,
            );

            return $message->load(['user', 'attachments']);
        });
    }
}
