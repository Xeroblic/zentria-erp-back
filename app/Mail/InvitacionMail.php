<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Invitation;

class InvitacionMail extends Mailable
{
    use Queueable, SerializesModels;

    public $invitation;
    public $activationUrl;

    public function __construct(Invitation $invitation)
    {
        $this->invitation = $invitation;
        $this->activationUrl = $invitation->getActivationUrl();
    }

    public function build()
    {
        return $this->subject('Invitación al Sistema ERP - ' . $this->invitation->company->company_name)
                    ->view('emails.invitacion')
                    ->with([
                        'invitation' => $this->invitation,
                        'activationUrl' => $this->activationUrl,
                        'companyName' => $this->invitation->company->company_name,
                        'branchName' => $this->invitation->branch->branch_name,
                        'invitedByName' => $this->invitation->invitedBy->first_name . ' ' . $this->invitation->invitedBy->last_name,
                        'temporaryPassword' => $this->invitation->temporary_password,
                        'expiresAt' => $this->invitation->expires_at,
                    ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invitación al Sistema ERP',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invitacion',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
