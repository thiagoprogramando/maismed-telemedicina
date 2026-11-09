<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SaleTransferMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly array $data)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->data['subject'] ?? ('Transferência de beneficiário — ' . config('app.name')),
            from: new Address(
                $this->data['fromEmail'] ?? config('mail.from.address'),
                $this->data['fromName'] ?? config('mail.from.name', config('app.name'))
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mails.sale_transfer',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
