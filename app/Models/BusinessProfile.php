<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessProfile extends Model
{
    protected $table = 'business_profiles';

    protected $guarded = ['id'];

    protected $casts = [
        'default_payment_terms_days' => 'integer',
        'default_tax_rate_bps' => 'integer',
        'default_tax_inclusive' => 'boolean',
        'invoice_number_digits' => 'integer',
        'next_invoice_number' => 'integer',
        'quote_number_digits' => 'integer',
        'next_quote_number' => 'integer',
        'credit_note_number_digits' => 'integer',
        'next_credit_note_number' => 'integer',
        'smtp_port' => 'integer',
    ];

    /**
     * Singleton accessor for business profile.
     */
    public static function current(): self
    {
        return static::firstOrCreate([], [
            'business_name' => 'My Studio / Consulting',
            'owner_name' => 'Freelance Developer',
            'tax_id' => 'TAX-12345678',
            'email' => 'hello@example.com',
            'phone' => '+1 (555) 019-2834',
            'website' => 'https://example.com',
            'address' => "123 Creative Street\nSuite 400\nSan Francisco, CA 94107",
            'default_currency' => 'USD',
            'default_payment_terms_days' => 14,
            'default_tax_rate_bps' => 0,
            'default_tax_inclusive' => false,
            'default_notes' => 'Thank you for your business. Please remit payment within payment terms.',
            'default_footer' => 'Payment due within 14 days. Late payments may be subject to a 5% monthly fee.',
            'bank_name' => 'Silicon Valley Bank / First Direct',
            'bank_account_name' => 'Freelance Consulting LLC',
            'bank_iban' => 'US12SVBK00001234567890',
            'bank_swift' => 'SVBKUS6S',
            'paypal_email' => 'billing@example.com',
            'payment_link' => 'https://buy.stripe.com/example_payment_link',
            'invoice_prefix' => 'INV-',
            'invoice_number_digits' => 4,
            'next_invoice_number' => 1,
            'quote_prefix' => 'EST-',
            'quote_number_digits' => 4,
            'next_quote_number' => 1,
            'credit_note_prefix' => 'CN-',
            'credit_note_number_digits' => 4,
            'next_credit_note_number' => 1,
            'default_template' => 'modern',
            'default_page_size' => 'a4',
            'default_locale' => 'en',
            'timezone' => 'America/New_York',
        ]);
    }

    /**
     * Generate and increment next invoice number sequentially.
     */
    public function generateNextInvoiceNumber(): string
    {
        $num = $this->next_invoice_number;
        $this->increment('next_invoice_number');
        return $this->invoice_prefix . str_pad((string) $num, $this->invoice_number_digits, '0', STR_PAD_LEFT);
    }

    /**
     * Generate and increment next quote number sequentially.
     */
    public function generateNextQuoteNumber(): string
    {
        $num = $this->next_quote_number;
        $this->increment('next_quote_number');
        return $this->quote_prefix . str_pad((string) $num, $this->quote_number_digits, '0', STR_PAD_LEFT);
    }

    /**
     * Generate and increment next credit note number sequentially.
     */
    public function generateNextCreditNoteNumber(): string
    {
        $num = $this->next_credit_note_number;
        $this->increment('next_credit_note_number');
        return $this->credit_note_prefix . str_pad((string) $num, $this->credit_note_number_digits, '0', STR_PAD_LEFT);
    }
}
