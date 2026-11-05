<?php

namespace App\Modules\Tenancy\Http\Controllers;

use App\Modules\Tenancy\Models\Organization;
use App\Shared\Enums\MembershipStatus;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrganizationSwitchController
{
    public function __invoke(Request $request, Organization $organization, TenantContext $context): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        $membership = $user->memberships()
            ->where('organization_id', $organization->getKey())
            ->where('status', MembershipStatus::Active->value)
            ->first();

        abort_if($membership === null, 403, 'Anda bukan anggota aktif organisasi ini.');

        $request->session()->put('current_organization_id', $organization->getKey());
        $context->set($organization, $membership);

        return redirect()
            ->route($membership->role->isStaff() ? 'admin.dashboard' : 'dashboard')
            ->with('status', 'Beralih ke '.$organization->name.'.');
    }
}
