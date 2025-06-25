<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Izin;

class IzinPersetujuanNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $izin;

    public function __construct(Izin $izin)
    {
        // Ensure all required relationships are loaded
        $this->izin = $izin->load(['izinPersetujuan', 'izinDokumen', 'izinDokumenPks', 'user']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Izin Disetujui - ID: ' . $this->izin->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.izin-persetujuan',
        );
    }

    public function attachments(): array
    {
        // Attach surat persetujuan jika ada
        $attachments = [];

        if ($this->izin->izinPersetujuan && $this->izin->izinPersetujuan->file_persetujuan) {
            $attachments[] = \Illuminate\Mail\Mailables\Attachment::fromStorageDisk(
                'public',
                $this->izin->izinPersetujuan->file_persetujuan
            )->as('Surat_Persetujuan_' . $this->izin->id . '.pdf')
            ->withMime('application/pdf');
        }

        return $attachments;
    }
}
