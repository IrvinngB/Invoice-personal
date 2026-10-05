<?php

namespace Tests\Unit;

use App\Services\InvoiceCalculationService;
use PHPUnit\Framework\TestCase;

class InvoiceCalculationServiceTest extends TestCase
{
    private InvoiceCalculationService $calc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calc = new InvoiceCalculationService();
    }

    public function test_simple_line_item_without_tax_or_discount(): void
    {
        // 5 hours @ $50.00 (5000 cents) = $250.00 (25000 cents)
        $line = $this->calc->calculateLineItem(5, 5000, 0, 0, false);

        $this->assertEquals(25000, $line['subtotal_cents']);
        $this->assertEquals(0, $line['discount_amount_cents']);
        $this->assertEquals(0, $line['tax_amount_cents']);
        $this->assertEquals(25000, $line['total_cents']);
    }

    public function test_fractional_hours_and_rounding(): void
    {
        // 3.75 hours @ $75.50 (7550 cents) = 28312.5 -> rounds to 28313 cents
        $line = $this->calc->calculateLineItem(3.75, 7550, 0, 0, false);

        $this->assertEquals(28313, $line['subtotal_cents']);
        $this->assertEquals(28313, $line['total_cents']);
    }

    public function test_line_item_with_percentage_discount(): void
    {
        // 10 units @ $100.00 (10000 cents) = $1000.00 (100000 cents)
        // 15% discount = 1500 bps -> discount = 15000 cents, net = 85000 cents
        $line = $this->calc->calculateLineItem(10, 10000, 1500, 0, false);

        $this->assertEquals(15000, $line['discount_amount_cents']);
        $this->assertEquals(85000, $line['subtotal_cents']);
        $this->assertEquals(85000, $line['total_cents']);
    }

    public function test_tax_exclusive_calculation(): void
    {
        // $100.00 (10000 cents) with 21% VAT (2100 bps)
        // subtotal = 10000, tax = 2100, total = 12100
        $line = $this->calc->calculateLineItem(1, 10000, 0, 2100, false);

        $this->assertEquals(10000, $line['subtotal_cents']);
        $this->assertEquals(2100, $line['tax_amount_cents']);
        $this->assertEquals(12100, $line['total_cents']);
    }

    public function test_tax_inclusive_calculation(): void
    {
        // Total price is $121.00 (12100 cents) including 21% VAT
        // Base = 12100 / 1.21 = 10000 cents, tax = 2100 cents
        $line = $this->calc->calculateLineItem(1, 12100, 0, 2100, true);

        $this->assertEquals(10000, $line['subtotal_cents']);
        $this->assertEquals(2100, $line['tax_amount_cents']);
        $this->assertEquals(12100, $line['total_cents']);
    }

    public function test_full_invoice_calculation_with_multi_tax_breakdown_and_shipping(): void
    {
        $items = [
            // Line 1: 10 hrs @ $100 = $1,000 with 21% VAT (2100 bps) -> subtotal $1000, tax $210, total $1210
            [
                'description' => 'Backend Development',
                'quantity' => 10,
                'unit_price_cents' => 10000,
                'discount_bps' => 0,
                'tax_rate_bps' => 2100,
                'tax_name' => 'VAT 21%',
            ],
            // Line 2: 2 books @ $50 = $100 with 10% discount = $90 with 5% Reduced Tax (500 bps) -> subtotal $90, tax $4.50 (450 cents), total $94.50
            [
                'description' => 'Documentation Guide',
                'quantity' => 2,
                'unit_price_cents' => 5000,
                'discount_bps' => 1000,
                'tax_rate_bps' => 500,
                'tax_name' => 'Reduced VAT 5%',
            ],
        ];

        $shippingFeeCents = 1500; // $15.00
        $amountPaidCents = 50000; // $500.00 partially paid

        $invoice = $this->calc->calculateInvoice($items, $shippingFeeCents, false, $amountPaidCents);

        $this->assertEquals(109000, $invoice['subtotal_cents']); // 100000 + 9000
        $this->assertEquals(1000, $invoice['discount_total_cents']);
        $this->assertEquals(21000 + 450, $invoice['tax_total_cents']); // 21450
        $this->assertEquals(1500, $invoice['shipping_fee_cents']);

        // Expected Total: 109000 (subtotal) + 21450 (tax) + 1500 (shipping) = 131950 cents ($1,319.50)
        $this->assertEquals(131950, $invoice['total_cents']);
        $this->assertEquals(50000, $invoice['amount_paid_cents']);
        // Balance due: 131950 - 50000 = 81950 cents ($819.50)
        $this->assertEquals(81950, $invoice['balance_cents']);

        // Check tax breakdown grouping
        $this->assertCount(2, $invoice['tax_breakdown']);
        $this->assertEquals('VAT 21%', $invoice['tax_breakdown'][0]['tax_name']);
        $this->assertEquals(21000, $invoice['tax_breakdown'][0]['tax_amount_cents']);
        $this->assertEquals('Reduced VAT 5%', $invoice['tax_breakdown'][1]['tax_name']);
        $this->assertEquals(450, $invoice['tax_breakdown'][1]['tax_amount_cents']);
    }

    public function test_lifecycle_status_transitions(): void
    {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $futureDate = date('Y-m-d', strtotime('+14 days'));

        // 1. Unfinalized draft remains draft
        $status = $this->calc->determineStatus('draft', 10000, 0, $futureDate, false);
        $this->assertEquals('draft', $status);

        // 2. Finalized unpaid invoice with future due date is 'sent'
        $status = $this->calc->determineStatus('sent', 10000, 0, $futureDate, true);
        $this->assertEquals('sent', $status);

        // 3. Partial payment marks as partially_paid
        $status = $this->calc->determineStatus('sent', 10000, 5000, $futureDate, true);
        $this->assertEquals('partially_paid', $status);

        // 4. Full payment marks as paid
        $status = $this->calc->determineStatus('sent', 10000, 10000, $futureDate, true);
        $this->assertEquals('paid', $status);

        // 5. Overpayment also marks as paid
        $status = $this->calc->determineStatus('sent', 10000, 15000, $futureDate, true);
        $this->assertEquals('paid', $status);

        // 6. Overdue based on due date
        $status = $this->calc->determineStatus('sent', 10000, 0, $yesterday, true);
        $this->assertEquals('overdue', $status);

        // 7. Void stays void
        $status = $this->calc->determineStatus('void', 10000, 0, $yesterday, true);
        $this->assertEquals('void', $status);
    }
}
