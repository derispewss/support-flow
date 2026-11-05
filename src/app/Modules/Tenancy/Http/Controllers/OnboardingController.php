<?php

namespace App\Modules\Tenancy\Http\Controllers;

use App\Modules\Identity\Support\AuthenticationRedirector;
use App\Modules\Tenancy\Models\Organization;
use App\Modules\Tenancy\Models\Invitation;
use App\Modules\Tenancy\Services\OrganizationProvisioner;
use App\Shared\Enums\UserRole;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OnboardingController
{
    public function create(Request $request, AuthenticationRedirector $redirector): View|RedirectResponse
    {
        $user = $request->user();

        if ($user !== null && ! $user->hasVerifiedEmail()) {
            return $redirector->for($user);
        }

        if ($user !== null && $user->memberships()->exists()) {
            return $redirector->for($user);
        }

        if ($user !== null && in_array($user->onboarding_role, [
            UserRole::Customer->value,
            UserRole::Agent->value,
        ], true)) {
            return redirect()->route('dashboard');
        }

        return view('pages.onboarding.create', [
            'suggestedName' => 'Ruang Kerja '.$user->name,
            'selectedRole' => $user?->onboarding_role,
        ]);
    }

    public function selectRole(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if ($user->onboarding_role !== null) {
            return $user->onboarding_role === UserRole::Owner->value
                ? redirect()->route('onboarding.create')
                : redirect()->route('dashboard');
        }

        $role = $request->validate([
            'role' => ['required', 'in:customer,owner,agent'],
        ])['role'];

        $user->forceFill(['onboarding_role' => $role])->save();

        return $role === UserRole::Owner->value
            ? redirect()->route('onboarding.create')
            : redirect()->route('dashboard');
    }

    public function customer(Request $request, AuthenticationRedirector $redirector): View|RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if (! $user->hasVerifiedEmail()) {
            return $redirector->for($user);
        }

        $membership = $user->memberships()->active()->first();

        if ($membership !== null) {
            return $redirector->for($user);
        }

        $role = $user->onboarding_role ?? UserRole::Customer->value;
        $roleLabel = $role === UserRole::Agent->value ? 'agent' : 'customer';
        $invitations = Invitation::query()
            ->with('organization')
            ->pending()
            ->where('email', $user->email)
            ->latest()
            ->get();

        return redirect()->route('dashboard');
    }

    public function store(
        Request $request,
        OrganizationProvisioner $provisioner,
        TenantContext $context,
    ): RedirectResponse {
        if ($request->user()?->onboarding_role !== UserRole::Owner->value) {
            return redirect()->route('onboarding.create');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'plan' => ['nullable', 'in:free,pro,enterprise'],
            'agents' => ['nullable', 'integer', 'min:0'],
        ]);
        $data['plan'] ??= 'free';

        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        Gate::authorize('create', Organization::class);

        $organization = $provisioner->provision($user, $data['name']);
        $organization->plan = $data['plan'];
        $organization->save();

        // Optional: auto-generate placeholder agents if entered
        if (!empty($data['agents']) && (int)$data['agents'] > 0) {
            $agentsCount = min((int)$data['agents'], 50);
            for ($i = 1; $i <= $agentsCount; $i++) {
                $agentEmail = 'agent'.$i.'_'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(5)).'@supportflow.test';
                $agent = \App\Modules\Identity\Models\User::create([
                    'name' => 'Agent '.$i.' ('.$organization->name.')',
                    'email' => $agentEmail,
                    'password' => 'Password123',
                    'email_verified_at' => now(),
                    'timezone' => 'Asia/Jakarta',
                    'locale' => 'id',
                    'is_active' => true,
                ]);

                \App\Modules\Tenancy\Models\Membership::create([
                    'organization_id' => $organization->getKey(),
                    'user_id' => $agent->getKey(),
                    'role' => \App\Shared\Enums\UserRole::Agent,
                    'status' => \App\Shared\Enums\MembershipStatus::Active,
                    'joined_at' => now(),
                ]);
            }
        }

        $membership = $user->membershipFor($organization);

        if ($membership !== null) {
            $context->set($organization, $membership);
        }

        $request->session()->put('current_organization_id', $organization->getKey());

        if ($data['plan'] !== 'free') {
            return redirect()
                ->route('owner.payment', $organization)
                ->with('status', 'Silakan selesaikan pembayaran paket '.strtoupper($data['plan']).' melalui gateway sandbox Tripay.');
        }

        return redirect()
            ->route('admin.dashboard')
            ->with('status', 'Organisasi '.$organization->name.' berhasil dibuat.');
    }
}
