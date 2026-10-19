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
     * Generate DomPDF instance for the invoice.
     */
    public function generate(Invoice $invoice, ?BusinessProfile $profile = null, ?string $templateOverride = null)
    {
        $profile ??= BusinessProfile::current();
        $template = $templateOverride ?: ($invoice->pdf_template ?: ($profile->default_template ?: 'modern'));
        if (!$this->supportsTemplate($template)) {
            $template = 'modern';
        }
        $pageSize = strtolower($invoice->page_size ?: ($profile->default_page_size ?: 'a4'));
        $locale = $invoice->locale ?: ($profile->default_locale ?: 'en');

        // Payment QR Code (if payment link or bank details available)
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

        // View mapping
        $viewName = "invoices.pdf.{$template}";
        if (!view()->exists($viewName)) {
            $viewName = 'invoices.pdf.modern';
        }

        $showSignature = $invoice->show_signature ?? $profile->show_signature ?? false;
        $signaturePath = $profile->signature_path ?? null;

        $pdf = Pdf::loadView($viewName, [
            'invoice' => $invoice,
            'profile' => $profile,
            'client' => $invoice->client,
            'items' => $invoice->items,
            'payments' => $invoice->payments,
            'qrCodeSvg' => $qrCodeDataUri,
            'locale' => $locale,
            'showSignature' => $showSignature,
            'signaturePath' => $signaturePath,
        ]);

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
