<?php

namespace App\Modules\Tenancy\Services;

use App\Modules\Identity\Models\User;
use App\Modules\Tenancy\Models\Invitation;
use App\Modules\Tenancy\Models\Membership;
use App\Modules\Tenancy\Models\Organization;
use App\Modules\Tenancy\Notifications\InvitationNotification;
use App\Shared\Enums\MembershipStatus;
use App\Shared\Enums\UserRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvitationService
{
    /**
     * @return array{0: Invitation, 1: string}
     */
    public function invite(Organization $organization, User $inviter, string $email, UserRole $role): array
    {
        if ($role === UserRole::Owner) {
            throw ValidationException::withMessages([
                'role' => 'Undangan tidak dapat diberikan role owner.',
            ]);
        }

        $email = Str::lower(trim($email));

        $alreadyMember = Membership::query()
            ->where('organization_id', $organization->getKey())
            ->whereHas('user', fn ($query) => $query->where('email', $email))
            ->exists();

        if ($alreadyMember) {
            throw ValidationException::withMessages([
                'email' => 'Email tersebut sudah menjadi anggota organisasi ini.',
            ]);
        }

        $organization->invitations()
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->delete();

        $plainToken = Str::random(64);

        $invitation = $organization->invitations()->create([
            'email' => $email,
            'role' => $role,
            'token_hash' => hash('sha256', $plainToken),
            'invited_by' => $inviter->getKey(),
            'expires_at' => now()->addDays(7),
        ]);

        Notification::route('mail', $email)
            ->notify(new InvitationNotification($invitation, $plainToken));

        return [$invitation, $plainToken];
    }

    public function accept(Invitation $invitation, User $user): Membership
    {
        if (! $invitation->isPending()) {
            throw ValidationException::withMessages([
                'invitation' => 'Undangan sudah kedaluwarsa atau sudah diterima.',
            ]);
        }

        if (Str::lower($user->email) !== Str::lower($invitation->email)) {
            throw new AuthorizationException('Undangan ini ditujukan untuk alamat email lain.');
        }

        return DB::transaction(function () use ($invitation, $user): Membership {
            $membership = Membership::updateOrCreate(
                [
                    'organization_id' => $invitation->organization_id,
                    'user_id' => $user->getKey(),
                ],
                [
                    'role' => $invitation->role,
                    'status' => MembershipStatus::Active,
                    'joined_at' => now(),
                ],
            );

            $invitation->forceFill(['accepted_at' => now()])->save();

            return $membership;
        });
    }

    public function revoke(Invitation $invitation): void
    {
        $invitation->delete();
    }
}
