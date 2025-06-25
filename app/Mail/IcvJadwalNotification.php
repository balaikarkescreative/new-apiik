<?php
// filepath: /home/syhrl/Documents/new-apiik2/app/Mail/IcvJadwalNotification.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Icv;

class IcvJadwalNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $icv;
    public $jadwalPengambilan;

    public function __construct(Icv $icv, $jadwalPengambilan)
    {
        $this->icv = $icv;
        $this->jadwalPengambilan = $jadwalPengambilan;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Jadwal Distribusi ICV Ditetapkan - ID: ' . $this->icv->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.icv.jadwal',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
