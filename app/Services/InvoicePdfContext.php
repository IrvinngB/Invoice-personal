<?php

namespace App\Services;

use App\Models\BusinessProfile;
use App\Models\Invoice;
use Carbon\CarbonInterface;

/**
 * View-model for the PDF layout: everything a template needs that is more
 * than plain attribute access (translation, money, dates, asset paths).
 */
class InvoicePdfContext
{
    public function __construct(
        public readonly Invoice $invoice,
        public readonly BusinessProfile $profile,
        public readonly string $locale,
        public readonly array $theme,
        public readonly bool $showSignature = false,
        public readonly ?string $qrCodeUri = null,
        public readonly bool $showPaymentInstructions = true,
    ) {
    }

    public function t(string $key, array $replace = []): string
    {
        return __("invoice.{$key}", $replace, $this->locale);
    }

    public function money(int $cents): string
    {
        return InvoiceCalculationService::formatMoney($cents, $this->invoice->currency, $this->locale);
    }

    public function date(?CarbonInterface $date): ?string
    {
        if (!$date) {
            return null;
        }

        $format = $this->locale === 'es' ? 'D [de] MMMM [de] Y' : 'MMMM D, Y';

        return $date->copy()->locale($this->locale)->isoFormat($format);
    }

    public function docTitle(): string
    {
        return match ($this->invoice->type) {
            'quote' => $this->t('quote'),
            'credit_note' => $this->t('credit_note'),
            default => $this->t('invoice'),
        };
    }

    public function logoPath(): ?string
    {
        return $this->existingPublicPath($this->profile->logo_path);
    }

    public function signaturePath(): ?string
    {
        if (!$this->showSignature) {
            return null;
        }

        return $this->existingPublicPath($this->profile->signature_path);
    }

    /**
     * Signature is switched on but none was uploaded: print a visible reminder
     * instead of a stock signature that could be mistaken for the user's own.
     */
    public function showSignaturePlaceholder(): bool
    {
        return $this->showSignature && $this->signaturePath() === null;
    }

    /**
     * Free-text payment instructions, unless they just repeat a profile field
     * already printed in the bank block.
     */
    public function extraPaymentInstructions(): ?string
    {
        $extra = trim((string) $this->invoice->payment_instructions);
        if ($extra === '') {
            return null;
        }

        $known = array_map('trim', array_filter([
            $this->profile->bank_iban,
            $this->profile->bank_name,
            $this->profile->paypal_email,
            $this->profile->tax_id,
            $this->profile->bank_account_name,
        ]));

        return in_array($extra, $known, true) ? null : $extra;
    }

    public function hasPaymentBlock(): bool
    {
        if (!$this->showPaymentInstructions) {
            return false;
        }

        return $this->profile->bank_iban
            || $this->profile->bank_name
            || $this->profile->paypal_email
            || $this->invoice->payment_link
            || $this->extraPaymentInstructions() !== null
            || $this->qrCodeUri;
    }

    /**
     * @return array{text: string, color: string}|null
     */
    public function watermark(): ?array
    {
        return match (true) {
            $this->invoice->status === 'paid' => ['text' => $this->t('status_paid'), 'color' => 'rgba(34, 197, 94, 0.07)'],
            $this->invoice->status === 'void' => ['text' => $this->t('status_void'), 'color' => 'rgba(239, 68, 68, 0.07)'],
            $this->invoice->isOverdue() => ['text' => $this->t('status_overdue'), 'color' => 'rgba(239, 68, 68, 0.07)'],
            default => null,
        };
    }

    /**
     * @return array{bg: string, fg: string}
     */
    public function statusColors(): array
    {
        return match ($this->invoice->status) {
            'paid' => ['bg' => '#dcfce7', 'fg' => '#15803d'],
            'sent' => ['bg' => '#e0f2fe', 'fg' => '#0369a1'],
            'overdue' => ['bg' => '#fee2e2', 'fg' => '#b91c1c'],
            default => ['bg' => '#f1f5f9', 'fg' => '#475569'],
        };
    }

    public function quantity(mixed $quantity): string
    {
        return rtrim(rtrim(number_format((float) $quantity, 4), '0'), '.');
    }

    private function existingPublicPath(?string $relative): ?string
    {
        if (empty($relative)) {
            return null;
        }

        $path = public_path($relative);

        return file_exists($path) ? $path : null;
    }
}
