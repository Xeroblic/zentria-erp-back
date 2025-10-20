<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string  $activationUrl,
        public string  $role,
        public ?string $branchName = null,   // now nullable
        public ?string $expiresAt = null
    ) {}

    public function via($notifiable): array { return ['mail']; }

    public function toMail($notifiable): MailMessage
    {
        // Mejor formato usando Markdown template
        return (new MailMessage)
            ->subject('Invitación de acceso al ERP')
            ->markdown('vendor.mail.invitations.activate', [
                'role'          => $this->role,
                'branchName'    => $this->branchName,
                'activationUrl' => $this->activationUrl,
                'expiresAt'     => $this->expiresAt,
            ]);
    }
}
