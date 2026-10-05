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
}
