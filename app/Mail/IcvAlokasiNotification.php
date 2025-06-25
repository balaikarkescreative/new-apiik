<?php
// filepath: /home/syhrl/Documents/new-apiik2/app/Mail/IcvAlokasiNotification.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Icv;

class IcvAlokasiNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $icv;
    public $icvDialokasikan;

    public function __construct(Icv $icv, $icvDialokasikan)
    {
        $this->icv = $icv;
        $this->icvDialokasikan = $icvDialokasikan;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'ICV Berhasil Dialokasikan - ID: ' . $this->icv->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.icv.alokasi',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
