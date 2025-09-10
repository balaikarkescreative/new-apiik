<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountActivationNotification extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    public string $loginUrl;

    public string $defaultPassword;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->loginUrl = route('filament.admin.auth.login');
        $this->defaultPassword = '12345678'; // Default password
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Akun APIIK Anda Telah Aktif - '.$this->user->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.account-activation',
            with: [
                'user' => $this->user,
                'username' => $this->user->username,
                'password' => $this->defaultPassword,
                'loginUrl' => $this->loginUrl,
                'activationDate' => now()->format('d F Y H:i'),
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
