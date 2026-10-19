<?php

namespace App\Services;

use App\Models\BusinessProfile;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Carbon\Carbon;

/**
 * Builds an unsaved Invoice from the (possibly half-typed) create form so it
 * can be rendered as a PDF preview. Nothing here touches the database.
 *
 * Input is deliberately not validated: while someone is typing, a missing
 * client or a non-numeric quantity must degrade to a sensible blank, not an error.
 */
class DraftInvoiceFactory
{
    public function __construct(private readonly InvoiceCalculationService $calculator)
    {
    }

    public function make(array $data, BusinessProfile $profile, ?Client $client = null): Invoice
    {
        $locale = in_array($data['locale'] ?? null, ['en', 'es'], true) ? $data['locale'] : ($profile->default_locale ?: 'en');
        $taxInclusive = filter_var($data['tax_inclusive'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $shippingCents = (int) round($this->number($data['shipping_fee'] ?? 0) * 100);

        $lines = $this->lines($data['items'] ?? []);
        $result = $this->calculator->calculateInvoice(
            array_map(fn ($l) => [
                'quantity' => $l['quantity'],
                'unit_price_cents' => $l['unit_price_cents'],
                'discount_bps' => $l['discount_bps'],
                'tax_rate_bps' => $l['tax_rate_bps'],
                'tax_name' => $l['tax_name'],
            ], $lines),
            $shippingCents,
            $taxInclusive,
        );

        $items = collect();
        foreach ($lines as $i => $line) {
            $calc = $result['items'][$i];
            $items->push(new InvoiceItem([
                'sort_order' => $i,
                'description' => $line['description'],
                'quantity' => $line['quantity'],
                'unit' => $line['unit'],
                'unit_price_cents' => $line['unit_price_cents'],
                'discount_bps' => $line['discount_bps'],
                'tax_rate_bps' => $line['tax_rate_bps'],
                'subtotal_cents' => $calc['subtotal_cents'],
                'discount_amount_cents' => $calc['discount_amount_cents'],
                'tax_amount_cents' => $calc['tax_amount_cents'],
                'total_cents' => $calc['total_cents'],
            ]));
        }

        $issue = $this->date($data['issue_date'] ?? null) ?? now();
        $due = $this->date($data['due_date'] ?? null) ?? $issue->copy()->addDays((int) ($profile->default_payment_terms_days ?: 14));

        $invoice = new Invoice([
            'invoice_number' => trim((string) ($data['invoice_number'] ?? '')) ?: '—',
            'type' => in_array($data['type'] ?? null, ['invoice', 'quote', 'credit_note'], true) ? $data['type'] : 'invoice',
            'status' => 'draft',
            'issue_date' => $issue,
            'due_date' => $due,
            'currency' => strtoupper(substr((string) ($data['currency'] ?? ''), 0, 3)) ?: ($profile->default_currency ?: 'USD'),
            'exchange_rate' => $this->number($data['exchange_rate'] ?? 1) ?: 1,
            'locale' => $locale,
            'tax_inclusive' => $taxInclusive,
            'subtotal_cents' => $result['subtotal_cents'],
            'discount_total_cents' => $result['discount_total_cents'],
            'tax_total_cents' => $result['tax_total_cents'],
            'shipping_fee_cents' => $result['shipping_fee_cents'],
            'total_cents' => $result['total_cents'],
            'amount_paid_cents' => 0,
            'balance_cents' => $result['balance_cents'],
            'notes' => $this->text($data['notes'] ?? null),
            'payment_terms' => $this->text($data['payment_terms'] ?? null),
            'payment_instructions' => $this->text($data['payment_instructions'] ?? null),
            'payment_link' => $this->text($data['payment_link'] ?? null),
            'page_size' => in_array($data['page_size'] ?? null, ['a4', 'letter'], true) ? $data['page_size'] : null,
            'show_signature' => array_key_exists('show_signature', $data)
                ? filter_var($data['show_signature'], FILTER_VALIDATE_BOOLEAN)
                : null,
        ]);

        $invoice->setRelation('client', $client ?? $this->placeholderClient($locale));
        $invoice->setRelation('items', $items);

        return $invoice;
    }

    /**
     * Rows the user has not touched yet (no description, no price) are not lines.
     *
     * @return list<array<string, mixed>>
     */
    private function lines(mixed $rawItems): array
    {
        $lines = [];
        foreach (is_array($rawItems) ? $rawItems : [] as $raw) {
            if (!is_array($raw)) {
                continue;
            }
            $description = trim((string) ($raw['description'] ?? ''));
            $priceCents = (int) round($this->number($raw['unit_price'] ?? 0) * 100);
            if ($description === '' && $priceCents === 0) {
                continue;
            }

            $lines[] = [
                'description' => $description,
                'quantity' => $this->number($raw['quantity'] ?? 1),
                'unit' => trim((string) ($raw['unit'] ?? '')),
                'unit_price_cents' => $priceCents,
                'discount_bps' => (int) round(min(100, max(0, $this->number($raw['discount_percent'] ?? 0))) * 100),
                'tax_rate_bps' => (int) round(min(100, max(0, $this->number($raw['tax_rate_percent'] ?? 0))) * 100),
                'tax_name' => $this->text($raw['tax_name'] ?? null),
            ];
        }

        return $lines;
    }

    private function placeholderClient(string $locale): Client
    {
        return new Client([
            'name' => $locale === 'es' ? 'Selecciona un cliente' : 'Select a client',
        ]);
    }

    private function number(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function text(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? null : $value;
    }

    private function date(mixed $value): ?Carbon
    {
        try {
            return is_string($value) && $value !== '' ? Carbon::parse($value) : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
