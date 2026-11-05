<?php

namespace App\Modules\Support\Models;

use App\Modules\Identity\Models\User;
use App\Modules\Tenancy\Models\Organization;
use Database\Factories\CannedResponseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UseFactory(CannedResponseFactory::class)]
#[Fillable(['organization_id', 'title', 'body', 'created_by'])]
class CannedResponse extends Model
{
    /** @use HasFactory<CannedResponseFactory> */
    use HasFactory;

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
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
