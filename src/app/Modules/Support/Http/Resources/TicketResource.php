<?php

namespace App\Modules\Support\Http\Resources;

use App\Modules\Support\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Ticket */
class TicketResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'ticket_number' => $this->ticket_number,
            'subject' => $this->subject,
            'description' => $this->description,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'priority' => $this->priority->value,
            'priority_label' => $this->priority->label(),
            'channel' => $this->channel->value,
            'category' => $this->whenLoaded('category', fn () => $this->category !== null ? new CategoryResource($this->category) : null),
            'requester' => $this->whenLoaded('requester', fn () => $this->requester !== null ? new UserSummaryResource($this->requester) : null),
            'assignee' => $this->whenLoaded('assignee', fn () => $this->assignee !== null ? new UserSummaryResource($this->assignee) : null),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'first_response_at' => $this->first_response_at?->toIso8601String(),
            'first_response_due_at' => $this->first_response_due_at?->toIso8601String(),
            'due_at' => $this->due_at?->toIso8601String(),
            'sla_breached_at' => $this->sla_breached_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'last_activity_at' => $this->last_activity_at?->toIso8601String(),
            'satisfaction_rating' => $this->satisfaction_rating,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
