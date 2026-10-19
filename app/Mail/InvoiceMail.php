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
        $locale = $this->invoice->locale ?: ($profile->default_locale ?: 'en');
        $type = __($this->invoice->type === 'credit_note' ? 'invoice.credit_note' : "invoice.{$this->invoice->type}", [], $locale);

        return new Envelope(
            subject: __('invoice.mail_subject', [
                'type' => $type,
                'number' => $this->invoice->invoice_number,
                'business' => $profile->business_name,
            ], $locale),
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
                'locale' => $this->invoice->locale ?: (BusinessProfile::current()->default_locale ?: 'en'),
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
