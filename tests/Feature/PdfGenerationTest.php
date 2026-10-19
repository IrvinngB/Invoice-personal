<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\Client;
use App\Models\Invoice;
use App\Services\PdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_service_generates_valid_pdf_for_all_templates_and_languages(): void
    {
        $profile = BusinessProfile::current();
        $client = Client::create([
            'name' => 'John Doe',
            'company' => 'Stark Industries',
            'tax_id' => 'US-998877',
            'billing_address' => '10880 Malibu Point, Malibu, CA',
            'email' => 'tony@stark.test',
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-2026-0001',
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'currency' => 'USD',
            'payment_link' => 'https://example.com/pay/inv1',
        ]);

        $invoice->items()->create([
            'description' => 'Reactor Maintenance & Consulting',
            'quantity' => 5,
            'unit' => 'hours',
            'unit_price_cents' => 20000,
            'tax_rate_bps' => 1000,
            'tax_name' => 'Standard Tax',
        ]);

        $invoice->recalculateTotals();

        $pdfService = new PdfService();

        // 1. Test Modern template with A4 and English
        $invoice->update(['pdf_template' => 'modern', 'page_size' => 'a4', 'locale' => 'en']);
        $pdfOutput = $pdfService->generate($invoice)->output();
        $this->assertNotEmpty($pdfOutput);
        $this->assertStringStartsWith('%PDF', $pdfOutput);

        // 2. Test Classic template with Letter and Spanish
        $invoice->update(['pdf_template' => 'classic', 'page_size' => 'letter', 'locale' => 'es']);
        $pdfOutput = $pdfService->generate($invoice)->output();
        $this->assertNotEmpty($pdfOutput);
        $this->assertStringStartsWith('%PDF', $pdfOutput);

        // 3. Test Minimal template
        $invoice->update(['pdf_template' => 'minimal', 'page_size' => 'a4', 'locale' => 'en']);
        $pdfOutput = $pdfService->generate($invoice)->output();
        $this->assertNotEmpty($pdfOutput);
        $this->assertStringStartsWith('%PDF', $pdfOutput);
    }

    public function test_all_pdf_layouts_use_the_persisted_invoice_locale_despite_the_app_locale(): void
    {
        app()->setLocale('en');
        $profile = BusinessProfile::current();
        $client = Client::create(['name' => 'Ana Cliente']);
        $invoice = Invoice::create([
            'invoice_number' => 'INV-ES-1',
            'type' => 'invoice',
            'status' => 'draft',
            'client_id' => $client->id,
            'issue_date' => '2026-10-18',
            'due_date' => '2026-11-01',
            'currency' => 'USD',
            'locale' => 'es',
        ]);
        $item = $invoice->items()->create([
            'description' => 'Servicio',
            'quantity' => 1,
            'unit' => 'hora',
            'unit_price_cents' => 123456,
            'total_cents' => 123456,
        ]);
        $invoice->recalculateTotals()->refresh();

        foreach (['classic', 'emerald', 'minimal', 'modern'] as $template) {
            $html = view("invoices.pdf.{$template}", [
                'invoice' => $invoice,
                'profile' => $profile,
                'client' => $client,
                'items' => $invoice->items,
                'payments' => $invoice->payments,
                'qrCodeSvg' => null,
                'locale' => $invoice->locale,
            ])->render();

            $this->assertStringContainsString('FACTURA', $html, $template);
            $this->assertStringContainsString('18 de octubre de 2026', $html, $template);
            $this->assertStringContainsString('$1.234,56', $html, $template);
        }
    }

    public function test_every_pdf_layout_renders_english_document_text_dates_and_money(): void
    {
        app()->setLocale('es');
        $profile = BusinessProfile::current();
        $client = Client::create(['name' => 'English Client']);
        $invoice = Invoice::create([
            'invoice_number' => 'INV-EN-1',
            'type' => 'invoice',
            'status' => 'draft',
            'client_id' => $client->id,
            'issue_date' => '2026-10-18',
            'due_date' => '2026-11-01',
            'currency' => 'USD',
            'locale' => 'en',
        ]);
        $invoice->items()->create([
            'description' => 'English service',
            'quantity' => 1,
            'unit' => 'hour',
            'unit_price_cents' => 123456,
            'total_cents' => 123456,
        ]);
        $invoice->recalculateTotals()->refresh();

        foreach (PdfService::SUPPORTED_TEMPLATES as $template) {
            $html = $this->renderPdf($template, $invoice, $profile, $client);

            $this->assertStringContainsString('INVOICE', $html, $template);
            $this->assertStringContainsString('October 18, 2026', $html, $template);
            $this->assertStringContainsString('$1,234.56', $html, $template);
        }
    }

    private function renderPdf(string $template, Invoice $invoice, BusinessProfile $profile, Client $client): string
    {
        return view("invoices.pdf.{$template}", [
            'invoice' => $invoice,
            'profile' => $profile,
            'client' => $client,
            'items' => $invoice->items,
            'payments' => $invoice->payments,
            'qrCodeSvg' => null,
            'locale' => $invoice->locale,
        ])->render();
    }
}
