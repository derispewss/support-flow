<?php

namespace App\Modules\Support\Http\Resources;

use App\Modules\Support\Models\TicketMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TicketMessage */
class TicketMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'body' => $this->body,
            'is_internal' => $this->is_internal,
            'user' => $this->whenLoaded('user', fn () => $this->user !== null ? new UserSummaryResource($this->user) : null),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
