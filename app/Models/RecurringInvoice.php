<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class RecurringInvoice extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_issue_date' => 'date',
        'last_generated_at' => 'datetime',
        'auto_send' => 'boolean',
        'payment_terms_days' => 'integer',
        'template_payload' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Compute next issue date after generating an occurrence.
     */
    public function advanceNextIssueDate(): Carbon
    {
        $current = Carbon::parse($this->next_issue_date);

        $next = match ($this->frequency) {
            'weekly' => $current->copy()->addWeek(),
            'biweekly' => $current->copy()->addWeeks(2),
            'monthly' => $current->copy()->addMonthNoOverflow(),
            'quarterly' => $current->copy()->addMonthsNoOverflow(3),
            'yearly' => $current->copy()->addYearNoOverflow(),
            default => $current->copy()->addMonthNoOverflow(),
        };

        if ($this->end_date && $next->gt($this->end_date)) {
            $this->status = 'finished';
        }

        $this->next_issue_date = $next->toDateString();
        $this->last_generated_at = now();
        $this->save();

        return $next;
    }

    /**
     * Generate actual draft invoice from this recurring template.
     */
    public function generateInvoice(): Invoice
    {
        return DB::transaction(function () {
            $profile = BusinessProfile::current();
            $payload = $this->template_payload ?? [];

            $issueDate = $this->next_issue_date->toDateString();
            $dueDate = $this->next_issue_date->copy()->addDays($this->payment_terms_days ?: $profile->default_payment_terms_days)->toDateString();

            $invoice = Invoice::create([
                'invoice_number' => $profile->generateNextInvoiceNumber(),
                'type' => 'invoice',
                'status' => 'draft',
                'client_id' => $this->client_id,
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'currency' => $payload['currency'] ?? $profile->default_currency,
                'tax_inclusive' => $payload['tax_inclusive'] ?? $profile->default_tax_inclusive,
                'notes' => $payload['notes'] ?? $profile->default_notes,
                'payment_terms' => $payload['payment_terms'] ?? $profile->default_footer,
                'payment_instructions' => $payload['payment_instructions'] ?? $profile->bank_iban,
                'pdf_template' => $payload['pdf_template'] ?? $profile->default_template,
                'page_size' => $payload['page_size'] ?? $profile->default_page_size,
                'locale' => $payload['locale'] ?? $profile->default_locale,
                'shipping_fee_cents' => (int) ($payload['shipping_fee_cents'] ?? 0),
            ]);

            $items = $payload['items'] ?? [];
            foreach ($items as $index => $item) {
                $invoice->items()->create([
                    'sort_order' => $index,
                    'catalog_item_id' => $item['catalog_item_id'] ?? null,
                    'description' => $item['description'] ?? 'Recurring Service',
                    'quantity' => $item['quantity'] ?? 1,
                    'unit' => $item['unit'] ?? 'hours',
                    'unit_price_cents' => (int) ($item['unit_price_cents'] ?? 0),
                    'discount_bps' => (int) ($item['discount_bps'] ?? 0),
                    'tax_rate_bps' => (int) ($item['tax_rate_bps'] ?? 0),
                    'tax_name' => $item['tax_name'] ?? null,
                ]);
            }

            $invoice->recalculateTotals();

            AuditLog::log($invoice->id, 'generated_from_recurring', ['recurring_id' => $this->id]);

            $this->advanceNextIssueDate();

            return $invoice;
        });
    }
}
