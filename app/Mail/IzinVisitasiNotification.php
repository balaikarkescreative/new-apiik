<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Izin;

class IzinVisitasiNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $izin;

    public function __construct(Izin $izin)
    {
        // Ensure the relationship is loaded
        $this->izin = $izin->load('izinDokumenPks', 'user');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Penjadwalan Visitasi - ID: ' . $this->izin->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.izin-visitasi',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
