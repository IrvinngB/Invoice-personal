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

    public function test_labels_from_form_resolves_default_custom_and_blank(): void
    {
        $labels = ThemeTokens::labelsFromForm(
            ['tax_id' => 'RUC', 'billed_to' => '   ', 'bank' => 'ignored when blank', 'nope' => 'x'],
            ['bank' => '1', 'iban' => '0'],
        );

        $this->assertSame(['tax_id' => 'RUC', 'bank' => ''], $labels, 'whitespace and unflagged empties keep the default');
    }

    public function test_label_sanitising_whitelists_keys_strips_control_chars_and_caps_length(): void
    {
        $clean = ThemeTokens::sanitize(['labels' => [
            'tax_id' => "  RU\x00C\n ",
            'total' => str_repeat('x', 100),
            'notes' => '',
            'unknown_key' => 'x',
            'bank' => ['array'],
        ]]);

        $this->assertSame('RUC', $clean['labels']['tax_id']);
        $this->assertSame(ThemeTokens::LABEL_MAX_LENGTH, mb_strlen($clean['labels']['total']));
        $this->assertSame('', $clean['labels']['notes'], 'empty string is kept: it means no label');
        $this->assertArrayNotHasKey('unknown_key', $clean['labels']);
        $this->assertArrayNotHasKey('bank', $clean['labels']);
    }

    public function test_a_theme_can_rename_a_label_and_blank_another_while_keeping_the_value(): void
    {
        $profile = BusinessProfile::current();
        $profile->update(['tax_id' => 'SELLER-RUC-123']);
        $invoice = $this->createInvoice();

        $default = InvoiceTheme::create(['name' => 'Default', 'base' => 'modern', 'overrides' => []]);
        $html = app(PdfService::class)->html($invoice, $profile->fresh(), $default->key());
        $this->assertStringContainsString('NIF / RUC: SELLER-RUC-123', $html);

        $renamed = InvoiceTheme::create(['name' => 'Renamed', 'base' => 'modern', 'overrides' => ['labels' => ['tax_id' => 'RUC', 'invoice' => 'RECIBO']]]);
        $html = app(PdfService::class)->html($invoice, $profile->fresh(), $renamed->key());
        $this->assertStringContainsString('RUC: SELLER-RUC-123', $html);
        $this->assertStringNotContainsString('NIF / RUC', $html);
        $this->assertStringContainsString('RECIBO', $html);

        $blank = InvoiceTheme::create(['name' => 'Blank', 'base' => 'modern', 'overrides' => ['labels' => ['tax_id' => '', 'billed_to' => '']]]);
        $html = app(PdfService::class)->html($invoice, $profile->fresh(), $blank->key());
        $this->assertStringContainsString('SELLER-RUC-123', $html, 'the field is still printed');
        $this->assertStringNotContainsString('NIF / RUC', $html);
        $this->assertStringNotContainsString(': SELLER-RUC-123', $html, 'and without a dangling colon');
        $this->assertStringNotContainsString('Facturado a', $html);
        $this->assertStringNotContainsString('<div class="label"></div>', $html, 'no empty heading left behind');
    }

    public function test_custom_labels_are_html_escaped_in_the_document(): void
    {
        $theme = InvoiceTheme::create(['name' => 'Xss', 'base' => 'modern', 'overrides' => ['labels' => ['total' => '<script>alert(1)</script>']]]);
        $html = app(PdfService::class)->html($this->createInvoice(), BusinessProfile::current(), $theme->key());

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    public function test_store_persists_labels_from_the_two_form_inputs(): void
    {
        $this->post(route('settings.themes.store'), [
            'name' => 'Labels', 'base' => 'modern',
            'labels' => ['tax_id' => 'RUC', 'billed_to' => '', 'bank' => 'whatever'],
            'labels_blank' => ['bank' => '1'],
        ])->assertRedirect();

        $this->assertSame(['tax_id' => 'RUC', 'bank' => ''], InvoiceTheme::firstOrFail()->overrides['labels']);
    }

    public function test_store_rejects_labels_longer_than_the_limit(): void
    {
        $this->post(route('settings.themes.store'), [
            'name' => 'Long', 'base' => 'modern', 'labels' => ['tax_id' => str_repeat('x', 41)],
        ])->assertSessionHasErrors('labels.tax_id');
        $this->assertSame(0, InvoiceTheme::count());
    }

    public function test_editor_prefills_custom_and_blank_labels_and_preview_accepts_them(): void
    {
        BusinessProfile::current();
        $theme = InvoiceTheme::create(['name' => 'L', 'base' => 'modern', 'overrides' => ['labels' => ['tax_id' => 'RUC', 'bank' => '']]]);

        $page = $this->get(route('settings.themes.edit', $theme))->assertOk();
        $page->assertSee('tax_id\\u0022:\\u0022RUC', false);
        $page->assertSee('name="labels_blank[bank]"', false);
        $page->assertSee('settings#logo', false);

        $this->get(route('settings.themes.preview', ['base' => 'modern', 'labels' => ['tax_id' => 'RUC'], 'labels_blank' => ['bank' => '1']]))->assertOk();
        $this->get(route('settings.themes.preview', ['base' => 'modern', 'labels' => 'garbage', 'labels_blank' => 'garbage']))->assertOk();
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
