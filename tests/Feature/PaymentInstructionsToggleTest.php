<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\Client;
use App\Models\Invoice;
use App\Services\PdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentInstructionsToggleTest extends TestCase
{
    use RefreshDatabase;

    private const LABEL = 'Instrucciones de Pago';

    private function profile(bool $showByDefault = true): BusinessProfile
    {
        $profile = BusinessProfile::current();
        $profile->update([
            'bank_name' => 'Example Bank',
            'bank_iban' => '0000-0000-0000-0000',
            'payment_link' => 'https://pay.example.com/abc',
            'show_payment_instructions' => $showByDefault,
        ]);

        return $profile->fresh();
    }

    private function invoice(array $attributes = []): Invoice
    {
        $client = Client::create(['name' => 'Ana Cliente']);
        $invoice = Invoice::create(array_merge([
            'invoice_number' => 'INV-PAY-1',
            'type' => 'invoice',
            'status' => 'draft',
            'client_id' => $client->id,
            'issue_date' => '2026-10-18',
            'due_date' => '2026-11-01',
            'currency' => 'USD',
            'locale' => 'es',
            'pdf_template' => 'modern',
        ], $attributes));
        $invoice->items()->create(['description' => 'Servicio', 'quantity' => 1, 'unit' => 'h', 'unit_price_cents' => 1000, 'total_cents' => 1000]);

        return $invoice->recalculateTotals()->refresh();
    }

    private function html(Invoice $invoice, BusinessProfile $profile): string
    {
        return app(PdfService::class)->html($invoice, $profile, 'modern');
    }

    public function test_block_is_shown_by_default_when_the_profile_has_payment_data(): void
    {
        $html = $this->html($this->invoice(), $this->profile());

        $this->assertStringContainsString(self::LABEL, $html);
        $this->assertStringContainsString('Example Bank', $html);
    }

    public function test_invoice_can_hide_the_block_including_the_qr_even_when_the_profile_shows_it(): void
    {
        $html = $this->html($this->invoice(['show_payment_instructions' => false]), $this->profile(true));

        $this->assertStringNotContainsString(self::LABEL, $html);
        $this->assertStringNotContainsString('Example Bank', $html);
        $this->assertStringNotContainsString('class="qr"', $html);
    }

    public function test_null_follows_the_profile_default_in_both_directions(): void
    {
        $invoice = $this->invoice();

        $this->assertStringNotContainsString(self::LABEL, $this->html($invoice, $this->profile(false)));
        $this->assertStringContainsString(self::LABEL, $this->html($invoice, $this->profile(true)));
    }

    public function test_invoice_can_force_the_block_on_when_the_profile_hides_it(): void
    {
        $html = $this->html($this->invoice(['show_payment_instructions' => true]), $this->profile(false));

        $this->assertStringContainsString(self::LABEL, $html);
    }

    public function test_unchecked_checkbox_is_saved_as_an_explicit_off_not_as_inherit(): void
    {
        $profile = $this->profile(true);
        $client = Client::create(['name' => 'Ana Cliente']);
        $payload = [
            'client_id' => $client->id, 'type' => 'invoice', 'issue_date' => '2026-10-18', 'due_date' => '2026-11-01',
            'currency' => 'USD', 'pdf_template' => 'modern', 'page_size' => 'a4', 'locale' => 'es',
            'items' => [['description' => 'Servicio', 'quantity' => 1, 'unit' => 'h', 'unit_price' => 10]],
            // what the browser sends when the box is unchecked: only the hidden "0"
            'show_payment_instructions' => '0',
        ];

        $this->post(route('invoices.store'), $payload)->assertRedirect();
        $invoice = Invoice::firstOrFail();

        $this->assertFalse($invoice->show_payment_instructions);
        $this->assertStringNotContainsString(self::LABEL, $this->html($invoice, $profile));

        $this->put(route('invoices.update', $invoice), array_merge($payload, ['show_payment_instructions' => '1']))->assertRedirect();
        $this->assertTrue($invoice->fresh()->show_payment_instructions);
    }

    public function test_settings_persist_the_default(): void
    {
        $profile = $this->profile(true);
        $base = [
            'business_name' => 'Acme', 'default_currency' => 'USD', 'default_payment_terms_days' => 14,
            'invoice_number_digits' => 4, 'next_invoice_number' => 1, 'quote_number_digits' => 4, 'next_quote_number' => 1,
            'credit_note_number_digits' => 4, 'next_credit_note_number' => 1, 'default_template' => 'modern',
            'default_page_size' => 'a4', 'timezone' => 'UTC',
        ];

        $this->put(route('settings.update'), $base)->assertSessionHasNoErrors();
        $this->assertFalse($profile->fresh()->show_payment_instructions, 'an unchecked box means off');

        $this->put(route('settings.update'), $base + ['show_payment_instructions' => '1'])->assertSessionHasNoErrors();
        $this->assertTrue($profile->fresh()->show_payment_instructions);
    }

    public function test_draft_preview_honours_the_toggle(): void
    {
        $profile = $this->profile(true);
        $factory = app(\App\Services\DraftInvoiceFactory::class);

        $off = $factory->make(['show_payment_instructions' => '0', 'locale' => 'es'], $profile);
        $inherit = $factory->make(['locale' => 'es'], $profile);

        $this->assertStringNotContainsString(self::LABEL, $this->html($off, $profile));
        $this->assertStringContainsString(self::LABEL, $this->html($inherit, $profile));
    }
}
