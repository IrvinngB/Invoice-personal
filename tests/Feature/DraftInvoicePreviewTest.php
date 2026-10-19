<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceTheme;
use App\Services\DraftInvoiceFactory;
use App\Services\PdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DraftInvoicePreviewTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'type' => 'invoice',
            'invoice_number' => 'INV-DRAFT-1',
            'issue_date' => '2026-10-18',
            'due_date' => '2026-11-01',
            'currency' => 'usd',
            'locale' => 'es',
            'pdf_template' => 'modern',
            'shipping_fee' => '5',
            'items' => [
                ['description' => 'Consulting', 'quantity' => '2', 'unit' => 'hours', 'unit_price' => '50', 'discount_percent' => '10', 'tax_rate_percent' => '7'],
            ],
        ], $overrides);
    }

    public function test_totals_are_calculated_with_the_same_rules_as_a_saved_invoice(): void
    {
        $invoice = app(DraftInvoiceFactory::class)->make($this->payload(), BusinessProfile::current());

        $this->assertSame(9000, $invoice->subtotal_cents);
        $this->assertSame(1000, $invoice->discount_total_cents);
        $this->assertSame(630, $invoice->tax_total_cents);
        $this->assertSame(500, $invoice->shipping_fee_cents);
        $this->assertSame(10130, $invoice->total_cents);
        $this->assertSame(10130, $invoice->balance_cents);
        $this->assertSame('USD', $invoice->currency);
        $this->assertCount(1, $invoice->items);
        $this->assertSame(9630, $invoice->items->first()->total_cents);
    }

    public function test_untouched_rows_are_not_lines_and_garbage_numbers_degrade_to_zero(): void
    {
        $invoice = app(DraftInvoiceFactory::class)->make($this->payload([
            'shipping_fee' => 'abc',
            'items' => [
                ['description' => '', 'quantity' => '1', 'unit' => 'hours', 'unit_price' => '0'],
                ['description' => 'Real', 'quantity' => 'x', 'unit' => 'u', 'unit_price' => 'nope', 'discount_percent' => '999'],
            ],
        ]), BusinessProfile::current());

        $this->assertCount(1, $invoice->items);
        $this->assertSame(0, $invoice->total_cents);
        $this->assertSame(0, $invoice->shipping_fee_cents);
    }

    public function test_missing_client_falls_back_to_a_visible_placeholder(): void
    {
        $es = app(DraftInvoiceFactory::class)->make($this->payload(['locale' => 'es']), BusinessProfile::current());
        $en = app(DraftInvoiceFactory::class)->make($this->payload(['locale' => 'en']), BusinessProfile::current());

        $this->assertSame('Selecciona un cliente', $es->client->name);
        $this->assertSame('Select a client', $en->client->name);
    }

    public function test_unparseable_dates_and_unknown_locale_use_profile_defaults(): void
    {
        $profile = BusinessProfile::current();
        $profile->update(['default_locale' => 'en', 'default_payment_terms_days' => 30]);

        $invoice = app(DraftInvoiceFactory::class)->make($this->payload(['issue_date' => 'not-a-date', 'due_date' => '', 'locale' => 'fr']), $profile->fresh());

        $this->assertSame('en', $invoice->locale);
        $this->assertSame(30, (int) $invoice->issue_date->diffInDays($invoice->due_date));
    }

    public function test_endpoint_streams_a_pdf_with_what_was_typed(): void
    {
        BusinessProfile::current();
        $client = Client::create(['name' => 'Ana Cliente', 'company' => 'Cliente SA']);

        $response = $this->post(route('invoices.preview-draft'), $this->payload(['client_id' => $client->id]));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());

        $invoice = app(DraftInvoiceFactory::class)->make($this->payload(), BusinessProfile::current(), $client);
        $html = app(PdfService::class)->html($invoice, BusinessProfile::current(), 'modern');
        $this->assertStringContainsString('Consulting', $html);
        $this->assertStringContainsString('INV-DRAFT-1', $html);
        $this->assertStringContainsString('Cliente SA', $html);
    }

    public function test_endpoint_renders_an_empty_form_without_failing(): void
    {
        BusinessProfile::current();

        $this->post(route('invoices.preview-draft'), [])->assertOk();
        $this->post(route('invoices.preview-draft'), ['client_id' => 'abc', 'items' => 'not-an-array', 'pdf_template' => 'bogus'])->assertOk();
    }

    public function test_endpoint_supports_custom_themes(): void
    {
        BusinessProfile::current();
        $theme = InvoiceTheme::create(['name' => 'Brand', 'base' => 'modern', 'overrides' => ['accent' => '#ff0000']]);

        $this->post(route('invoices.preview-draft'), $this->payload(['pdf_template' => $theme->key()]))->assertOk();
    }

    public function test_endpoint_is_strictly_read_only(): void
    {
        $profile = BusinessProfile::current();
        $next = $profile->next_invoice_number;
        $client = Client::create(['name' => 'Ana Cliente']);

        $this->post(route('invoices.preview-draft'), $this->payload(['client_id' => $client->id, 'invoice_number' => 'INV-9999']))->assertOk();

        $this->assertSame(0, Invoice::count());
        $this->assertSame(0, InvoiceItem::count());
        $this->assertSame($next, $profile->fresh()->next_invoice_number);
        $this->assertSame('INV-2026-', $profile->fresh()->invoice_prefix, 'a typed number must not move the numbering sequence');
    }

    public function test_endpoint_returns_404_when_the_business_profile_was_never_created(): void
    {
        $this->post(route('invoices.preview-draft'), $this->payload())->assertNotFound();
        $this->assertSame(0, BusinessProfile::count());
    }

    public function test_create_screen_exposes_the_preview_button_and_endpoint(): void
    {
        BusinessProfile::current();

        $this->get(route('invoices.create', ['type' => 'invoice']))
            ->assertOk()
            ->assertSee(route('invoices.preview-draft'), false)
            ->assertSee('togglePreview()', false);
    }
}
