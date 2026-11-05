<?php

namespace App\Modules\Support\Http\Controllers\Portal;

use App\Modules\Identity\Models\User;
use App\Modules\Support\Models\Ticket;
use App\Modules\Tenancy\Models\Invitation;
use App\Modules\Tenancy\Models\Organization;
use App\Shared\Enums\TicketStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $organization = tenant();

        abort_unless($user instanceof User, 403);

        if (! $organization instanceof Organization) {
            return view('pages.portal.dashboard', [
                'counts' => [
                    'active' => 0,
                    'pending' => 0,
                    'resolved' => 0,
                    'total' => 0,
                ],
                'tickets' => collect(),
                'invitations' => Invitation::query()
                    ->with('organization')
                    ->pending()
                    ->where('email', $user->email)
                    ->latest()
                    ->get(),
            ]);
        }

        $base = Ticket::query()
            ->forOrganization($organization)
            ->visibleTo($user);

        $counts = [
            'active' => (clone $base)->whereIn('status', [
                TicketStatus::New->value,
                TicketStatus::Open->value,
                TicketStatus::Pending->value,
            ])->count(),
            'pending' => (clone $base)->where('status', TicketStatus::Pending->value)->count(),
            'resolved' => (clone $base)->where('status', TicketStatus::Resolved->value)->count(),
            'total' => (clone $base)->count(),
        ];

        $tickets = (clone $base)
            ->with('category')
            ->orderByDesc('last_activity_at')
            ->limit(5)
            ->get();

        return view('pages.portal.dashboard', [
            'counts' => $counts,
            'tickets' => $tickets,
            'invitations' => collect(),
        ]);
    }
}
