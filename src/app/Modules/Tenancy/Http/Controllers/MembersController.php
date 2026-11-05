<?php

namespace App\Modules\Tenancy\Http\Controllers;

use App\Modules\Identity\Models\User;
use App\Modules\Support\Services\AuditLogger;
use App\Modules\Tenancy\Models\Invitation;
use App\Modules\Tenancy\Models\Membership;
use App\Modules\Tenancy\Models\Organization;
use App\Modules\Tenancy\Services\InvitationService;
use App\Shared\Enums\MembershipStatus;
use App\Shared\Enums\UserRole;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MembersController
{
    public function index(TenantContext $context): View
    {
        $organization = $this->organization($context);

        Gate::authorize('manageMembers', $organization);

        $members = $organization->memberships()
            ->with('user')
            ->orderByRaw("CASE role WHEN 'owner' THEN 0 WHEN 'admin' THEN 1 WHEN 'agent' THEN 2 ELSE 3 END")
            ->orderBy('id')
            ->get();

        $invitations = $organization->invitations()
            ->pending()
            ->latest()
            ->get();

        return view('pages.admin.members', [
            'organization' => $organization,
            'members' => $members,
            'invitations' => $invitations,
            'inviteRoles' => [UserRole::Admin, UserRole::Agent, UserRole::Customer],
            'manageRoles' => [UserRole::Admin, UserRole::Agent, UserRole::Customer],
        ]);
    }

    public function storeInvitation(Request $request, TenantContext $context, InvitationService $invitations, AuditLogger $audit): RedirectResponse
    {
        $organization = $this->organization($context);

        Gate::authorize('create', [Invitation::class, $organization]);

        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:191'],
            'role' => ['required', Rule::in([UserRole::Admin->value, UserRole::Agent->value, UserRole::Customer->value])],
        ]);

        $inviter = $request->user();

        if ($inviter === null) {
            return redirect()->route('login');
        }

        [$invitation] = $invitations->invite($organization, $inviter, $data['email'], UserRole::from($data['role']));

        $audit->log(
            'invitation.sent',
            'Undangan dikirim ke '.$data['email'].'.',
            $invitation,
            ['email' => $data['email'], 'role' => $data['role']],
            $organization,
            $inviter,
        );

        return back()->with('status', 'Undangan sudah dikirim ke '.$data['email'].'.');
    }

    public function destroyInvitation(Request $request, Invitation $invitation, TenantContext $context, InvitationService $invitations, AuditLogger $audit): RedirectResponse
    {
        $organization = $this->organization($context);

        abort_unless($invitation->organization_id === $organization->getKey(), 404);

        Gate::authorize('delete', $invitation);

        $invitations->revoke($invitation);

        $audit->log(
            'invitation.revoked',
            'Undangan untuk '.$invitation->email.' dibatalkan.',
            $invitation,
            ['email' => $invitation->email],
            $organization,
            $request->user(),
        );

        return back()->with('status', 'Undangan dibatalkan.');
    }

    public function updateRole(Request $request, Membership $membership, TenantContext $context, AuditLogger $audit): RedirectResponse
    {
        $organization = $this->organization($context);

        abort_unless($membership->organization_id === $organization->getKey(), 404);

        Gate::authorize('update', $membership);

        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        $data = $request->validate([
            'role' => ['required', Rule::enum(UserRole::class)],
        ]);

        $role = UserRole::from($data['role']);

        if ($membership->user_id === $actor->getKey()) {
            throw ValidationException::withMessages([
                'role' => 'Anda tidak dapat mengubah role sendiri.',
            ]);
        }

        if ($role === UserRole::Owner && $actor->roleIn($organization) !== UserRole::Owner) {
            throw ValidationException::withMessages([
                'role' => 'Hanya owner yang dapat memberikan role owner.',
            ]);
        }

        if ($role === UserRole::Superadmin && $actor->roleIn($organization) !== UserRole::Superadmin) {
            throw ValidationException::withMessages([
                'role' => 'Hanya superadmin yang dapat memberikan role superadmin.',
            ]);
        }

        if ($membership->role === UserRole::Owner && $role !== UserRole::Owner) {
            $this->ensureAnotherOwner($organization, $membership);
        }

        $previous = $membership->role;

        $membership->forceFill(['role' => $role])->save();

        $audit->log(
            'member.role_changed',
            'Role '.$membership->user->name." diubah dari {$previous->label()} ke {$role->label()}.",
            $membership,
            ['from' => $previous->value, 'to' => $role->value],
            $organization,
            $actor,
        );

        return back()->with('status', 'Role anggota diperbarui.');
    }

    public function updateStatus(Request $request, Membership $membership, TenantContext $context, AuditLogger $audit): RedirectResponse
    {
        $organization = $this->organization($context);

        abort_unless($membership->organization_id === $organization->getKey(), 404);

        Gate::authorize('update', $membership);

        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        $data = $request->validate([
            'status' => ['required', Rule::in([MembershipStatus::Active->value, MembershipStatus::Suspended->value])],
        ]);

        $status = MembershipStatus::from($data['status']);

        if ($membership->user_id === $actor->getKey() && $status === MembershipStatus::Suspended) {
            throw ValidationException::withMessages([
                'status' => 'Anda tidak dapat menangguhkan akun sendiri.',
            ]);
        }

        if ($membership->role === UserRole::Owner && $status === MembershipStatus::Suspended) {
            $this->ensureAnotherOwner($organization, $membership);
        }

        $previous = $membership->status;

        $membership->forceFill(['status' => $status])->save();

        $audit->log(
            'member.status_changed',
            'Status '.$membership->user->name." diubah dari {$previous->label()} ke {$status->label()}.",
            $membership,
            ['from' => $previous->value, 'to' => $status->value],
            $organization,
            $actor,
        );

        return back()->with('status', 'Status anggota diperbarui.');
    }

    public function destroy(Request $request, Membership $membership, TenantContext $context, AuditLogger $audit): RedirectResponse
    {
        $organization = $this->organization($context);

        abort_unless($membership->organization_id === $organization->getKey(), 404);

        Gate::authorize('delete', $membership);

        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        if ($membership->user_id === $actor->getKey()) {
            throw ValidationException::withMessages([
                'membership' => 'Anda tidak dapat menghapus keanggotaan sendiri.',
            ]);
        }

        $name = $membership->user->name;

        $membership->delete();

        $audit->log(
            'member.removed',
            "{$name} dikeluarkan dari organisasi.",
            null,
            ['name' => $name, 'membership_id' => $membership->getKey()],
            $organization,
            $actor,
        );

        return back()->with('status', 'Anggota dikeluarkan.');
    }

    protected function organization(TenantContext $context): Organization
    {
        $organization = $context->organization();

        abort_unless($organization instanceof Organization, 403);

        return $organization;
    }

    protected function ensureAnotherOwner(Organization $organization, Membership $membership): void
    {
        $otherOwners = $organization->memberships()
            ->where('role', UserRole::Owner->value)
            ->where('status', MembershipStatus::Active->value)
            ->where('id', '!=', $membership->getKey())
            ->exists();

        if (! $otherOwners) {
            throw ValidationException::withMessages([
                'membership' => 'Organisasi harus memiliki minimal satu owner aktif.',
            ]);
        }
    }
}
