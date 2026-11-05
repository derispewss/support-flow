<?php

namespace App\Modules\Support\Services;

use App\Modules\Identity\Models\User;
use App\Modules\Support\Models\Attachment;
use App\Modules\Support\Models\Ticket;
use App\Modules\Support\Models\TicketMessage;
use App\Modules\Tenancy\Models\Organization;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class AttachmentService
{
    public function store(Organization $organization, ?User $user, Ticket|TicketMessage $attachable, UploadedFile $file): Attachment
    {
        $extension = Str::lower($file->getClientOriginalExtension() ?: (string) $file->extension());
        $name = Str::random(40).($extension !== '' ? '.'.$extension : '');
        $path = $file->storeAs('attachments/'.$organization->getKey(), $name, ['disk' => 'local']);

        if (! is_string($path)) {
            throw new RuntimeException('Lampiran gagal disimpan.');
        }

        return $attachable->attachments()->create([
            'organization_id' => $organization->getKey(),
            'user_id' => $user?->getKey(),
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size' => $file->getSize() ?: 0,
        ]);
    }

    public function delete(Attachment $attachment): void
    {
        Storage::disk($attachment->disk)->delete($attachment->path);

        $attachment->delete();
    }
}
