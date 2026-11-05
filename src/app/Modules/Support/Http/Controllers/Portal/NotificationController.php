<?php

namespace App\Modules\Support\Http\Controllers\Portal;

use App\Modules\Identity\Models\User;
use App\Modules\Tenancy\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $organization = tenant();
        $notifications = $user->notifications()
            ->where(function ($query) use ($organization): void {
                $query->whereNull('data->organization_id');

                if ($organization instanceof Organization) {
                    $query->orWhere('data->organization_id', $organization->getKey());
                }
            });

        return view('pages.portal.notifications', [
            'hasOrganization' => $organization instanceof Organization,
            'notifications' => $request->boolean('cursor')
                ? $notifications->latest('id')->cursorPaginate(20)->withQueryString()
                : $notifications->latest()->paginate(20)->withQueryString(),
        ]);
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->notifications()->whereKey($notification)->update(['read_at' => now()]);

        return back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }
}
