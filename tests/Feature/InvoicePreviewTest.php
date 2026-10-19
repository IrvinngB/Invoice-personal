<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BusinessProfile;
use App\Services\PdfService;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvoicePreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_streams_the_selected_layout_as_an_inline_pdf_using_the_invoice_locale(): void
    {
        app()->setLocale('en');
        $invoice = $this->createInvoice(['pdf_template' => 'classic', 'locale' => 'es']);

        $response = $this->get(route('invoices.preview', $invoice));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString('inline', (string) $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_preview_rejects_missing_and_unsupported_layouts_without_side_effects(): void
    {
        $invoice = $this->createInvoice(['pdf_template' => 'unsupported']);
        $before = $this->snapshot($invoice);

        $this->get(route('invoices.preview', $invoice))->assertNotFound();
        $this->get('/invoices/999999/preview')->assertNotFound();

        $this->assertSame($before, $this->snapshot($invoice));
    }

    public function test_preview_does_not_mutate_invoice_related_records_or_send_mail(): void
    {
        Mail::fake();
        $invoice = $this->createInvoice(['pdf_template' => 'emerald', 'locale' => 'es']);
        Payment::create([
            'invoice_id' => $invoice->id,
            'amount_cents' => 1000,
            'payment_date' => '2026-10-18',
            'method' => 'cash',
        ]);
        AuditLog::log($invoice->id, 'pdf_downloaded', ['source' => 'existing-download'], '127.0.0.1');
        $before = $this->snapshot($invoice);

        $this->get(route('invoices.preview', $invoice))->assertOk();

        $this->assertSame($before, $this->snapshot($invoice));
        Mail::assertNothingSent();
    }

    public function test_unsaved_create_or_edit_state_has_no_preview_document(): void
    {
        $invoice = $this->createInvoice();
        $invoice->items->first()->description = 'UNSAVED PREVIEW CONTENT';

        $this->get('/invoices/create/preview')->assertNotFound();
        $this->get(route('invoices.preview', $invoice))->assertOk();

        $this->assertDatabaseMissing('invoice_items', [
            'invoice_id' => $invoice->id,
            'description' => 'UNSAVED PREVIEW CONTENT',
        ]);
    }

    public function test_preview_uses_spanish_document_content_and_the_selected_layout(): void
    {
        app()->setLocale('en');
        $invoice = $this->createInvoice(['locale' => 'es']);
        $profile = BusinessProfile::current();
        $layoutMarkers = [
            'classic' => "font-family: 'Times'",
            'emerald' => '#1f7a4d',
            'minimal' => '#111111',
            'modern' => '#2563eb',
        ];

        foreach ($layoutMarkers as $template => $marker) {
            $invoice->update(['pdf_template' => $template]);
            $freshInvoice = $invoice->fresh();

            $response = $this->get(route('invoices.preview', $freshInvoice));
            $response->assertOk();
            $this->assertStringContainsString('inline', (string) $response->headers->get('content-disposition'), $template);

            $html = app(PdfService::class)->html($freshInvoice, BusinessProfile::current(), $template);

            $this->assertStringContainsString('FACTURA', $html, $template);
            $this->assertStringContainsString('18 de octubre de 2026', $html, $template);
            $this->assertStringContainsString('$1.234,56', $html, $template);
            $this->assertStringContainsString('INV-PREVIEW-1', $html, $template);
            $this->assertStringContainsString('Servicio de prueba', $html, $template);
            $this->assertStringContainsString($marker, $html, $template);
        }
    }

    public function test_preview_supports_template_query_parameter_for_all_styles(): void
    {
        $invoice = $this->createInvoice(['pdf_template' => 'modern', 'locale' => 'es']);

        foreach (['emerald', 'classic', 'minimal', 'modern'] as $style) {
            $response = $this->get(route('invoices.preview', [$invoice, 'template' => $style]));
            $response->assertOk();
            $this->assertSame('application/pdf', $response->headers->get('content-type'));
        }
    }

    public function test_signature_is_omitted_by_default_and_renders_when_enabled(): void
    {
        $invoice = $this->createInvoice(['pdf_template' => 'emerald', 'locale' => 'es']);

        // Default: signature is omitted
        $defaultHtml = app(PdfService::class)->html($invoice, BusinessProfile::current(), 'emerald');

        $this->assertStringNotContainsString('class="signature-img"', $defaultHtml);

        // When show_signature is enabled on the invoice
        $invoice->update(['show_signature' => true]);
        $enabledHtml = app(PdfService::class)->html($invoice->fresh(), BusinessProfile::current(), 'emerald');

        $this->assertStringContainsString('class="signature-img"', $enabledHtml);
    }

    private function createInvoice(array $attributes = []): Invoice
    {
        BusinessProfile::current();
        $client = Client::create(['name' => 'Ana Cliente', 'company' => 'Cliente SA']);
        $invoice = Invoice::create(array_merge([
            'invoice_number' => 'INV-PREVIEW-1',
            'type' => 'invoice',
            'status' => 'draft',
            'client_id' => $client->id,
            'issue_date' => '2026-10-18',
            'due_date' => '2026-11-01',
            'currency' => 'USD',
            'pdf_template' => 'modern',
            'page_size' => 'a4',
            'locale' => 'en',
        ], $attributes));
        $invoice->items()->create([
            'description' => 'Servicio de prueba',
            'quantity' => 1,
            'unit' => 'hora',
            'unit_price_cents' => 123456,
            'total_cents' => 123456,
        ]);
        $invoice->recalculateTotals();

        return $invoice->fresh();
    }

    private function snapshot(Invoice $invoice): array
    {
        return [
            'invoice' => $invoice->fresh()->getAttributes(),
            'payments' => Payment::where('invoice_id', $invoice->id)->get()->map->getAttributes()->all(),
            'audit_logs' => AuditLog::where('invoice_id', $invoice->id)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get()
                ->map->getAttributes()
                ->all(),
        ];
    }
}
