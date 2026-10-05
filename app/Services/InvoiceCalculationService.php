<?php

namespace App\Services;

class InvoiceCalculationService
{
    /**
     * Calculate line item financial components with integer cents precision.
     *
     * @param float|string|int $quantity Number of hours/units/days
     * @param int $unitPriceCents Unit price in integer cents
     * @param int $discountBps Discount in basis points (100 bps = 1%)
     * @param int $taxRateBps Tax rate in basis points (2100 bps = 21%)
     * @param bool $taxInclusive Whether unit price includes tax
     * @return array
     */
    public function calculateLineItem(
        float|string|int $quantity,
        int $unitPriceCents,
        int $discountBps = 0,
        int $taxRateBps = 0,
        bool $taxInclusive = false
    ): array {
        $qty = (float) $quantity;
        $rawLineTotal = (int) round($qty * $unitPriceCents);

        // Discount calculation
        $discountAmountCents = 0;
        if ($discountBps > 0) {
            $discountAmountCents = (int) round(($rawLineTotal * $discountBps) / 10000);
            $discountAmountCents = min($discountAmountCents, $rawLineTotal);
        }

        $afterDiscountCents = $rawLineTotal - $discountAmountCents;

        if ($taxInclusive) {
            // Price already includes tax: extract base and tax
            if ($taxRateBps > 0) {
                $divisor = 1 + ($taxRateBps / 10000);
                $netCents = (int) round($afterDiscountCents / $divisor);
                $taxAmountCents = $afterDiscountCents - $netCents;
            } else {
                $netCents = $afterDiscountCents;
                $taxAmountCents = 0;
            }

            return [
                'raw_subtotal_cents' => $rawLineTotal,
                'discount_amount_cents' => $discountAmountCents,
                'subtotal_cents' => $netCents, // net before tax
                'tax_amount_cents' => $taxAmountCents,
                'total_cents' => $afterDiscountCents, // inclusive total
            ];
        }

        // Tax-exclusive (default): add tax on top of discounted subtotal
        $taxAmountCents = 0;
        if ($taxRateBps > 0) {
            $taxAmountCents = (int) round(($afterDiscountCents * $taxRateBps) / 10000);
        }

        return [
            'raw_subtotal_cents' => $rawLineTotal,
            'discount_amount_cents' => $discountAmountCents,
            'subtotal_cents' => $afterDiscountCents,
            'tax_amount_cents' => $taxAmountCents,
            'total_cents' => $afterDiscountCents + $taxAmountCents,
        ];
    }

    /**
     * Calculate invoice totals across all lines, with tax grouping and balance due.
     *
     * @param array $lineItems Array of items, each containing: quantity, unit_price_cents, discount_bps, tax_rate_bps, tax_name
     * @param int $shippingFeeCents Optional extra/shipping fee in cents
     * @param bool $taxInclusive Whether pricing is tax-inclusive
     * @param int $amountPaidCents Total payments already recorded in cents
     * @return array
     */
    public function calculateInvoice(
        array $lineItems,
        int $shippingFeeCents = 0,
        bool $taxInclusive = false,
        int $amountPaidCents = 0
    ): array {
        $subtotalCents = 0;
        $discountTotalCents = 0;
        $taxTotalCents = 0;
        $calculatedItems = [];
        $taxBreakdown = [];

        foreach ($lineItems as $item) {
            $qty = $item['quantity'] ?? 1;
            $unitPriceCents = (int) ($item['unit_price_cents'] ?? 0);
            $discountBps = (int) ($item['discount_bps'] ?? 0);
            $taxRateBps = (int) ($item['tax_rate_bps'] ?? 0);
            $taxName = !empty($item['tax_name']) ? (string) $item['tax_name'] : ($taxRateBps > 0 ? sprintf('Tax (%.2f%%)', $taxRateBps / 100) : 'None');

            $calc = $this->calculateLineItem($qty, $unitPriceCents, $discountBps, $taxRateBps, $taxInclusive);

            $subtotalCents += $calc['subtotal_cents'];
            $discountTotalCents += $calc['discount_amount_cents'];
            $taxTotalCents += $calc['tax_amount_cents'];

            // Group tax breakdown by rate & name
            if ($calc['tax_amount_cents'] > 0) {
                $groupKey = $taxName . '_' . $taxRateBps;
                if (!isset($taxBreakdown[$groupKey])) {
                    $taxBreakdown[$groupKey] = [
                        'tax_name' => $taxName,
                        'tax_rate_bps' => $taxRateBps,
                        'taxable_cents' => 0,
                        'tax_amount_cents' => 0,
                    ];
                }
                $taxBreakdown[$groupKey]['taxable_cents'] += $calc['subtotal_cents'];
                $taxBreakdown[$groupKey]['tax_amount_cents'] += $calc['tax_amount_cents'];
            }

            $calculatedItems[] = array_merge($item, [
                'subtotal_cents' => $calc['subtotal_cents'],
                'discount_amount_cents' => $calc['discount_amount_cents'],
                'tax_amount_cents' => $calc['tax_amount_cents'],
                'total_cents' => $calc['total_cents'],
            ]);
        }

        $totalCents = $subtotalCents + $taxTotalCents + max(0, $shippingFeeCents);
        $balanceCents = max(0, $totalCents - max(0, $amountPaidCents));

        return [
            'items' => $calculatedItems,
            'subtotal_cents' => $subtotalCents,
            'discount_total_cents' => $discountTotalCents,
            'tax_total_cents' => $taxTotalCents,
            'shipping_fee_cents' => max(0, $shippingFeeCents),
            'tax_breakdown' => array_values($taxBreakdown),
            'total_cents' => $totalCents,
            'amount_paid_cents' => $amountPaidCents,
            'balance_cents' => $balanceCents,
        ];
    }

    /**
     * Compute next lifecycle status based on payments, due date, and current status.
     *
     * @param string $currentStatus
     * @param int $totalCents
     * @param int $amountPaidCents
     * @param string $dueDate YYYY-MM-DD
     * @param bool $isFinalized
     * @return string
     */
    public function determineStatus(
        string $currentStatus,
        int $totalCents,
        int $amountPaidCents,
        string $dueDate,
        bool $isFinalized = false
    ): string {
        if ($currentStatus === 'void') {
            return 'void';
        }

        if ($totalCents > 0 && $amountPaidCents >= $totalCents) {
            return 'paid';
        }

        if ($amountPaidCents > 0 && $amountPaidCents < $totalCents) {
            return 'partially_paid';
        }

        // 0 paid
        if (!$isFinalized && $currentStatus === 'draft') {
            return 'draft';
        }

        $today = date('Y-m-d');
        if ($dueDate < $today && $currentStatus !== 'draft') {
            return 'overdue';
        }

        return $currentStatus === 'draft' ? 'draft' : 'sent';
    }

    /**
     * Format integer cents into a displayable string (e.g. 125000 -> "$1,250.00").
     */
    public static function formatMoney(int $cents, string $currency = 'USD', string $locale = 'en'): string
    {
        $dollars = $cents / 100;
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'CAD' => 'CA$',
            'AUD' => 'AU$',
            'MXN' => 'MX$',
            'CHF' => 'CHF ',
            'JPY' => '¥',
        ];

        $symbol = $symbols[$currency] ?? ($currency . ' ');

        if ($locale === 'es') {
            // Spanish convention: 1.250,00 € or $1.250,00
            return $symbol . number_format($dollars, 2, ',', '.');
        }

        return $symbol . number_format($dollars, 2, '.', ',');
    }
}
