<?php

namespace App\Modules\Tenancy\Notifications;

use App\Modules\Tenancy\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Invitation $invitation,
        public string $plainToken,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->invitation->loadMissing(['organization', 'inviter']);

        $organization = $this->invitation->organization;

        /** @var string|null $inviterName */
        $inviterName = $this->invitation->inviter?->name;

        return (new MailMessage)
            ->subject('Undangan bergabung dengan '.$organization->name)
            ->greeting('Halo,')
            ->line(($inviterName ?? 'Admin').' mengundang Anda bergabung dengan '.$organization->name.' sebagai '.$this->invitation->role->label().'.')
            ->action('Terima undangan', route('invitations.show', ['token' => $this->plainToken]))
            ->line('Undangan berlaku sampai '.$this->invitation->expires_at->translatedFormat('d F Y H:i').'.')
            ->line('Jika Anda tidak merasa diundang, abaikan email ini.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invitation_id' => $this->invitation->getKey(),
            'organization_id' => $this->invitation->organization_id,
        ];
    }
}
