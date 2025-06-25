<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Izin;

class IzinVerifikasiNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $izin;
    public $hasilVerifikasi;
    public $catatan;

    public function __construct(Izin $izin, $hasilVerifikasi, $catatan = null)
    {
        $this->izin = $izin;
        $this->hasilVerifikasi = $hasilVerifikasi;
        $this->catatan = $catatan;
    }

    public function envelope(): Envelope
    {
        $subject = $this->hasilVerifikasi == 1
            ? 'Verifikasi Dokumen Berhasil - Berkas Lengkap'
            : 'Verifikasi Dokumen - Berkas Tidak Lengkap';

        return new Envelope(
            subject: $subject . ' - ID: ' . $this->izin->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.izin-verifikasi',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
