<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SaleAgreementMail extends Mailable {

    use Queueable, SerializesModels;

    public function __construct(public readonly array $data) {
    }

    public function envelope(): Envelope {
        $bcc = [];
        if (!empty($this->data['bcc'])) {
            $bcc[] = new Address((string) $this->data['bcc']);
        }

        return new Envelope(
            subject: $this->data['subject'] ?? ('Cópia do acordo - ' . config('app.name')),
            from: new Address(
                $this->data['fromEmail'] ?? config('mail.from.address'),
                $this->data['fromName'] ?? config('mail.from.name', config('app.name'))
            ),
            bcc: $bcc,
        );
    }

    public function content(): Content {
        return new Content(
            view: 'mails.sale_agreement',
        );
    }

    public function attachments(): array {
        return [];
    }
}
