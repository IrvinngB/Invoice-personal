<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_spanish_session_localizes_the_shared_application_shell(): void
    {
        $response = $this->withSession(['locale' => 'es'])->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Tablero');
        $response->assertSee('Facturas y Presupuestos');
        $response->assertSee('Alternar tema de color', false);
    }

    public function test_login_feedback_uses_the_active_session_locale(): void
    {
        config(['invoice.local_mode' => false, 'invoice.app_password' => 'correct-password']);

        $this->withSession(['locale' => 'es'])
            ->from(route('login'))
            ->post(route('login.submit'), [])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['password' => 'El campo contraseña es obligatorio.']);

        $this->withSession(['locale' => 'es'])
            ->post(route('login.submit'), ['password' => 'incorrect-password'])
            ->assertRedirect()
            ->assertSessionHasErrors(['password' => 'La contraseña es incorrecta.']);

        $this->withSession(['locale' => 'es'])
            ->post(route('login.submit'), ['password' => 'correct-password'])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success', '¡Bienvenido de nuevo!');
    }

    public function test_english_accessibility_text_and_business_data_remain_unchanged_after_locale_switch(): void
    {
        $client = Client::create([
            'name' => 'Cliente con nombre propio',
            'currency' => 'USD',
        ]);

        $this->withSession(['locale' => 'en'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Toggle color theme', false);

        $this->withSession(['locale' => 'es'])
            ->get(route('locale.switch', 'en'))
            ->assertRedirect();

        $this->assertSame('Cliente con nombre propio', $client->fresh()->name);
    }

    public function test_management_screens_use_the_active_session_locale(): void
    {
        $this->withSession(['locale' => 'es'])
            ->get(route('catalog.index'))
            ->assertOk()
            ->assertSee('Catálogo de servicios y productos');

        $this->withSession(['locale' => 'es'])
            ->get(route('clients.create'))
            ->assertOk()
            ->assertSee('Nuevo cliente');

        $this->withSession(['locale' => 'es'])
            ->get(route('recurring.index'))
            ->assertOk()
            ->assertSee('Facturación recurrente');

        $this->withSession(['locale' => 'es'])
            ->get(route('recurring.create'))
            ->assertOk()
            ->assertSee('Seleccionar cliente', false)
            ->assertSee('Semanal', false);

        $this->withSession(['locale' => 'es'])
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('Configuración y preferencias del estudio')
            ->assertSee('Nombre completo del propietario o consultor', false)
            ->assertSee('Copias de seguridad y exportaciones de datos', false);
    }

    public function test_catalog_validation_and_feedback_use_the_active_session_locale(): void
    {
        $this->withSession(['locale' => 'es'])
            ->from(route('catalog.create'))
            ->post(route('catalog.store'), [])
            ->assertRedirect(route('catalog.create'))
            ->assertSessionHasErrors(['name' => 'El campo nombre es obligatorio.']);

        $this->withSession(['locale' => 'en'])
            ->post(route('catalog.store'), [
                'name' => 'Translation test service',
                'unit' => 'hour',
                'unit_price' => '12.00',
            ])
            ->assertRedirect(route('catalog.index'))
            ->assertSessionHas('success', 'Catalog item added.');
    }

    public function test_invoice_browser_and_feedback_use_the_active_session_locale(): void
    {
        $client = Client::create(['name' => 'Cliente de factura', 'currency' => 'USD']);
        $invoice = Invoice::create([
            'invoice_number' => 'FAC-001',
            'type' => 'invoice',
            'status' => 'draft',
            'client_id' => $client->id,
            'issue_date' => '2026-10-18',
            'due_date' => '2026-11-01',
            'currency' => 'USD',
            'pdf_template' => 'modern',
            'page_size' => 'letter',
            'locale' => 'en',
        ]);

        $this->withSession(['locale' => 'es'])
            ->get(route('invoices.index'))
            ->assertOk()
            ->assertSee('Facturas, presupuestos y notas de crédito')
            ->assertSee('Buscar por número o nombre del cliente...', false)
            ->assertSee('Acciones');

        $this->withSession(['locale' => 'es'])
            ->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Vista previa', false)
            ->assertSee('Registrar Cobro', false)
            ->assertSee('Borrador');

        $this->withSession(['locale' => 'es'])
            ->post(route('invoices.payments.store', $invoice), [
                'amount' => 10,
                'payment_date' => '2026-10-18',
                'method' => 'cash',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Cobro registrado correctamente.');
    }

    public function test_invoice_audit_event_labels_use_the_active_session_locale_without_translating_the_stored_event(): void
    {
        $client = Client::create(['name' => 'Cliente de auditoría', 'currency' => 'USD']);
        $invoice = Invoice::create([
            'invoice_number' => 'FAC-AUDIT-001',
            'type' => 'invoice',
            'status' => 'draft',
            'client_id' => $client->id,
            'issue_date' => '2026-10-18',
            'due_date' => '2026-11-01',
            'currency' => 'USD',
            'pdf_template' => 'modern',
            'page_size' => 'letter',
            'locale' => 'en',
        ]);
        $auditLog = AuditLog::log($invoice->id, 'pdf_downloaded');
        AuditLog::log($invoice->id, 'manual_reviewed');

        $this->withSession(['locale' => 'es'])
            ->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertSee('PDF descargado', false)
            ->assertDontSee('Pdf downloaded', false)
            ->assertSee('Manual reviewed', false);

        $this->assertSame('pdf_downloaded', $auditLog->fresh()->event);
    }

    public function test_spanish_session_localizes_dashboard_and_invoice_form_workflows(): void
    {
        $client = Client::create(['name' => 'Cliente de formulario', 'currency' => 'USD']);
        $parentInvoice = Invoice::create([
            'invoice_number' => 'FAC-PARENT-001',
            'type' => 'invoice',
            'status' => 'draft',
            'client_id' => $client->id,
            'issue_date' => '2026-10-18',
            'due_date' => '2026-11-01',
            'currency' => 'USD',
            'pdf_template' => 'modern',
            'page_size' => 'letter',
            'locale' => 'es',
        ]);
        $invoice = Invoice::create([
            'invoice_number' => 'FAC-FORM-001',
            'type' => 'invoice',
            'status' => 'draft',
            'client_id' => $client->id,
            'parent_invoice_id' => $parentInvoice->id,
            'issue_date' => '2026-10-18',
            'due_date' => '2026-11-01',
            'currency' => 'USD',
            'pdf_template' => 'modern',
            'page_size' => 'letter',
            'locale' => 'es',
        ]);

        $this->withSession(['locale' => 'es'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Facturas')
            ->assertSee('Vence:')
            ->assertSee('Ver PDF', false);

        $this->withSession(['locale' => 'es'])
            ->get(route('invoices.create'))
            ->assertOk()
            ->assertSee('Arrastrá los artículos para reordenarlos', false)
            ->assertSee('Resumen financiero', false)
            ->assertSee('Precios con impuestos incluidos', false);

        $this->withSession(['locale' => 'es'])
            ->get(route('invoices.edit', $invoice))
            ->assertOk()
            ->assertSee('Arrastrá los artículos para reordenarlos', false)
            ->assertSee('Notas del cliente', false)
            ->assertSee('Resumen financiero', false);

        $this->withSession(['locale' => 'es'])
            ->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Ref. doc.:', false)
            ->assertSee('Referencia / ID de transacción', false)
            ->assertSee('Transferencia bancaria', false)
            ->assertSee('La factura PDF se generará automáticamente', false);
    }
}
