<?php

namespace App\Modules\Identity\Support;

use App\Modules\Identity\Models\User;
use App\Shared\Enums\MembershipStatus;
use App\Shared\Enums\UserRole;
use Illuminate\Http\RedirectResponse;

class AuthenticationRedirector
{
    public function for(User $user): RedirectResponse
    {
        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        $membership = $user->memberships()
            ->where('status', MembershipStatus::Active->value)
            ->orderByRaw("CASE WHEN role = 'customer' THEN 1 ELSE 0 END")
            ->orderBy('id')
            ->first();

        if ($membership === null) {
            return match (UserRole::tryFrom($user->onboarding_role ?? '')) {
                UserRole::Customer, UserRole::Agent => redirect()->route('dashboard'),
                default => redirect()->route('onboarding.create'),
            };
        }

        return $membership->role->isStaff()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('dashboard');
    }
}
