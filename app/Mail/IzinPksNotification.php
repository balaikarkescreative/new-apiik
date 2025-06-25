<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Izin;

class IzinPksNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $izin;

    public function __construct(Izin $izin)
    {
        // Ensure all required relationships are loaded
        $this->izin = $izin->load(['izinDokumenPks', 'izinPersetujuan', 'izinDokumen', 'user']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'PKS Selesai - ID: ' . $this->izin->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.izin-pks-selesai',
        );
    }

    public function attachments(): array
    {
        // Attach PKS yang sudah ditandatangani
        $attachments = [];

        if ($this->izin->izinDokumenPks && $this->izin->izinDokumenPks->dokumen_pks_bkk) {
            $attachments[] = \Illuminate\Mail\Mailables\Attachment::fromStorageDisk(
                'public',
                $this->izin->izinDokumenPks->dokumen_pks_bkk
            )->as('PKS_Final_' . $this->izin->id . '.pdf')
            ->withMime('application/pdf');
        }

        return $attachments;
    }
}
