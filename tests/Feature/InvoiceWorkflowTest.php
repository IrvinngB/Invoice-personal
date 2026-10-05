<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\RecurringInvoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_invoice_system_workflow(): void
    {
        $profile = BusinessProfile::current();
        $this->assertNotNull($profile);

        // 1. Dashboard loads
        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Studio Overview');

        // 2. Create Client
        $client = Client::create([
            'name' => 'Alice Test',
            'company' => 'Wonderland Labs',
            'tax_id' => 'WL-123456',
            'email' => 'alice@wonderland.test',
            'currency' => 'USD',
        ]);

        // 3. Create Catalog Item
        $item = CatalogItem::create([
            'name' => 'Full-Stack Consulting',
            'unit' => 'hours',
            'unit_price_cents' => 15000, // $150.00
            'tax_rate_bps' => 1000, // 10%
        ]);

        // 4. Create Invoice via POST
        $createResponse = $this->post(route('invoices.store'), [
            'client_id' => $client->id,
            'type' => 'invoice',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'currency' => 'USD',
            'exchange_rate' => 1.0,
            'tax_inclusive' => 0,
            'shipping_fee' => 25.00,
            'notes' => 'Thank you for your business.',
            'pdf_template' => 'modern',
            'page_size' => 'a4',
            'locale' => 'en',
            'items' => [
                [
                    'catalog_item_id' => $item->id,
                    'description' => 'Consulting Sprint 1',
                    'quantity' => 10,
                    'unit' => 'hours',
                    'unit_price' => 150.00,
                    'discount_percent' => 10, // 10% discount on $1,500 = $150 discount => $1,350 subtotal
                    'tax_rate_percent' => 10, // 10% tax on $1,350 = $135
                    'tax_name' => 'VAT 10%',
                ]
            ]
        ]);

        // Total: 1350 (subtotal) + 135 (tax) + 25 (shipping) = $1,510.00 (151000 cents)
        $invoice = Invoice::where('client_id', $client->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(135000, $invoice->subtotal_cents);
        $this->assertEquals(15000, $invoice->discount_total_cents);
        $this->assertEquals(13500, $invoice->tax_total_cents);
        $this->assertEquals(2500, $invoice->shipping_fee_cents);
        $this->assertEquals(151000, $invoice->total_cents);
        $this->assertEquals(151000, $invoice->balance_cents);
        $this->assertEquals('draft', $invoice->status);

        $createResponse->assertRedirect(route('invoices.show', $invoice));

        // 5. Finalize invoice
        $finalizeResponse = $this->post(route('invoices.finalize', $invoice));
        $finalizeResponse->assertRedirect();
        $this->assertTrue($invoice->fresh()->isFinalized());
        $this->assertEquals('sent', $invoice->fresh()->status);

        // 6. Record Partial Payment ($510.00)
        $paymentResponse1 = $this->post(route('invoices.payments.store', $invoice), [
            'amount' => 510.00,
            'payment_date' => now()->toDateString(),
            'method' => 'bank_transfer',
            'reference' => 'WIRE-9988',
        ]);
        $paymentResponse1->assertRedirect();
        $this->assertEquals(51000, $invoice->fresh()->amount_paid_cents);
        $this->assertEquals(100000, $invoice->fresh()->balance_cents); // $1,000 left
        $this->assertEquals('partially_paid', $invoice->fresh()->status);

        // 7. Record Remaining Payment ($1,000.00)
        $paymentResponse2 = $this->post(route('invoices.payments.store', $invoice), [
            'amount' => 1000.00,
            'payment_date' => now()->toDateString(),
            'method' => 'stripe_link',
            'reference' => 'CH_10293',
        ]);
        $paymentResponse2->assertRedirect();
        $this->assertEquals(151000, $invoice->fresh()->amount_paid_cents);
        $this->assertEquals(0, $invoice->fresh()->balance_cents);
        $this->assertEquals('paid', $invoice->fresh()->status);

        // 8. Stream PDF
        $pdfResponse = $this->get(route('invoices.pdf', $invoice));
        $pdfResponse->assertStatus(200);
        $this->assertEquals('application/pdf', $pdfResponse->headers->get('content-type'));

        // 9. Duplicate Invoice
        $duplicateResponse = $this->post(route('invoices.duplicate', $invoice));
        $duplicateResponse->assertRedirect();
        $this->assertEquals(2, Invoice::count());
        $duplicated = Invoice::where('id', '!=', $invoice->id)->first();
        $this->assertEquals('draft', $duplicated->status);
        $this->assertEquals(0, $duplicated->amount_paid_cents);
        $this->assertEquals(151000, $duplicated->balance_cents);

        // 10. Test JSON and CSV backup exports
        $jsonExport = $this->get(route('backup.export-json'));
        $jsonExport->assertStatus(200);
        $jsonExport->assertHeader('content-type', 'application/json');

        $csvExport = $this->get(route('backup.export-csv'));
        $csvExport->assertStatus(200);
        $this->assertStringStartsWith('text/csv', $csvExport->headers->get('content-type'));

        // 11. Test recurring Artisan generation command
        $recurring = RecurringInvoice::create([
            'client_id' => $client->id,
            'frequency' => 'monthly',
            'start_date' => now()->subDay()->toDateString(),
            'next_issue_date' => now()->subDay()->toDateString(),
            'payment_terms_days' => 14,
            'template_payload' => [
                'currency' => 'USD',
                'items' => [
                    [
                        'description' => 'Recurring Retainer Support',
                        'quantity' => 1,
                        'unit' => 'month',
                        'unit_price_cents' => 50000,
                    ]
                ]
            ]
        ]);

        $this->artisan('invoices:generate-recurring')->assertSuccessful();
        $this->assertEquals(3, Invoice::count());
    }
}
