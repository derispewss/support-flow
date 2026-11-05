<?php

namespace App\Modules\Tenancy\Http\Controllers;

use App\Modules\Tenancy\Models\Invitation;
use App\Modules\Tenancy\Services\InvitationService;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InvitationController
{
    public function acceptFromCustomer(
        Request $request,
        Invitation $invitation,
        InvitationService $invitations,
        TenantContext $context,
    ): RedirectResponse {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if (Str::lower($user->email) !== Str::lower($invitation->email)) {
            abort(403, 'Undangan ini ditujukan untuk alamat email lain.');
        }

        if (! $invitation->isPending()) {
            return redirect()
                ->route('onboarding.customer')
                ->with('error', 'Undangan sudah kedaluwarsa atau sudah diterima.');
        }

        $membership = $invitations->accept($invitation, $user);
        $request->session()->put('current_organization_id', $invitation->organization_id);
        $request->session()->forget('onboarding_role');
        $context->set($invitation->organization, $membership);

        return redirect()
            ->route($membership->role->isStaff() ? 'admin.dashboard' : 'dashboard')
            ->with('status', 'Anda bergabung dengan '.$invitation->organization->name.'.');
    }

    public function show(Request $request, string $token): View
    {
        $invitation = $this->findByToken($token);

        $status = 'pending';

        if ($invitation->isAccepted()) {
            $status = 'accepted';
        } elseif ($invitation->isExpired()) {
            $status = 'expired';
        }

        $user = $request->user();

        return view('pages.invitations.show', [
            'invitation' => $invitation,
            'status' => $status,
            'token' => $token,
            'emailMatches' => $user !== null && Str::lower($user->email) === Str::lower($invitation->email),
        ]);
    }

    public function accept(
        Request $request,
        string $token,
        InvitationService $invitations,
        TenantContext $context,
    ): RedirectResponse {
        $invitation = $this->findByToken($token);

        if (! $invitation->isPending()) {
            return redirect()
                ->route('invitations.show', ['token' => $token])
                ->with('error', 'Undangan sudah kedaluwarsa atau sudah diterima.');
        }

        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        $membership = $invitations->accept($invitation, $user);

        $request->session()->put('current_organization_id', $invitation->organization_id);
        $context->set($invitation->organization, $membership);

        return redirect()
            ->route($membership->role->isStaff() ? 'admin.dashboard' : 'dashboard')
            ->with('status', 'Anda bergabung dengan '.$invitation->organization->name.'.');
    }

    protected function findByToken(string $token): Invitation
    {
        return Invitation::query()
            ->with(['organization', 'inviter'])
            ->where('token_hash', hash('sha256', $token))
            ->firstOrFail();
    }
}
