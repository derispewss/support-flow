<?php

namespace Tests\Feature\Tenancy;

use App\Modules\Identity\Models\User;
use App\Modules\Tenancy\Models\Invitation;
use App\Modules\Tenancy\Notifications\InvitationNotification;
use App\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_invite_member_and_notification_is_sent(): void
    {
        Notification::fake();

        [$owner, $organization] = $this->createTenantUser(UserRole::Owner);

        $this->actingAs($owner)
            ->post(route('admin.members.invitations.store'), [
                'email' => 'invitee@example.com',
                'role' => UserRole::Agent->value,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $invitation = Invitation::query()->firstOrFail();

        $this->assertSame($organization->getKey(), $invitation->organization_id);
        $this->assertSame(UserRole::Agent, $invitation->role);

        Notification::assertSentOnDemand(
            InvitationNotification::class,
            fn (InvitationNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === 'invitee@example.com',
        );
    }

    public function test_cannot_invite_existing_member(): void
    {
        Notification::fake();

        [$owner, $organization] = $this->createTenantUser(UserRole::Owner);
        $existing = User::factory()->create(['email' => 'existing@example.com']);

        $organization->memberships()->create([
            'user_id' => $existing->getKey(),
            'role' => UserRole::Agent,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->actingAs($owner)
            ->post(route('admin.members.invitations.store'), [
                'email' => 'existing@example.com',
                'role' => UserRole::Agent->value,
            ])
            ->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }

    public function test_invitation_acceptance_creates_membership(): void
    {
        [$owner, $organization] = $this->createTenantUser(UserRole::Owner);
        $invitee = User::factory()->create(['email' => 'invitee@example.com']);
        $token = Str::random(64);

        $invitation = Invitation::factory()->create([
            'organization_id' => $organization->getKey(),
            'email' => 'invitee@example.com',
            'role' => UserRole::Agent,
            'token_hash' => hash('sha256', $token),
            'invited_by' => $owner->getKey(),
        ]);

        $this->actingAs($invitee)
            ->post(route('invitations.accept', ['token' => $token]))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('memberships', [
            'organization_id' => $organization->getKey(),
            'user_id' => $invitee->getKey(),
            'role' => UserRole::Agent->value,
        ]);

        $this->assertNotNull($invitation->fresh()->accepted_at);
        $this->assertSame($organization->getKey(), session('current_organization_id'));
    }

    public function test_invitation_cannot_be_accepted_by_different_email(): void
    {
        [, $organization] = $this->createTenantUser(UserRole::Owner);
        $invitee = User::factory()->create(['email' => 'other@example.com']);
        $token = Str::random(64);

        Invitation::factory()->create([
            'organization_id' => $organization->getKey(),
            'email' => 'invitee@example.com',
            'role' => UserRole::Agent,
            'token_hash' => hash('sha256', $token),
        ]);

        $this->actingAs($invitee)
            ->post(route('invitations.accept', ['token' => $token]))
            ->assertForbidden();

        $this->assertDatabaseMissing('memberships', [
            'organization_id' => $organization->getKey(),
            'user_id' => $invitee->getKey(),
        ]);
    }

    public function test_expired_invitation_cannot_be_accepted(): void
    {
        [, $organization] = $this->createTenantUser(UserRole::Owner);
        $invitee = User::factory()->create(['email' => 'invitee@example.com']);
        $token = Str::random(64);

        Invitation::factory()->expired()->create([
            'organization_id' => $organization->getKey(),
            'email' => 'invitee@example.com',
            'role' => UserRole::Agent,
            'token_hash' => hash('sha256', $token),
        ]);

        $this->actingAs($invitee)
            ->post(route('invitations.accept', ['token' => $token]))
            ->assertRedirect(route('invitations.show', ['token' => $token]))
            ->assertSessionHas('error');
    }

    public function test_guest_can_view_invitation_page(): void
    {
        $this->withoutVite();

        [, $organization] = $this->createTenantUser(UserRole::Owner);
        $token = Str::random(64);

        Invitation::factory()->create([
            'organization_id' => $organization->getKey(),
            'email' => 'invitee@example.com',
            'role' => UserRole::Customer,
            'token_hash' => hash('sha256', $token),
        ]);

        $this->get(route('invitations.show', ['token' => $token]))
            ->assertOk()
            ->assertSee($organization->name)
            ->assertSee('Masuk untuk menerima');
    }

    public function test_owner_can_revoke_invitation(): void
    {
        [$owner, $organization] = $this->createTenantUser(UserRole::Owner);

        $invitation = Invitation::factory()->create([
            'organization_id' => $organization->getKey(),
            'email' => 'invitee@example.com',
            'role' => UserRole::Agent,
        ]);

        $this->actingAs($owner)
            ->delete(route('admin.members.invitations.destroy', $invitation))
            ->assertRedirect();

        $this->assertDatabaseMissing('invitations', ['id' => $invitation->getKey()]);
    }

    public function test_admin_cannot_invite_owner(): void
    {
        [$owner, $organization] = $this->createTenantUser(UserRole::Admin);

        $this->actingAs($owner)
            ->post(route('admin.members.invitations.store'), [
                'email' => 'new-owner@example.com',
                'role' => UserRole::Owner->value,
            ])
            ->assertSessionHasErrors('role');

        $this->assertSame($organization->getKey(), $organization->getKey());
    }
}
