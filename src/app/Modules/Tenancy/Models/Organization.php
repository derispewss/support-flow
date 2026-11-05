<?php

namespace App\Modules\Tenancy\Models;

use App\Modules\Identity\Models\User;
use App\Modules\KnowledgeBase\Models\Article;
use App\Modules\KnowledgeBase\Models\Category as KnowledgeBaseCategory;
use App\Modules\Support\Models\Attachment;
use App\Modules\Support\Models\CannedResponse;
use App\Modules\Support\Models\Category;
use App\Modules\Support\Models\SlaPolicy;
use App\Modules\Support\Models\Tag;
use App\Modules\Support\Models\Ticket;
use App\Modules\Support\Models\TicketDailyStat;
use App\Modules\Support\Models\Webhook;
use App\Shared\Enums\OrganizationPlan;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UseFactory(OrganizationFactory::class)]
#[Fillable(['name', 'slug', 'plan', 'trial_ends_at', 'settings'])]
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'plan' => OrganizationPlan::class,
            'trial_ends_at' => 'datetime',
            'settings' => 'array',
        ];
    }

    /**
     * @return HasMany<Membership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'memberships')
            ->withPivot(['role', 'status', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<Invitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    /**
     * @return HasMany<Team, $this>
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * @return HasMany<Tag, $this>
     */
    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    /**
     * @return HasMany<SlaPolicy, $this>
     */
    public function slaPolicies(): HasMany
    {
        return $this->hasMany(SlaPolicy::class);
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * @return HasMany<Attachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * @return HasMany<CannedResponse, $this>
     */
    public function cannedResponses(): HasMany
    {
        return $this->hasMany(CannedResponse::class);
    }

    /** @return HasMany<KnowledgeBaseCategory, $this> */
    public function kbCategories(): HasMany
    {
        return $this->hasMany(KnowledgeBaseCategory::class);
    }

    /** @return HasMany<Article, $this> */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    /** @return HasMany<Webhook, $this> */
    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class);
    }

    /** @return HasMany<TicketDailyStat, $this> */
    public function ticketDailyStats(): HasMany
    {
        return $this->hasMany(TicketDailyStat::class);
    }
}
