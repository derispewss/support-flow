<?php

namespace App\Modules\Support\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['webhook_id', 'event', 'payload', 'attempts', 'response_status', 'response_body', 'delivered_at', 'next_retry_at'])]
class WebhookDelivery extends Model
{
    /** @return BelongsTo<Webhook, $this> */
    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'delivered_at' => 'datetime',
            'next_retry_at' => 'datetime',
        ];
    }
}
