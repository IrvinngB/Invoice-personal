<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\RecurringInvoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_invoice_domain_lifecycle_and_payments(): void
    {
        $profile = BusinessProfile::current();
        $this->assertNotNull($profile);

        $client = Client::create([
            'name' => 'Acme Corporation',
            'company' => 'Acme Corp',
            'email' => 'finance@acme.test',
            'currency' => 'USD',
        ]);

        $invoice = Invoice::create([
            'invoice_number' => $profile->generateNextInvoiceNumber(),
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'currency' => 'USD',
        ]);

        // Add 2 items:
        // Item 1: 10 hrs @ $150 = $1500 (150000 cents), 20% VAT (2000 bps)
        $invoice->items()->create([
            'description' => 'Architecture Consulting',
            'quantity' => 10,
            'unit' => 'hours',
            'unit_price_cents' => 15000,
            'tax_rate_bps' => 2000,
            'tax_name' => 'VAT 20%',
        ]);

        // Item 2: 1 fixed setup @ $500 (50000 cents), 10% discount (1000 bps), 20% VAT (2000 bps)
        $invoice->items()->create([
            'description' => 'Server Setup',
            'quantity' => 1,
            'unit' => 'units',
            'unit_price_cents' => 50000,
            'discount_bps' => 1000,
            'tax_rate_bps' => 2000,
            'tax_name' => 'VAT 20%',
        ]);

        $invoice->recalculateTotals();

        // Calculations:
        // Item 1: $1,500.00 subtotal, $300.00 tax => $1,800.00 total
        // Item 2: $500.00 - $50.00 discount = $450.00 subtotal, $90.00 tax => $540.00 total
        // Total subtotal: $1,950.00 (195000 cents)
        // Total discount: $50.00 (5000 cents)
        // Total tax: $390.00 (39000 cents)
        // Total: $2,340.00 (234000 cents)
        $this->assertEquals(195000, $invoice->subtotal_cents);
        $this->assertEquals(5000, $invoice->discount_total_cents);
        $this->assertEquals(39000, $invoice->tax_total_cents);
        $this->assertEquals(234000, $invoice->total_cents);
        $this->assertEquals(234000, $invoice->balance_cents);
        $this->assertEquals('draft', $invoice->status);

        // Finalize invoice
        $invoice->finalize();
        $this->assertTrue($invoice->isFinalized());
        $this->assertEquals('sent', $invoice->status);

        // Record partial payment of $1,000.00 (100000 cents)
        $payment1 = $invoice->recordPayment(100000, now()->toDateString(), 'bank_transfer', 'WIRE-001');
        $this->assertEquals('partially_paid', $invoice->status);
        $this->assertEquals(100000, $invoice->amount_paid_cents);
        $this->assertEquals(134000, $invoice->balance_cents); // $1,340.00 left

        // Record remaining payment of $1,340.00 (134000 cents)
        $payment2 = $invoice->recordPayment(134000, now()->toDateString(), 'stripe_link', 'CH_9988');
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(234000, $invoice->amount_paid_cents);
        $this->assertEquals(0, $invoice->balance_cents);
        $this->assertTrue($invoice->isPaid());

        // Test Duplicate
        $duplicated = $invoice->duplicate();
        $this->assertEquals('draft', $duplicated->status);
        $this->assertNotEquals($invoice->invoice_number, $duplicated->invoice_number);
        $this->assertEquals(2, $duplicated->items()->count());
        $this->assertEquals(0, $duplicated->amount_paid_cents);

        // Test Credit Note
        $creditNote = $invoice->createCreditNote();
        $this->assertEquals('credit_note', $creditNote->type);
        $this->assertEquals($invoice->id, $creditNote->parent_invoice_id);
        $this->assertStringContainsString('Credit note for invoice', $creditNote->notes);
    }

    public function test_recurring_invoice_generation(): void
    {
        $client = Client::create([
            'name' => 'Monthly Retainer Client',
            'currency' => 'EUR',
        ]);

        $recurring = RecurringInvoice::create([
            'client_id' => $client->id,
            'frequency' => 'monthly',
            'start_date' => now()->toDateString(),
            'next_issue_date' => now()->toDateString(),
            'template_payload' => [
                'currency' => 'EUR',
                'shipping_fee_cents' => 0,
                'items' => [
                    [
                        'description' => 'Monthly Retainer Service',
                        'quantity' => 1,
                        'unit' => 'month',
                        'unit_price_cents' => 200000,
                        'tax_rate_bps' => 2100,
                        'tax_name' => 'VAT 21%',
                    ],
                ],
            ],
        ]);

        $invoice = $recurring->generateInvoice();

        $this->assertEquals('invoice', $invoice->type);
        $this->assertEquals($client->id, $invoice->client_id);
        $this->assertEquals('EUR', $invoice->currency);
        $this->assertEquals(200000, $invoice->subtotal_cents);
        $this->assertEquals(42000, $invoice->tax_total_cents);
        $this->assertEquals(242000, $invoice->total_cents);

        // Verify next issue date was advanced by 1 month
        $expectedNext = now()->addMonthNoOverflow()->toDateString();
        $this->assertEquals($expectedNext, $recurring->fresh()->next_issue_date->toDateString());
    }
}
