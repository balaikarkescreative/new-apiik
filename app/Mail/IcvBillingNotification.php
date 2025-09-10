<?php

// filepath: /home/syhrl/Documents/new-apiik2/app/Mail/IcvBillingNotification.php

namespace App\Mail;

use App\Models\Icv;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IcvBillingNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $icv;

    public $kodeBilling;

    public $nominal;

    public function __construct(Icv $icv, $kodeBilling, $nominal)
    {
        $this->icv = $icv;
        $this->kodeBilling = $kodeBilling;
        $this->nominal = $nominal;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Kode Billing ICV Diterbitkan - ID: '.$this->icv->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.icv.billing',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
