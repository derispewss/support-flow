<?php

namespace App\Modules\Support\Services;

use App\Modules\Support\Models\Ticket;
use App\Modules\Tenancy\Models\Organization;
use Illuminate\Support\Facades\DB;

class TicketNumberGenerator
{
    public function generate(int $organizationId): string
    {
        if (DB::transactionLevel() === 0) {
            return DB::transaction(fn (): string => $this->generate($organizationId));
        }

        $prefix = 'TKT-'.now()->format('Ymd').'-';

        // Serialize number generation per organization inside the current transaction.
        Organization::query()->whereKey($organizationId)->lockForUpdate()->first();

        $last = Ticket::withTrashed()
            ->where('organization_id', $organizationId)
            ->where('ticket_number', 'like', $prefix.'%')
            ->orderByDesc('ticket_number')
            ->value('ticket_number');

        $sequence = 1;

        if (is_string($last)) {
            $sequence = (int) substr($last, strlen($prefix)) + 1;
        }

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
