<?php

namespace App\Modules\Support\Http\Controllers\Admin;

use App\Modules\Support\Models\AuditLog;
use App\Modules\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AuditLogController
{
    public function index(Request $request, TenantContext $context): View
    {
        $organization = $context->organization();

        abort_unless($organization instanceof Organization, 403);

        Gate::authorize('update', $organization);

        $event = $request->string('event')->toString();
        $actor = $request->integer('actor');
        $search = $request->string('q')->toString();

        $logs = AuditLog::query()
            ->where('organization_id', $organization->getKey())
            ->with('actor')
            ->when($event !== '', fn ($query) => $query->where('event', $event))
            ->when($actor > 0, fn ($query) => $query->where('actor_id', $actor))
            ->when($search !== '', function ($query) use ($search): void {
                $needle = '%'.addcslashes($search, '%_\\').'%';
                $query->where('description', 'like', $needle);
            })
            ->latest()
            ->when($request->boolean('cursor'), fn ($query) => $query->cursorPaginate(25), fn ($query) => $query->paginate(25))
            ->withQueryString();

        $events = AuditLog::query()
            ->where('organization_id', $organization->getKey())
            ->distinct()
            ->orderBy('event')
            ->pluck('event');

        return view('pages.admin.audit', [
            'organization' => $organization,
            'logs' => $logs,
            'events' => $events,
            'actors' => $organization->memberships()->with('user')->get(),
            'event' => $event,
            'actor' => $actor,
            'search' => $search,
        ]);
    }
}
