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
    public $fileSbbk;

    public function __construct(Icv $icv, $fileSbbk)
    {
        $this->icv = $icv;
        $this->fileSbbk = $fileSbbk;
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
        // Attach SBBK file
        $attachments = [];

        if ($this->fileSbbk) {
            $attachments[] = \Illuminate\Mail\Mailables\Attachment::fromStorageDisk(
                'public',
                $this->fileSbbk
            )->as('SBBK_ICV_' . $this->icv->id . '.pdf')
            ->withMime('application/pdf');
        }

        return $attachments;
    }
}
