<?php

namespace App\Services;

use App\Models\BusinessProfile;
use App\Models\Invoice;
use App\Models\InvoiceTheme;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class PdfService
{
    public const SUPPORTED_TEMPLATES = ['modern', 'classic', 'minimal', 'emerald'];

    public function supportsTemplate(?string $template): bool
    {
        if (in_array($template, self::SUPPORTED_TEMPLATES, true)) {
            return true;
        }

        $id = InvoiceTheme::idFromKey($template);

        return $id !== null && InvoiceTheme::whereKey($id)->exists();
    }

    /**
     * Preset tokens with unsaved overrides applied (theme editor preview).
     */
    public function previewTheme(string $base, array $overrides): array
    {
        $presets = config('invoice_themes.presets');

        return ThemeTokens::apply($presets[$base] ?? $presets['modern'], $overrides);
    }

    /**
     * Design tokens for a preset or a "custom:{id}" theme (see config/invoice_themes.php).
     */
    public function theme(string $template): array
    {
        $presets = config('invoice_themes.presets');

        if ($id = InvoiceTheme::idFromKey($template)) {
            $custom = InvoiceTheme::find($id);
            if ($custom) {
                return ThemeTokens::apply($presets[$custom->base] ?? $presets['modern'], $custom->overrides ?? []);
            }
        }

        return $presets[$template] ?? $presets['modern'];
    }

    /**
     * Every selectable template: key => label (presets first, then custom themes).
     *
     * @return array<string, string>
     */
    public function availableTemplates(): array
    {
        $templates = [];
        foreach (self::SUPPORTED_TEMPLATES as $key) {
            $templates[$key] = __("app.template_{$key}");
        }
        foreach (InvoiceTheme::orderBy('name')->get() as $theme) {
            $templates[$theme->key()] = $theme->name;
        }

        return $templates;
    }

    /**
     * Resolve template, locale and payment QR into the view-model the layout renders.
     */
    public function context(Invoice $invoice, ?BusinessProfile $profile = null, ?string $templateOverride = null, ?array $themeTokens = null): InvoicePdfContext
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
            theme: $themeTokens ?? $this->theme($template),
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
    public function generate(Invoice $invoice, ?BusinessProfile $profile = null, ?string $templateOverride = null, ?array $themeTokens = null)
    {
        $profile ??= BusinessProfile::current();
        $pageSize = strtolower($invoice->page_size ?: ($profile->default_page_size ?: 'a4'));

        $pdf = Pdf::loadView('invoices.pdf.document', ['ctx' => $this->context($invoice, $profile, $templateOverride, $themeTokens)]);

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
