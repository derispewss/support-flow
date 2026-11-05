<?php

namespace App\Modules\KnowledgeBase\Models;

use App\Modules\Identity\Models\User;
use App\Modules\Tenancy\Models\Organization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['organization_id', 'kb_category_id', 'author_id', 'title', 'slug', 'body', 'status', 'views', 'published_at'])]
class Article extends Model
{
    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'kb_category_id');
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** @return HasMany<ArticleVote, $this> */
    public function votes(): HasMany
    {
        return $this->hasMany(ArticleVote::class);
    }

    /** @param Builder<Article> $query
     * @return Builder<Article>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->whereNotNull('published_at');
    }

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'views' => 'integer'];
    }
}
