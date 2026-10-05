<?php

namespace App\Mail;

use App\Models\BusinessProfile;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public string $pdfContent,
        public string $filename
    ) {}

    public function envelope(): Envelope
    {
        $profile = BusinessProfile::current();
        $type = ucfirst($this->invoice->type);
        return new Envelope(
            subject: "{$type} {$this->invoice->invoice_number} from {$profile->business_name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice',
            with: [
                'invoice' => $this->invoice,
                'profile' => BusinessProfile::current(),
                'client' => $this->invoice->client,
            ]
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContent, $this->filename)
                ->withMime('application/pdf'),
        ];
    }
}
