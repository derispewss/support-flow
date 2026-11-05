<?php

namespace App\Modules\Support\Http\Controllers\Api\V1;

use App\Modules\Identity\Models\User;
use App\Modules\Support\Http\Resources\AttachmentResource;
use App\Modules\Support\Models\Attachment;
use App\Modules\Support\Models\Ticket;
use App\Modules\Support\Models\TicketMessage;
use App\Modules\Support\Services\AttachmentService;
use App\Modules\Tenancy\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketAttachmentController
{
    public function index(int $ticket): AnonymousResourceCollection
    {
        $model = $this->findTicket($ticket);

        Gate::authorize('view', $model);

        $messageIds = $model->messages()->pluck('id');

        $attachments = Attachment::query()
            ->where('organization_id', $model->organization_id)
            ->where(function ($query) use ($model, $messageIds): void {
                $query
                    ->where(function ($inner) use ($model): void {
                        $inner->where('attachable_type', Ticket::class)
                            ->where('attachable_id', $model->getKey());
                    })
                    ->orWhere(function ($inner) use ($messageIds): void {
                        $inner->where('attachable_type', TicketMessage::class)
                            ->whereIn('attachable_id', $messageIds);
                    });
            })
            ->orderBy('created_at')
            ->get();

        return AttachmentResource::collection($attachments);
    }

    public function store(Request $request, int $ticket, AttachmentService $attachments): JsonResponse
    {
        $model = $this->findTicket($ticket);

        $user = $request->user();

        abort_unless($user instanceof User, 401);

        Gate::authorize('addAttachment', $model);

        $request->validate([
            'attachments' => ['required', 'array', 'min:1', 'max:5'],
            'attachments.*' => [
                'file',
                'max:10240',
                'mimes:png,jpg,jpeg,gif,webp,pdf,txt,csv,zip,doc,docx,xls,xlsx',
            ],
        ]);

        $organization = $model->organization;

        abort_unless($organization instanceof Organization, 403);

        $files = $request->file('attachments');
        $files = is_array($files) ? array_values(array_filter($files)) : [];

        $created = collect($files)
            ->map(fn ($file) => $attachments->store($organization, $user, $model, $file))
            ->values();

        return AttachmentResource::collection($created)->response()->setStatusCode(201);
    }

    public function download(int $ticket, int $attachment): StreamedResponse
    {
        $model = $this->findTicket($ticket);
        $file = $this->findAttachment($model, $attachment);

        Gate::authorize('view', $file);

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    public function destroy(int $ticket, int $attachment, AttachmentService $attachments): JsonResponse
    {
        $model = $this->findTicket($ticket);
        $file = $this->findAttachment($model, $attachment);

        Gate::authorize('delete', $file);

        $attachments->delete($file);

        return response()->json(['message' => 'Lampiran dihapus.']);
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

    protected function findAttachment(Ticket $ticket, int $id): Attachment
    {
        $attachment = Attachment::query()
            ->where('organization_id', $ticket->organization_id)
            ->whereKey($id)
            ->first();

        if ($attachment === null || ! $this->belongsToTicket($attachment, $ticket)) {
            abort(404, 'Resource not found.');
        }

        return $attachment;
    }

    protected function belongsToTicket(Attachment $attachment, Ticket $ticket): bool
    {
        $attachable = $attachment->attachable;

        if ($attachable instanceof Ticket) {
            return $attachable->getKey() === $ticket->getKey();
        }

        if ($attachable instanceof TicketMessage) {
            return $attachable->ticket_id === $ticket->getKey();
        }

        return false;
    }
}
