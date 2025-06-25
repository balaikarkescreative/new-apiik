<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Icv;

class IcvVerifikasiNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $icv;
    public $hasilVerifikasi;
    public $catatan;

    public function __construct(Icv $icv, $hasilVerifikasi, $catatan = null)
    {
        $this->icv = $icv;
        $this->hasilVerifikasi = $hasilVerifikasi;
        $this->catatan = $catatan;
    }

    public function envelope(): Envelope
    {
        $subject = $this->hasilVerifikasi == '1'
            ? 'Verifikasi ICV Berhasil - Berkas Lengkap'
            : 'Verifikasi ICV - Berkas Tidak Lengkap';

        return new Envelope(
            subject: $subject . ' - ID: ' . $this->icv->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.icv.verifikasi',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
