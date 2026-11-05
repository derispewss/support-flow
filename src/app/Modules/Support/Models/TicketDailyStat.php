<?php

namespace App\Modules\Support\Models;

use App\Modules\Tenancy\Models\Organization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'organization_id',
    'stat_date',
    'created_count',
    'resolved_count',
    'first_response_count',
    'sla_on_time_count',
    'sla_evaluated_count',
    'avg_first_response_minutes',
    'avg_resolution_minutes',
])]
class TicketDailyStat extends Model
{
    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    protected function casts(): array
    {
        return [
            'stat_date' => 'date',
            'created_count' => 'integer',
            'resolved_count' => 'integer',
            'first_response_count' => 'integer',
            'sla_on_time_count' => 'integer',
            'sla_evaluated_count' => 'integer',
            'avg_first_response_minutes' => 'integer',
            'avg_resolution_minutes' => 'integer',
        ];
    }
}
