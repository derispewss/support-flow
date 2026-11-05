<?php

namespace App\Modules\Tenancy\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Fillable(['team_id', 'user_id'])]
class TeamMember extends Pivot
{
    protected $table = 'team_members';

    public $incrementing = true;
}
