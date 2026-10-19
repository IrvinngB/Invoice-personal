<?php

namespace App\Services;

use App\Models\BusinessProfile;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;

/**
 * In-memory invoice used to preview a theme. Nothing here is persisted.
 */
class SampleInvoiceFactory
{
    public static function make(BusinessProfile $profile, string $locale): Invoice
    {
        $es = $locale === 'es';

        $client = new Client([
            'name' => 'Alex Rivera',
            'company' => $es ? 'Cliente Ejemplo S.A.' : 'Example Client Inc.',
            'tax_id' => '123456-7-890',
            'billing_address' => $es ? "Av. Principal 123\nCiudad de Panamá" : "123 Main Street\nSpringfield",
            'email' => 'billing@example.com',
        ]);

        $rows = [
            [$es ? 'Diseño de interfaz' : 'Interface design', 12, 'h', 6500, 0, 700],
            [$es ? 'Desarrollo frontend' : 'Frontend development', 20, 'h', 7500, 1000, 700],
            [$es ? 'Revisión y entrega' : 'Review and handover', 1, 'u', 15000, 0, 700],
        ];

        $items = collect();
        $subtotal = $discountTotal = $taxTotal = 0;
        foreach ($rows as $i => [$description, $qty, $unit, $price, $discountBps, $taxBps]) {
            $gross = (int) round($qty * $price);
            $discount = (int) round($gross * $discountBps / 10000);
            $tax = (int) round(($gross - $discount) * $taxBps / 10000);
            $items->push(new InvoiceItem([
                'description' => $description,
                'quantity' => $qty,
                'unit' => $unit,
                'unit_price_cents' => $price,
                'discount_bps' => $discountBps,
                'discount_amount_cents' => $discount,
                'tax_rate_bps' => $taxBps,
                'tax_amount_cents' => $tax,
                'subtotal_cents' => $gross,
                'total_cents' => $gross - $discount,
                'sort_order' => $i,
            ]));
            $subtotal += $gross;
            $discountTotal += $discount;
            $taxTotal += $tax;
        }
        $total = $subtotal - $discountTotal + $taxTotal;

        $invoice = new Invoice([
            'invoice_number' => ($profile->invoice_prefix ?? 'INV-') . str_pad('1', (int) ($profile->invoice_number_digits ?: 4), '0', STR_PAD_LEFT),
            'type' => 'invoice',
            'status' => 'sent',
            'issue_date' => now(),
            'due_date' => now()->addDays((int) ($profile->default_payment_terms_days ?: 14)),
            'currency' => $profile->default_currency ?: 'USD',
            'locale' => $locale,
            'subtotal_cents' => $subtotal,
            'discount_total_cents' => $discountTotal,
            'tax_total_cents' => $taxTotal,
            'shipping_fee_cents' => 0,
            'total_cents' => $total,
            'amount_paid_cents' => 0,
            'balance_cents' => $total,
            'tax_inclusive' => false,
            'notes' => $es ? 'Gracias por su preferencia.' : 'Thank you for your business.',
            'show_signature' => (bool) $profile->show_signature,
        ]);
        $invoice->setRelation('client', $client);
        $invoice->setRelation('items', $items);

        return $invoice;
    }
}
