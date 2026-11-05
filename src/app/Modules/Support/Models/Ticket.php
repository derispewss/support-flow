<?php

namespace App\Modules\Support\Models;

use App\Modules\Identity\Models\User;
use App\Modules\Support\Observers\TicketObserver;
use App\Modules\Tenancy\Models\Organization;
use App\Modules\Tenancy\Models\Team;
use App\Shared\Enums\TicketChannel;
use App\Shared\Enums\TicketPriority;
use App\Shared\Enums\TicketStatus;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UseFactory(TicketFactory::class)]
#[ObservedBy([TicketObserver::class])]
#[Fillable([
    'organization_id',
    'ticket_number',
    'subject',
    'description',
    'status',
    'priority',
    'channel',
    'category_id',
    'requester_id',
    'assignee_id',
    'team_id',
    'sla_policy_id',
    'first_response_at',
    'first_response_due_at',
    'due_at',
    'sla_breached_at',
    'resolved_at',
    'closed_at',
    'last_activity_at',
    'satisfaction_rating',
])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'channel' => TicketChannel::class,
            'first_response_at' => 'datetime',
            'first_response_due_at' => 'datetime',
            'due_at' => 'datetime',
            'sla_breached_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'satisfaction_rating' => 'integer',
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
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<SlaPolicy, $this>
     */
    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(SlaPolicy::class);
    }

    /**
     * @return HasMany<TicketMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->orderBy('created_at');
    }

    /**
     * @return MorphMany<Attachment, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'ticket_tag');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function watchers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'ticket_watchers')->withTimestamps();
    }

    /**
     * @param  Builder<Ticket>  $query
     * @return Builder<Ticket>
     */
    public function scopeForOrganization(Builder $query, Organization $organization): Builder
    {
        return $query->where('organization_id', $organization->getKey());
    }

    /**
     * @param  Builder<Ticket>  $query
     * @return Builder<Ticket>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $builder) use ($user): void {
            $builder
                ->where('requester_id', $user->getKey())
                ->orWhereHas('watchers', function (Builder $watchers) use ($user): void {
                    $watchers->whereKey($user->getKey());
                });
        });
    }

    /**
     * @param  Builder<Ticket>  $query
     * @return Builder<Ticket>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [
            TicketStatus::New->value,
            TicketStatus::Open->value,
            TicketStatus::Pending->value,
        ]);
    }

    public function isVisibleTo(User $user): bool
    {
        if ($user->isStaffIn($this->organization)) {
            return true;
        }

        return $this->requester_id === $user->getKey()
            || $this->watchers()->whereKey($user->getKey())->exists();
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [TicketStatus::New, TicketStatus::Open, TicketStatus::Pending], true);
    }
}
