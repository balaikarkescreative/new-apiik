<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Izin;

class IzinCreatedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $izin;

    public function __construct(Izin $izin)
    {
        $this->izin = $izin;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pengajuan Izin Berhasil Dibuat - ID: ' . $this->izin->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.izin-created',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
