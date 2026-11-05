<?php

namespace App\Modules\Support\Models;

use App\Modules\Identity\Models\User;
use App\Modules\Tenancy\Models\Organization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'organization_id',
    'actor_id',
    'event',
    'subject_type',
    'subject_id',
    'description',
    'properties',
    'ip_address',
])]
class AuditLog extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function eventLabel(): string
    {
        return match ($this->event) {
            'ticket.created' => 'Tiket dibuat',
            'ticket.replied' => 'Balasan dikirim',
            'ticket.note_added' => 'Catatan internal',
            'ticket.status_changed' => 'Status tiket',
            'ticket.assigned' => 'Penugasan tiket',
            'ticket.classified' => 'Klasifikasi tiket',
            'ticket.tags_updated' => 'Tag tiket',
            'ticket.watcher_added' => 'Watcher ditambah',
            'ticket.watcher_removed' => 'Watcher dihapus',
            'ticket.deleted' => 'Tiket dihapus',
            'category.created' => 'Kategori dibuat',
            'category.updated' => 'Kategori diubah',
            'category.deleted' => 'Kategori dihapus',
            'tag.created' => 'Tag dibuat',
            'tag.updated' => 'Tag diubah',
            'tag.deleted' => 'Tag dihapus',
            'sla.created' => 'SLA dibuat',
            'sla.updated' => 'SLA diubah',
            'sla.deleted' => 'SLA dihapus',
            'team.created' => 'Tim dibuat',
            'team.updated' => 'Tim diubah',
            'team.deleted' => 'Tim dihapus',
            'member.role_changed' => 'Role anggota',
            'member.status_changed' => 'Status anggota',
            'member.removed' => 'Anggota dihapus',
            'invitation.sent' => 'Undangan dikirim',
            'invitation.revoked' => 'Undangan dibatalkan',
            default => $this->event,
        };
    }
}
