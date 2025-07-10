<?php
// filepath: /home/syhrl/Documents/new-apiik2/app/Mail/IcvSbbkNotification.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Icv;

class IcvSbbkNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $icv;

    public function __construct(Icv $icv)
    {
        $this->icv = $icv;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'SBBK ICV Diterbitkan - Distribusi Selesai - ID: ' . $this->icv->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.icv.sbbk',
        );
    }

    public function attachments(): array
    {
        // Tidak ada file SBBK yang dilampirkan
        return [];
    }
}
