<?php

namespace App\Services;

use App\Models\BusinessProfile;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class PdfService
{
    public const SUPPORTED_TEMPLATES = ['modern', 'classic', 'minimal', 'emerald'];

    public function supportsTemplate(?string $template): bool
    {
        return in_array($template, self::SUPPORTED_TEMPLATES, true);
    }

    /**
     * Design tokens for a preset (see config/invoice_themes.php).
     */
    public function theme(string $template): array
    {
        return config("invoice_themes.presets.{$template}") ?? config('invoice_themes.presets.modern');
    }

    /**
     * Resolve template, locale and payment QR into the view-model the layout renders.
     */
    public function context(Invoice $invoice, ?BusinessProfile $profile = null, ?string $templateOverride = null): InvoicePdfContext
    {
        $profile ??= BusinessProfile::current();
        $template = $templateOverride ?: ($invoice->pdf_template ?: ($profile->default_template ?: 'modern'));
        if (!$this->supportsTemplate($template)) {
            $template = 'modern';
        }
        $locale = $invoice->locale ?: ($profile->default_locale ?: 'en');

        // Payment QR Code (if payment link available)
        $qrCodeDataUri = null;
        $qrPayload = $invoice->payment_link ?: $profile->payment_link;
        if (!empty($qrPayload)) {
            try {
                $options = new QROptions([
                    'outputType' => QRCode::OUTPUT_MARKUP_SVG,
                    'eccLevel' => QRCode::ECC_M,
                    'scale' => 4,
                    'svgUseFill' => true,
                ]);
                $qrCodeDataUri = (new QRCode($options))->render($qrPayload);
            } catch (\Throwable $e) {
                $qrCodeDataUri = null;
            }
        }

        return new InvoicePdfContext(
            invoice: $invoice,
            profile: $profile,
            locale: $locale,
            theme: $this->theme($template),
            showSignature: (bool) ($invoice->show_signature ?? $profile->show_signature ?? false),
            qrCodeUri: $qrCodeDataUri,
        );
    }

    /**
     * Rendered HTML of the document (what DomPDF receives).
     */
    public function html(Invoice $invoice, ?BusinessProfile $profile = null, ?string $templateOverride = null): string
    {
        return view('invoices.pdf.document', ['ctx' => $this->context($invoice, $profile, $templateOverride)])->render();
    }

    /**
     * Generate DomPDF instance for the invoice.
     */
    public function generate(Invoice $invoice, ?BusinessProfile $profile = null, ?string $templateOverride = null)
    {
        $profile ??= BusinessProfile::current();
        $pageSize = strtolower($invoice->page_size ?: ($profile->default_page_size ?: 'a4'));

        $pdf = Pdf::loadView('invoices.pdf.document', ['ctx' => $this->context($invoice, $profile, $templateOverride)]);

        $pdf->setPaper($pageSize === 'letter' ? 'letter' : 'a4', 'portrait');
        $pdf->setOption([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'Helvetica',
            'dpi' => 150,
        ]);

        return $pdf;
    }

    /**
     * Get safe filename for download.
     */
    public function getFilename(Invoice $invoice): string
    {
        $safeNumber = preg_replace('/[^A-Za-z0-9\-_]/', '_', $invoice->invoice_number);
        $safeClient = preg_replace('/[^A-Za-z0-9\-_]/', '_', $invoice->client->company ?: $invoice->client->name);
        return "{$safeNumber}_{$safeClient}.pdf";
    }
}
