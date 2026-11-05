<?php

namespace App\Modules\Support\Http\Controllers\Api\V1;

use App\Modules\Identity\Models\User;
use App\Modules\Support\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TicketWatcherController
{
    public function store(Request $request, Ticket $ticket): JsonResponse
    {
        abort_unless($ticket->organization_id === tenant()?->getKey(), 404);

        $user = $request->user();

        abort_unless($user instanceof User, 401);

        Gate::authorize('view', $ticket);

        $organizationId = $ticket->organization_id;

        $data = $request->validate([
            'user_id' => [
                'sometimes',
                'integer',
                Rule::exists('memberships', 'user_id')
                    ->where('organization_id', $organizationId)
                    ->where('status', 'active'),
            ],
        ]);

        $watcherId = (int) ($data['user_id'] ?? $user->getKey());

        if ($watcherId !== $user->getKey()) {
            Gate::authorize('manageWatchers', $ticket);
        }

        $ticket->watchers()->syncWithoutDetaching([$watcherId]);

        return response()->json(['message' => 'Watcher ditambahkan.']);
    }

    public function destroy(Request $request, Ticket $ticket, int $user): JsonResponse
    {
        abort_unless($ticket->organization_id === tenant()?->getKey(), 404);

        $actor = $request->user();

        abort_unless($actor instanceof User, 401);

        Gate::authorize('view', $ticket);

        if ($actor->getKey() !== $user) {
            Gate::authorize('manageWatchers', $ticket);
        }

        $ticket->watchers()->detach($user);

        return response()->json(['message' => 'Watcher dihapus.']);
    }
}
