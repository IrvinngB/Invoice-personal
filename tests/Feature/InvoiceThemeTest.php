<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceTheme;
use App\Services\PdfService;
use App\Services\ThemeTokens;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_sanitize_drops_anything_outside_the_whitelist(): void
    {
        $clean = ThemeTokens::sanitize([
            'accent' => 'red; } body { background: url(http://evil.test)',
            'font' => 'Comic Sans',
            'header' => 'band',
            'table' => '<script>',
            'total' => 'line',
            'radius' => 'abc',
            'scale' => 'huge',
            'unknown' => 'x',
        ]);

        $this->assertSame(['header' => 'band', 'total' => 'line'], $clean);
    }

    public function test_sanitize_normalises_valid_values(): void
    {
        $clean = ThemeTokens::sanitize(['accent' => '#AABBCC', 'radius' => '8', 'uppercase_labels' => '0', 'scale' => 'large']);

        $this->assertSame(['accent' => '#aabbcc', 'radius' => 8, 'scale' => 'large', 'uppercase_labels' => false], $clean);
    }

    public function test_accent_override_derives_soft_tint_and_readable_ink(): void
    {
        $base = config('invoice_themes.presets.modern');

        $dark = ThemeTokens::apply($base, ['accent' => '#1f2a44']);
        $this->assertSame('#1f2a44', $dark['accent']);
        $this->assertSame('#ffffff', $dark['accent_ink']);
        $this->assertNotSame($base['accent_soft'], $dark['accent_soft']);

        $light = ThemeTokens::apply($base, ['accent' => '#fde047']);
        $this->assertSame('#111111', $light['accent_ink']);
    }

    public function test_scale_multiplies_every_font_size(): void
    {
        $base = config('invoice_themes.presets.modern');
        $large = ThemeTokens::apply($base, ['scale' => 'large']);

        $this->assertEqualsWithDelta($base['size']['base'] * 1.1, $large['size']['base'], 0.01);
    }

    public function test_custom_theme_resolves_on_top_of_its_base_preset(): void
    {
        $theme = InvoiceTheme::create(['name' => 'Brand', 'base' => 'classic', 'overrides' => ['accent' => '#ff0000', 'header' => 'band']]);
        $service = app(PdfService::class);

        $this->assertTrue($service->supportsTemplate($theme->key()));
        $tokens = $service->theme($theme->key());

        $this->assertSame('#ff0000', $tokens['accent']);
        $this->assertSame('band', $tokens['header']);
        $this->assertSame('Times', $tokens['font'], 'untouched tokens come from the base preset');
        $this->assertArrayHasKey($theme->key(), $service->availableTemplates());
    }

    public function test_unknown_custom_keys_are_not_supported(): void
    {
        $service = app(PdfService::class);

        $this->assertFalse($service->supportsTemplate('custom:999'));
        $this->assertFalse($service->supportsTemplate('custom:abc'));
        $this->assertFalse($service->supportsTemplate('custom:'));
    }

    public function test_custom_theme_renders_into_the_document_html(): void
    {
        $theme = InvoiceTheme::create(['name' => 'Brand', 'base' => 'modern', 'overrides' => ['accent' => '#ff0000']]);
        $invoice = $this->createInvoice(['pdf_template' => $theme->key()]);

        $html = app(PdfService::class)->html($invoice, BusinessProfile::current());

        $this->assertStringContainsString('#ff0000', $html);
    }

    public function test_store_persists_only_sanitised_overrides(): void
    {
        $this->post(route('settings.themes.store'), [
            'name' => 'Brand',
            'base' => 'emerald',
            'accent' => '#123456',
            'header' => 'centered',
            'radius' => '12',
        ])->assertRedirect();

        $theme = InvoiceTheme::firstOrFail();
        $this->assertSame('emerald', $theme->base);
        $this->assertSame(['accent' => '#123456', 'header' => 'centered', 'radius' => 12, 'uppercase_labels' => false], $theme->overrides);
    }

    public function test_store_rejects_malicious_or_invalid_input(): void
    {
        $this->post(route('settings.themes.store'), ['name' => 'X', 'base' => 'modern', 'accent' => 'red; } *{display:none}'])
            ->assertSessionHasErrors('accent');
        $this->post(route('settings.themes.store'), ['name' => 'X', 'base' => 'nope'])->assertSessionHasErrors('base');
        $this->post(route('settings.themes.store'), ['base' => 'modern'])->assertSessionHasErrors('name');

        $this->assertSame(0, InvoiceTheme::count());
    }

    public function test_update_cannot_change_the_base_preset(): void
    {
        $theme = InvoiceTheme::create(['name' => 'Brand', 'base' => 'modern', 'overrides' => []]);

        $this->put(route('settings.themes.update', $theme), ['name' => 'Renamed', 'base' => 'classic', 'header' => 'band'])->assertRedirect();

        $theme->refresh();
        $this->assertSame('Renamed', $theme->name);
        $this->assertSame('modern', $theme->base);
    }

    public function test_deleting_a_theme_returns_invoices_and_default_to_the_base_preset(): void
    {
        $theme = InvoiceTheme::create(['name' => 'Brand', 'base' => 'classic', 'overrides' => []]);
        $invoice = $this->createInvoice(['pdf_template' => $theme->key()]);
        BusinessProfile::current()->update(['default_template' => $theme->key()]);

        $this->delete(route('settings.themes.destroy', $theme))->assertRedirect(route('settings.themes.index'));

        $this->assertDatabaseMissing('invoice_themes', ['id' => $theme->id]);
        $this->assertSame('classic', $invoice->fresh()->pdf_template);
        $this->assertSame('classic', BusinessProfile::current()->default_template);
    }

    public function test_available_templates_list_existing_custom_themes_only(): void
    {
        $theme = InvoiceTheme::create(['name' => 'Brand', 'base' => 'modern', 'overrides' => []]);
        $keys = array_keys(app(PdfService::class)->availableTemplates());

        $this->assertContains($theme->key(), $keys);
        $this->assertNotContains('custom:999', $keys);
    }

    public function test_preview_streams_a_pdf_without_persisting_anything(): void
    {
        BusinessProfile::current();
        $invoices = Invoice::count();

        $response = $this->get(route('settings.themes.preview', ['base' => 'minimal', 'accent' => '#0066ff', 'locale' => 'es']));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertSame($invoices, Invoice::count());
        $this->assertSame(0, InvoiceTheme::count());
    }

    public function test_preview_ignores_garbage_tokens_instead_of_failing(): void
    {
        $this->get(route('settings.themes.preview', ['base' => 'bogus', 'accent' => 'javascript:alert(1)', 'font' => 'x']))->assertOk();
    }

    public function test_theme_pages_render(): void
    {
        $theme = InvoiceTheme::create(['name' => 'Brand', 'base' => 'modern', 'overrides' => ['accent' => '#ff0000']]);

        $this->get(route('settings.themes.index'))->assertOk()->assertSee('Brand');
        $this->get(route('settings.themes.create', ['base' => 'classic']))->assertOk();
        $this->get(route('settings.themes.edit', $theme))->assertOk()->assertSee('#ff0000');
    }

    public function test_show_overrides_keep_only_known_elements_as_booleans(): void
    {
        $clean = ThemeTokens::sanitize(['show' => ['seller_tax_id' => '0', 'phone_number' => '0', 'status' => 'garbage', 'logo' => '1', 'notes' => null]]);

        $this->assertEqualsCanonicalizing(['seller_tax_id' => false, 'logo' => true, 'status' => false], $clean['show']);
        $this->assertSame(['show'], array_keys($clean));
        $this->assertSame([], ThemeTokens::sanitize(['show' => 'nope']));
    }

    public function test_every_element_is_visible_unless_a_theme_hides_it(): void
    {
        $base = config('invoice_themes.presets.modern');
        $this->assertSame(ThemeTokens::showKeys(), array_keys($base['show']));
        $this->assertNotContains(false, $base['show']);

        $applied = ThemeTokens::apply($base, ['show' => ['seller_tax_id' => false]]);
        $this->assertFalse($applied['show']['seller_tax_id']);
        $this->assertTrue($applied['show']['seller_phone'], 'untouched elements stay visible');
    }

    public function test_a_theme_can_hide_the_tax_id_phone_and_status_on_the_rendered_document(): void
    {
        $profile = BusinessProfile::current();
        $profile->update(['tax_id' => 'SELLER-RUC-123', 'phone' => '+1 555 000 9999']);
        $client = Client::create(['name' => 'Ana', 'company' => 'Cliente SA', 'tax_id' => 'CLIENT-RUC-456', 'email' => 'ana@client.test']);
        $invoice = $this->createInvoice(['client_id' => $client->id, 'status' => 'sent']);

        $visible = InvoiceTheme::create(['name' => 'Full', 'base' => 'modern', 'overrides' => []]);
        $html = app(PdfService::class)->html($invoice->fresh(), $profile->fresh(), $visible->key());
        foreach (['SELLER-RUC-123', '+1 555 000 9999', 'CLIENT-RUC-456', 'ana@client.test', 'class="status-pill"'] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }

        $trimmed = InvoiceTheme::create(['name' => 'Lean', 'base' => 'modern', 'overrides' => ['show' => [
            'seller_tax_id' => false, 'seller_phone' => false, 'client_tax_id' => false, 'client_email' => false, 'status' => false,
        ]]]);
        $html = app(PdfService::class)->html($invoice->fresh(), $profile->fresh(), $trimmed->key());
        foreach (['SELLER-RUC-123', '+1 555 000 9999', 'CLIENT-RUC-456', 'ana@client.test', 'class="status-pill"'] as $needle) {
            $this->assertStringNotContainsString($needle, $html, $needle);
        }
        $this->assertStringContainsString('Cliente SA', $html, 'unrelated content is untouched');
    }

    public function test_hiding_notes_and_footer_removes_those_sections(): void
    {
        $profile = BusinessProfile::current();
        $invoice = $this->createInvoice(['notes' => 'UNIQUE-NOTE-TEXT']);
        $theme = InvoiceTheme::create(['name' => 'Bare', 'base' => 'classic', 'overrides' => ['show' => ['notes' => false, 'footer' => false, 'meta_due_date' => false, 'meta_currency' => false]]]);

        $html = app(PdfService::class)->html($invoice, $profile, $theme->key());

        $this->assertStringNotContainsString('UNIQUE-NOTE-TEXT', $html);
        $this->assertStringNotContainsString('page-footer w-full', $html);
        $this->assertStringNotContainsString('Fecha de Vencimiento', $html);
        $this->assertStringNotContainsString('>Moneda<', $html);
    }

    public function test_store_persists_unchecked_elements_posted_as_hidden_zeros(): void
    {
        $this->post(route('settings.themes.store'), [
            'name' => 'Lean', 'base' => 'modern',
            'show' => ['seller_tax_id' => '0', 'seller_phone' => '1', 'bogus_key' => '0'],
        ])->assertRedirect();

        $this->assertSame(
            ['seller_tax_id' => false, 'seller_phone' => true],
            InvoiceTheme::firstOrFail()->overrides['show']
        );
    }

    public function test_editor_and_preview_reflect_the_saved_visibility(): void
    {
        $theme = InvoiceTheme::create(['name' => 'Lean', 'base' => 'modern', 'overrides' => ['show' => ['seller_tax_id' => false]]]);
        BusinessProfile::current();

        $page = $this->get(route('settings.themes.edit', $theme))->assertOk();
        $page->assertSee('seller_tax_id\\u0022:false', false); // Js::from encodes quotes as \\u0022
        $page->assertSee('name="show[seller_phone]"', false);

        $this->get(route('settings.themes.preview', ['base' => 'modern', 'show' => ['seller_tax_id' => '0']]))->assertOk();
    }

    private function createInvoice(array $attributes = []): Invoice
    {
        BusinessProfile::current();
        $client = Client::create(['name' => 'Ana Cliente', 'company' => 'Cliente SA']);
        $invoice = Invoice::create(array_merge([
            'invoice_number' => 'INV-THEME-1',
            'type' => 'invoice',
            'status' => 'draft',
            'client_id' => $client->id,
            'issue_date' => '2026-10-18',
            'due_date' => '2026-11-01',
            'currency' => 'USD',
            'locale' => 'es',
            'pdf_template' => 'modern',
        ], $attributes));
        $invoice->items()->create([
            'description' => 'Servicio de prueba',
            'quantity' => 1,
            'unit' => 'hora',
            'unit_price_cents' => 123456,
            'total_cents' => 123456,
        ]);

        return $invoice->recalculateTotals()->refresh();
    }
}
