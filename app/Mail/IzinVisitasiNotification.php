<?php

namespace App\Mail;

use App\Models\Izin;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IzinVisitasiNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $izin;

    public function __construct(Izin $izin)
    {
        // Ensure the relationship is loaded
        $this->izin = $izin->load('izinDokumenPks', 'user', 'picDokter');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Penjadwalan Visitasi - ID: '.$this->izin->id,
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
