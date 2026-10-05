<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('business_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('business_name')->default('My Business');
            $table->string('owner_name')->default('Freelancer');
            $table->string('tax_id')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->text('address')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('default_currency', 3)->default('USD');
            $table->integer('default_payment_terms_days')->default(14);
            $table->integer('default_tax_rate_bps')->default(0); // e.g. 2100 = 21.00%
            $table->boolean('default_tax_inclusive')->default(false);
            $table->text('default_notes')->nullable();
            $table->text('default_footer')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('bank_iban')->nullable();
            $table->string('bank_swift')->nullable();
            $table->string('paypal_email')->nullable();
            $table->string('payment_link')->nullable();
            $table->string('invoice_prefix')->default('INV-');
            $table->integer('invoice_number_digits')->default(4);
            $table->integer('next_invoice_number')->default(1);
            $table->string('quote_prefix')->default('EST-');
            $table->integer('quote_number_digits')->default(4);
            $table->integer('next_quote_number')->default(1);
            $table->string('credit_note_prefix')->default('CN-');
            $table->integer('credit_note_number_digits')->default(4);
            $table->integer('next_credit_note_number')->default(1);
            $table->string('default_template')->default('modern');
            $table->string('default_page_size')->default('a4');
            $table->string('default_locale', 5)->default('en');
            $table->string('smtp_host')->nullable();
            $table->integer('smtp_port')->nullable();
            $table->string('smtp_username')->nullable();
            $table->text('smtp_password')->nullable();
            $table->string('smtp_encryption')->nullable();
            $table->string('smtp_from_address')->nullable();
            $table->string('smtp_from_name')->nullable();
            $table->string('timezone')->default('UTC');
            $table->timestamps();
        });

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('tax_id')->nullable();
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->text('notes')->nullable();
            $table->string('currency', 3)->nullable();
            $table->integer('default_tax_rate_bps')->nullable();
            $table->integer('default_payment_terms_days')->nullable();
            $table->string('locale', 5)->default('en');
            $table->timestamps();
        });

        Schema::create('catalog_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit')->default('hours');
            $table->integer('unit_price_cents')->default(0);
            $table->integer('tax_rate_bps')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->string('type')->default('invoice'); // invoice, quote, credit_note
            $table->string('status')->default('draft'); // draft, sent, partially_paid, paid, overdue, void
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('parent_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->date('issue_date');
            $table->date('due_date');
            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 12, 6)->default(1.000000);
            $table->boolean('tax_inclusive')->default(false);
            $table->integer('subtotal_cents')->default(0);
            $table->integer('discount_total_cents')->default(0);
            $table->integer('tax_total_cents')->default(0);
            $table->integer('shipping_fee_cents')->default(0);
            $table->integer('total_cents')->default(0);
            $table->integer('amount_paid_cents')->default(0);
            $table->integer('balance_cents')->default(0);
            $table->text('notes')->nullable();
            $table->text('payment_terms')->nullable();
            $table->text('payment_instructions')->nullable();
            $table->string('payment_link')->nullable();
            $table->string('pdf_template')->default('modern');
            $table->string('page_size', 10)->default('a4');
            $table->string('locale', 5)->default('en');
            $table->timestamp('finalized_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('catalog_item_id')->nullable()->constrained('catalog_items')->nullOnDelete();
            $table->integer('sort_order')->default(0);
            $table->text('description');
            $table->decimal('quantity', 12, 4)->default(1.0000);
            $table->string('unit')->default('hours');
            $table->integer('unit_price_cents')->default(0);
            $table->integer('discount_bps')->default(0);
            $table->integer('tax_rate_bps')->default(0);
            $table->string('tax_name')->nullable();
            $table->integer('subtotal_cents')->default(0);
            $table->integer('discount_amount_cents')->default(0);
            $table->integer('tax_amount_cents')->default(0);
            $table->integer('total_cents')->default(0);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->date('payment_date');
            $table->integer('amount_cents');
            $table->string('method')->default('bank_transfer'); // bank_transfer, paypal, cash, card, other
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('recurring_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('frequency'); // weekly, biweekly, monthly, quarterly, yearly
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_issue_date');
            $table->string('status')->default('active'); // active, paused, finished
            $table->boolean('auto_send')->default(false);
            $table->integer('payment_terms_days')->default(14);
            $table->json('template_payload');
            $table->timestamp('last_generated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('event');
            $table->string('user_ip')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('recurring_invoices');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('catalog_items');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('business_profiles');
    }
};
