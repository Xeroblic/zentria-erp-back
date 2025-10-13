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
        $branch = $this->branchName ?: 'sucursal asignada';

        return (new MailMessage)
            ->subject('Invitación de acceso al ERP')
            ->greeting('¡Hola!')
            ->line("Has sido invitado como {$this->role} para la sucursal {$branch}.")
            ->action('Activar tu cuenta', $this->activationUrl)   // <-- BOTÓN
            ->line('Esta es una invitación única; no la compartas.')
            ->when($this->expiresAt, fn($m) => $m->line("Esta invitación vence el: {$this->expiresAt}."));
    }
}
