<?php

namespace Database\Seeders;

use App\Models\BusinessProfile;
use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\RecurringInvoice;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Business Profile
        $profile = BusinessProfile::current();
        $profile->update([
            'business_name' => 'Apex Architecture & Code LLC',
            'owner_name' => 'Irvinng Developer',
            'tax_id' => 'US-882910492',
            'email' => 'irvinng@example.com',
            'phone' => '+1 (555) 749-2810',
            'website' => 'https://apexcode.dev',
            'address' => "450 Mission Street, Suite 1200\nSan Francisco, CA 94105\nUnited States",
            'default_currency' => 'USD',
            'default_payment_terms_days' => 14,
            'default_notes' => "Payment is requested within 14 days of invoice date.\nPlease include the invoice number on your wire transfer reference.",
            'default_footer' => "Apex Architecture & Code LLC · Bank Wire: Silicon Valley Bank · IBAN: US89SVBK00001234567890 · SWIFT: SVBKUS6S",
            'bank_name' => 'Silicon Valley Bank',
            'bank_account_name' => 'Apex Architecture & Code LLC',
            'bank_iban' => 'US89SVBK00001234567890',
            'bank_swift' => 'SVBKUS6S',
            'paypal_email' => 'billing@apexcode.dev',
            'payment_link' => 'https://buy.stripe.com/demo_apex_payment',
            'invoice_prefix' => 'INV-2026-',
            'invoice_number_digits' => 4,
            'next_invoice_number' => 6,
            'quote_prefix' => 'EST-2026-',
            'quote_number_digits' => 4,
            'next_quote_number' => 2,
            'credit_note_prefix' => 'CN-2026-',
            'credit_note_number_digits' => 4,
            'next_credit_note_number' => 2,
            'default_template' => 'modern',
            'default_page_size' => 'a4',
            'default_locale' => 'en',
            'timezone' => 'America/Los_Angeles',
        ]);

        // 2. Catalog Items
        $devItem = CatalogItem::create([
            'name' => 'Full-Stack Software Engineering',
            'description' => 'Senior full-stack web application development and testing',
            'unit' => 'hours',
            'unit_price_cents' => 12500, // $125.00
            'tax_rate_bps' => 0,
            'is_active' => true,
        ]);

        $archItem = CatalogItem::create([
            'name' => 'Software Architecture & RFC Review',
            'description' => 'System design review, hexagonal architecture guidance, and performance audit',
            'unit' => 'hours',
            'unit_price_cents' => 20000, // $200.00
            'tax_rate_bps' => 0,
            'is_active' => true,
        ]);

        $cloudItem = CatalogItem::create([
            'name' => 'DevOps & VPS Deployment Setup',
            'description' => 'Automated deployment pipeline, SSL, database replication, and monitoring setup',
            'unit' => 'service',
            'unit_price_cents' => 150000, // $1,500.00
            'tax_rate_bps' => 0,
            'is_active' => true,
        ]);

        $retainerItem = CatalogItem::create([
            'name' => 'Monthly Engineering Retainer',
            'description' => 'Dedicated priority support, 10 hours monthly engineering and maintenance',
            'unit' => 'month',
            'unit_price_cents' => 110000, // $1,100.00
            'tax_rate_bps' => 0,
            'is_active' => true,
        ]);

        // 3. Clients
        $clientAcme = Client::create([
            'name' => 'Sarah Connor',
            'company' => 'Acme Corporation',
            'tax_id' => 'ACM-992144',
            'email' => 'sarah@acme.test',
            'phone' => '+1 (555) 234-5678',
            'billing_address' => "100 Industrial Parkway\nBuilding 4\nAustin, TX 78701",
            'currency' => 'USD',
            'default_tax_rate_bps' => 0,
            'default_payment_terms_days' => 14,
            'locale' => 'en',
        ]);

        $clientWayne = Client::create([
            'name' => 'Lucius Fox',
            'company' => 'Wayne Enterprises',
            'tax_id' => 'WAY-112233',
            'email' => 'lfox@wayne-enterprises.test',
            'phone' => '+1 (555) 902-1200',
            'billing_address' => "Wayne Tower, 38th Floor\nFinancial District\nGotham, NY 10001",
            'currency' => 'USD',
            'default_tax_rate_bps' => 0,
            'default_payment_terms_days' => 30,
            'locale' => 'en',
        ]);

        $clientGlobex = Client::create([
            'name' => 'Hank Scorpio',
            'company' => 'Globex Technologies SL',
            'tax_id' => 'ES-B84920194',
            'email' => 'hank@globex.test',
            'phone' => '+34 91 555 4321',
            'billing_address' => "Paseo de la Castellana 95\nPlanta 14\n28046 Madrid, España",
            'currency' => 'EUR',
            'default_tax_rate_bps' => 2100, // 21% IVA
            'default_payment_terms_days' => 14,
            'locale' => 'es',
        ]);

        $clientCyber = Client::create([
            'name' => 'Miles Dyson',
            'company' => 'Cyberdyne Systems',
            'tax_id' => 'CYB-492018',
            'email' => 'mdyson@cyberdyne.test',
            'phone' => '+1 (555) 789-0123',
            'billing_address' => "18144 El Camino Real\nSunnyvale, CA 94086",
            'currency' => 'USD',
            'default_tax_rate_bps' => 825, // 8.25%
            'default_payment_terms_days' => 14,
            'locale' => 'en',
        ]);

        // 4. Invoices across all statuses

        // Invoice 1: PAID (Acme Corp)
        $inv1 = Invoice::create([
            'invoice_number' => 'INV-2026-0001',
            'type' => 'invoice',
            'status' => 'draft',
            'client_id' => $clientAcme->id,
            'issue_date' => Carbon::now()->subDays(25)->toDateString(),
            'due_date' => Carbon::now()->subDays(11)->toDateString(),
            'currency' => 'USD',
            'pdf_template' => 'modern',
            'page_size' => 'a4',
            'locale' => 'en',
            'finalized_at' => Carbon::now()->subDays(25),
            'sent_at' => Carbon::now()->subDays(25),
        ]);
        $inv1->items()->create([
            'catalog_item_id' => $devItem->id,
            'description' => 'Senior Full-Stack Development - Sprint 1 Features',
            'quantity' => 20,
            'unit' => 'hours',
            'unit_price_cents' => 12500,
            'discount_bps' => 0,
            'tax_rate_bps' => 0,
        ]);
        $inv1->items()->create([
            'catalog_item_id' => $archItem->id,
            'description' => 'Architecture Review & Code Audit',
            'quantity' => 4,
            'unit' => 'hours',
            'unit_price_cents' => 20000,
            'discount_bps' => 500, // 5% discount
            'tax_rate_bps' => 0,
        ]);
        $inv1->recalculateTotals();
        $inv1->recordPayment($inv1->total_cents, Carbon::now()->subDays(15)->toDateString(), 'bank_transfer', 'WIRE-ACH-89102');

        // Invoice 2: PARTIALLY PAID (Cyberdyne Systems)
        $inv2 = Invoice::create([
            'invoice_number' => 'INV-2026-0002',
            'type' => 'invoice',
            'status' => 'draft',
            'client_id' => $clientCyber->id,
            'issue_date' => Carbon::now()->subDays(10)->toDateString(),
            'due_date' => Carbon::now()->addDays(4)->toDateString(),
            'currency' => 'USD',
            'pdf_template' => 'modern',
            'page_size' => 'a4',
            'locale' => 'en',
            'finalized_at' => Carbon::now()->subDays(10),
            'sent_at' => Carbon::now()->subDays(10),
        ]);
        $inv2->items()->create([
            'catalog_item_id' => $cloudItem->id,
            'description' => 'DevOps & High-Availability VPS Infrastructure Deployment',
            'quantity' => 1,
            'unit' => 'service',
            'unit_price_cents' => 150000,
            'discount_bps' => 0,
            'tax_rate_bps' => 825,
            'tax_name' => 'Sales Tax 8.25%',
        ]);
        $inv2->items()->create([
            'catalog_item_id' => $devItem->id,
            'description' => 'Containerization & Docker Orchestration',
            'quantity' => 12,
            'unit' => 'hours',
            'unit_price_cents' => 12500,
            'discount_bps' => 0,
            'tax_rate_bps' => 825,
            'tax_name' => 'Sales Tax 8.25%',
        ]);
        $inv2->recalculateTotals();
        $inv2->recordPayment(150000, Carbon::now()->subDays(3)->toDateString(), 'stripe_link', 'pi_3Pj9902Xk');

        // Invoice 3: SENT / PENDING (Wayne Enterprises)
        $inv3 = Invoice::create([
            'invoice_number' => 'INV-2026-0003',
            'type' => 'invoice',
            'status' => 'draft',
            'client_id' => $clientWayne->id,
            'issue_date' => Carbon::now()->subDays(5)->toDateString(),
            'due_date' => Carbon::now()->addDays(25)->toDateString(),
            'currency' => 'USD',
            'pdf_template' => 'classic',
            'page_size' => 'letter',
            'locale' => 'en',
            'finalized_at' => Carbon::now()->subDays(5),
            'sent_at' => Carbon::now()->subDays(5),
        ]);
        $inv3->items()->create([
            'catalog_item_id' => $devItem->id,
            'description' => 'Cryptographic Subsystem & Hardware Interface API',
            'quantity' => 25,
            'unit' => 'hours',
            'unit_price_cents' => 12500,
            'discount_bps' => 0,
            'tax_rate_bps' => 0,
        ]);
        $inv3->recalculateTotals();

        // Invoice 4: OVERDUE (Globex SL in EUR)
        $inv4 = Invoice::create([
            'invoice_number' => 'INV-2026-0004',
            'type' => 'invoice',
            'status' => 'draft',
            'client_id' => $clientGlobex->id,
            'issue_date' => Carbon::now()->subDays(28)->toDateString(),
            'due_date' => Carbon::now()->subDays(14)->toDateString(),
            'currency' => 'EUR',
            'exchange_rate' => 1.080000,
            'pdf_template' => 'modern',
            'page_size' => 'a4',
            'locale' => 'es',
            'finalized_at' => Carbon::now()->subDays(28),
            'sent_at' => Carbon::now()->subDays(28),
        ]);
        $inv4->items()->create([
            'catalog_item_id' => $retainerItem->id,
            'description' => 'Servicio Mensual de Mantenimiento y Soporte de Servidores',
            'quantity' => 1,
            'unit' => 'mes',
            'unit_price_cents' => 110000,
            'discount_bps' => 0,
            'tax_rate_bps' => 2100,
            'tax_name' => 'IVA 21%',
        ]);
        $inv4->recalculateTotals();
        $inv4->update(['status' => 'overdue']);

        // Invoice 5: DRAFT (Acme Corp)
        $inv5 = Invoice::create([
            'invoice_number' => 'INV-2026-0005',
            'type' => 'invoice',
            'status' => 'draft',
            'client_id' => $clientAcme->id,
            'issue_date' => Carbon::now()->toDateString(),
            'due_date' => Carbon::now()->addDays(14)->toDateString(),
            'currency' => 'USD',
            'pdf_template' => 'modern',
            'page_size' => 'a4',
            'locale' => 'en',
        ]);
        $inv5->items()->create([
            'catalog_item_id' => $devItem->id,
            'description' => 'Payment Gateway Migration & Webhook Integration',
            'quantity' => 15.5,
            'unit' => 'hours',
            'unit_price_cents' => 12500,
            'discount_bps' => 0,
            'tax_rate_bps' => 0,
        ]);
        $inv5->recalculateTotals();

        // 5. Quote / Estimate (EST-2026-0001)
        $quote1 = Invoice::create([
            'invoice_number' => 'EST-2026-0001',
            'type' => 'quote',
            'status' => 'sent',
            'client_id' => $clientWayne->id,
            'issue_date' => Carbon::now()->subDays(3)->toDateString(),
            'due_date' => Carbon::now()->addDays(27)->toDateString(),
            'currency' => 'USD',
            'pdf_template' => 'minimal',
            'page_size' => 'letter',
            'locale' => 'en',
            'finalized_at' => Carbon::now()->subDays(3),
            'sent_at' => Carbon::now()->subDays(3),
        ]);
        $quote1->items()->create([
            'catalog_item_id' => $archItem->id,
            'description' => 'Comprehensive Architecture Re-platforming Plan',
            'quantity' => 40,
            'unit' => 'hours',
            'unit_price_cents' => 20000,
            'discount_bps' => 1000, // 10% volume discount
            'tax_rate_bps' => 0,
        ]);
        $quote1->recalculateTotals();

        // 6. Credit Note (CN-2026-0001)
        $cn1 = Invoice::create([
            'invoice_number' => 'CN-2026-0001',
            'type' => 'credit_note',
            'status' => 'draft',
            'client_id' => $clientAcme->id,
            'parent_invoice_id' => $inv1->id,
            'issue_date' => Carbon::now()->subDays(5)->toDateString(),
            'due_date' => Carbon::now()->subDays(5)->toDateString(),
            'currency' => 'USD',
            'notes' => 'Credit note for unused consulting hours on INV-2026-0001',
            'pdf_template' => 'modern',
            'page_size' => 'a4',
            'locale' => 'en',
        ]);
        $cn1->items()->create([
            'description' => 'Rebate for unused architecture consulting hours',
            'quantity' => 2,
            'unit' => 'hours',
            'unit_price_cents' => 20000,
            'discount_bps' => 0,
            'tax_rate_bps' => 0,
        ]);
        $cn1->recalculateTotals();

        // 7. Recurring Invoice schedule
        RecurringInvoice::create([
            'client_id' => $clientGlobex->id,
            'frequency' => 'monthly',
            'start_date' => Carbon::now()->startOfMonth()->toDateString(),
            'next_issue_date' => Carbon::now()->addMonthNoOverflow()->startOfMonth()->toDateString(),
            'payment_terms_days' => 14,
            'auto_send' => true,
            'status' => 'active',
            'template_payload' => [
                'currency' => 'EUR',
                'notes' => 'Monthly dedicated maintenance and updates retainer',
                'items' => [
                    [
                        'catalog_item_id' => $retainerItem->id,
                        'description' => 'Monthly Retainer - Priority Maintenance & Support',
                        'quantity' => 1,
                        'unit' => 'month',
                        'unit_price_cents' => 110000,
                        'discount_bps' => 0,
                        'tax_rate_bps' => 2100,
                        'tax_name' => 'IVA 21%',
                    ],
                ],
            ],
        ]);
    }
}
