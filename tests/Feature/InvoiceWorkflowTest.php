<?php

namespace Tests\Feature;

use App\Mail\InvoiceMail;
use App\Models\BusinessProfile;
use App\Models\AuditLog;
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

    public function test_invoice_mail_uses_the_persisted_invoice_locale_not_the_session_locale(): void
    {
        app()->setLocale('en');
        $client = Client::create(['name' => 'Ana Cliente', 'email' => 'ana@example.test']);
        $invoice = Invoice::create([
            'invoice_number' => 'INV-ES-MAIL',
            'type' => 'invoice',
            'client_id' => $client->id,
            'issue_date' => '2026-10-18',
            'due_date' => '2026-11-01',
            'currency' => 'USD',
            'locale' => 'es',
            'balance_cents' => 123456,
        ]);
        $invoice->items()->create([
            'description' => 'Servicio',
            'quantity' => 1,
            'unit' => 'hora',
            'unit_price_cents' => 123456,
            'total_cents' => 123456,
        ]);
        $invoice->recalculateTotals()->refresh();

        app()->setLocale('es');
        $mail = new InvoiceMail($invoice, '%PDF-test', 'invoice.pdf');

        $this->assertStringContainsString('FACTURA', $mail->envelope()->subject);
        $this->assertStringContainsString('Hola Ana Cliente', $mail->render());
        $this->assertStringContainsString('1 de noviembre de 2026', $mail->render());
        $this->assertStringContainsString('$1.234,56', $mail->render());

        $html = view('invoices.pdf.modern', [
            'invoice' => $invoice,
            'profile' => BusinessProfile::current(),
            'client' => $client,
            'items' => $invoice->items,
            'payments' => $invoice->payments,
            'qrCodeSvg' => null,
            'locale' => $invoice->locale,
        ])->render();

        $this->assertStringContainsString('FACTURA', $html);
        $this->assertStringContainsString('18 de octubre de 2026', $html);
        $this->assertStringContainsString('$1.234,56', $html);
    }

    public function test_duplicate_invoice_guard_for_same_client(): void
    {
        $client = Client::create([
            'name' => 'Demo Client',
            'company' => 'Acme Demo Solutions LLC',
            'tax_id' => 'DEMO-123456-2026',
        ]);

        $today = now()->toDateString();

        // Create first invoice
        $this->post(route('invoices.store'), [
            'client_id' => $client->id,
            'type' => 'invoice',
            'issue_date' => $today,
            'due_date' => now()->addDays(15)->toDateString(),
            'currency' => 'USD',
            'pdf_template' => 'emerald',
            'page_size' => 'letter',
            'locale' => 'es',
            'items' => [
                [
                    'description' => 'Servicios de soporte.',
                    'quantity' => 5,
                    'unit' => 'horas',
                    'unit_price' => 10.00,
                ]
            ]
        ])->assertSessionHasNoErrors();

        $this->assertEquals(1, Invoice::count());

        // Attempt duplicate invoice for same client on same date without confirmation -> must fail
        $duplicateAttempt = $this->post(route('invoices.store'), [
            'client_id' => $client->id,
            'type' => 'invoice',
            'issue_date' => $today,
            'due_date' => now()->addDays(15)->toDateString(),
            'currency' => 'USD',
            'pdf_template' => 'emerald',
            'page_size' => 'letter',
            'locale' => 'es',
            'items' => [
                [
                    'description' => 'Servicios de soporte.',
                    'quantity' => 5,
                    'unit' => 'horas',
                    'unit_price' => 10.00,
                ]
            ]
        ]);

        $duplicateAttempt->assertSessionHasErrors(['client_id']);
        $this->assertEquals(1, Invoice::count());

        // Attempt duplicate invoice WITH allow_duplicate confirmation -> must succeed
        $confirmedAttempt = $this->post(route('invoices.store'), [
            'client_id' => $client->id,
            'type' => 'invoice',
            'issue_date' => $today,
            'due_date' => now()->addDays(15)->toDateString(),
            'currency' => 'USD',
            'pdf_template' => 'emerald',
            'page_size' => 'letter',
            'locale' => 'es',
            'allow_duplicate' => 1,
            'items' => [
                [
                    'description' => 'Servicios adicionales.',
                    'quantity' => 2,
                    'unit' => 'horas',
                    'unit_price' => 10.00,
                ]
            ]
        ]);

        $confirmedAttempt->assertSessionHasNoErrors();
        $this->assertEquals(2, Invoice::count());
    }

    public function test_custom_invoice_number_sequence_continuation(): void
    {
        $client = Client::create([
            'name' => 'Custom Sequence Client',
            'email' => 'client@test.com',
        ]);

        $profile = BusinessProfile::current();
        $profile->update([
            'invoice_prefix' => '',
            'invoice_number_digits' => 5,
            'next_invoice_number' => 1,
        ]);

        // 1. Manually continue sequence starting at 00008 (from previous generator)
        $res = $this->post(route('invoices.store'), [
            'client_id' => $client->id,
            'invoice_number' => '00008',
            'type' => 'invoice',
            'issue_date' => '2026-09-30',
            'due_date' => '2026-10-15',
            'currency' => 'USD',
            'pdf_template' => 'emerald',
            'page_size' => 'letter',
            'locale' => 'es',
            'items' => [
                [
                    'description' => 'Servicios de soporte.',
                    'quantity' => 5,
                    'unit' => 'horas',
                    'unit_price' => 10.00,
                ]
            ]
        ]);

        $res->assertSessionHasNoErrors();
        $this->assertDatabaseHas('invoices', ['invoice_number' => '00008']);

        // Profile next invoice number should have automatically synced to 9!
        $this->assertEquals(9, $profile->fresh()->next_invoice_number);

        // 2. Next invoice without specifying invoice_number should automatically be 00009
        $res2 = $this->post(route('invoices.store'), [
            'client_id' => $client->id,
            'type' => 'invoice',
            'issue_date' => '2026-10-01',
            'due_date' => '2026-10-15',
            'currency' => 'USD',
            'pdf_template' => 'emerald',
            'page_size' => 'letter',
            'locale' => 'es',
            'items' => [
                [
                    'description' => 'Servicios de soporte mes siguiente.',
                    'quantity' => 5,
                    'unit' => 'horas',
                    'unit_price' => 10.00,
                ]
            ]
        ]);

        $res2->assertSessionHasNoErrors();
        $this->assertDatabaseHas('invoices', ['invoice_number' => '00009']);
        $this->assertEquals(10, $profile->fresh()->next_invoice_number);
    }

    public function test_emerald_pdf_template_renders_properly(): void
    {
        $client = Client::create([
            'name' => 'Demo Client Corp',
            'company' => 'Acme Demo Solutions LLC',
            'tax_id' => 'DEMO-123456-2026',
            'billing_address' => "Demo Avenue 100\nCity",
        ]);

        $invoice = Invoice::create([
            'invoice_number' => '00008',
            'type' => 'invoice',
            'status' => 'paid',
            'client_id' => $client->id,
            'issue_date' => '2026-09-30',
            'due_date' => '2026-10-15',
            'currency' => 'USD',
            'pdf_template' => 'emerald',
            'page_size' => 'letter',
            'locale' => 'es',
        ]);

        $invoice->items()->create([
            'description' => 'Servicios de soporte.',
            'quantity' => 5,
            'unit' => 'horas',
            'unit_price_cents' => 1000,
            'total_cents' => 5000,
        ]);
        $invoice->recalculateTotals();

        $auditCount = AuditLog::count();
        $response = $this->get(route('invoices.pdf', $invoice));
        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
        $this->assertSame($auditCount + 1, AuditLog::count());
        $this->assertDatabaseHas('audit_logs', ['invoice_id' => $invoice->id, 'event' => 'pdf_downloaded']);
    }

    public function test_settings_update_handles_default_locale(): void
    {
        $profile = BusinessProfile::current();

        $response = $this->put(route('settings.update'), [
            'business_name' => 'Studio Test Name',
            'owner_name' => 'Consultant Name',
            'default_currency' => 'USD',
            'default_payment_terms_days' => 15,
            'invoice_number_digits' => 5,
            'next_invoice_number' => 9,
            'quote_number_digits' => 5,
            'next_quote_number' => 1,
            'credit_note_number_digits' => 5,
            'next_credit_note_number' => 1,
            'default_template' => 'emerald',
            'default_page_size' => 'letter',
            'default_locale' => 'es',
            'timezone' => 'America/Panama',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals('es', $profile->fresh()->default_locale);

        // Also test omitting default_locale -> should fallback gracefully without error
        $response2 = $this->put(route('settings.update'), [
            'business_name' => 'Studio Test Name',
            'default_currency' => 'USD',
            'default_payment_terms_days' => 15,
            'invoice_number_digits' => 5,
            'next_invoice_number' => 9,
            'quote_number_digits' => 5,
            'next_quote_number' => 1,
            'credit_note_number_digits' => 5,
            'next_credit_note_number' => 1,
            'default_template' => 'emerald',
            'default_page_size' => 'letter',
            'timezone' => 'America/Panama',
        ]);

        $response2->assertSessionHasNoErrors();
        $this->assertEquals('es', $profile->fresh()->default_locale);
    }
}
