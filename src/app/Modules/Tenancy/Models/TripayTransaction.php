<?php

namespace App\Modules\Tenancy\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripayTransaction extends Model
{
    protected $fillable = [
        'reference',
        'merchant_ref',
        'user_id',
        'organization_id',
        'organization_name',
        'plan',
        'expected_agents',
        'payment_method',
        'payment_name',
        'amount',
        'fee',
        'total_amount',
        'pay_code',
        'qr_url',
        'status',
        'paid_at',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'fee' => 'integer',
            'total_amount' => 'integer',
            'paid_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
