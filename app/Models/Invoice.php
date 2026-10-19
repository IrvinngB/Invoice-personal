<?php

namespace App\Models;

use App\Services\InvoiceCalculationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Invoice extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'exchange_rate' => 'decimal:6',
        'tax_inclusive' => 'boolean',
        'subtotal_cents' => 'integer',
        'discount_total_cents' => 'integer',
        'tax_total_cents' => 'integer',
        'shipping_fee_cents' => 'integer',
        'total_cents' => 'integer',
        'amount_paid_cents' => 'integer',
        'balance_cents' => 'integer',
        'show_signature' => 'boolean',
        'show_payment_instructions' => 'boolean',
        'finalized_at' => 'datetime',
        'sent_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function parentInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'parent_invoice_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order', 'asc');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('payment_date', 'desc');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class)->orderBy('created_at', 'desc');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isFinalized(): bool
    {
        return !is_null($this->finalized_at);
    }

    public function isLocked(): bool
    {
        return $this->isFinalized() || in_array($this->status, ['sent', 'partially_paid', 'paid', 'void']);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isVoid(): bool
    {
        return $this->status === 'void';
    }

    public function isOverdue(): bool
    {
        if ($this->isPaid() || $this->isVoid() || $this->isDraft()) {
            return false;
        }
        return $this->due_date->isPast();
    }

    /**
     * Recalculate all financial totals from line items and update balance & status.
     */
    public function recalculateTotals(): self
    {
        $itemsData = $this->items->map(fn($item) => [
            'quantity' => (float) $item->quantity,
            'unit_price_cents' => (int) $item->unit_price_cents,
            'discount_bps' => (int) $item->discount_bps,
            'tax_rate_bps' => (int) $item->tax_rate_bps,
            'tax_name' => $item->tax_name,
        ])->toArray();

        $amountPaid = (int) $this->payments()->sum('amount_cents');

        $calcService = app(InvoiceCalculationService::class);
        $result = $calcService->calculateInvoice(
            $itemsData,
            (int) ($this->shipping_fee_cents ?? 0),
            (bool) $this->tax_inclusive,
            $amountPaid
        );

        // Persist calculated financial fields back to each individual line item
        foreach ($this->items as $index => $item) {
            if (isset($result['items'][$index])) {
                $itemCalc = $result['items'][$index];
                $item->update([
                    'subtotal_cents' => $itemCalc['subtotal_cents'],
                    'discount_amount_cents' => $itemCalc['discount_amount_cents'],
                    'tax_amount_cents' => $itemCalc['tax_amount_cents'],
                    'total_cents' => $itemCalc['total_cents'],
                ]);
            }
        }

        $newStatus = $calcService->determineStatus(
            $this->status ?? 'draft',
            $result['total_cents'],
            $amountPaid,
            $this->due_date ? $this->due_date->format('Y-m-d') : date('Y-m-d'),
            $this->isFinalized()
        );

        $this->update([
            'subtotal_cents' => $result['subtotal_cents'],
            'discount_total_cents' => $result['discount_total_cents'],
            'tax_total_cents' => $result['tax_total_cents'],
            'total_cents' => $result['total_cents'],
            'amount_paid_cents' => $amountPaid,
            'balance_cents' => $result['balance_cents'],
            'status' => $newStatus,
        ]);

        return $this;
    }

    /**
     * Lock invoice as finalized.
     */
    public function finalize(): self
    {
        if ($this->isFinalized()) {
            return $this;
        }

        $this->finalized_at = now();
        if ($this->status === 'draft') {
            $this->status = 'sent';
            $this->sent_at = now();
        }
        $this->save();

        AuditLog::log($this->id, 'finalized', ['status' => $this->status]);

        return $this;
    }

    /**
     * Record a new payment.
     */
    public function recordPayment(int $amountCents, string $paymentDate, string $method, ?string $reference = null, ?string $notes = null): Payment
    {
        return DB::transaction(function () use ($amountCents, $paymentDate, $method, $reference, $notes) {
            $payment = $this->payments()->create([
                'amount_cents' => $amountCents,
                'payment_date' => $paymentDate,
                'method' => $method,
                'reference' => $reference,
                'notes' => $notes,
            ]);

            // Finalize if not yet finalized
            if (!$this->isFinalized()) {
                $this->finalized_at = now();
            }

            $this->recalculateTotals();

            AuditLog::log($this->id, 'payment_recorded', [
                'payment_id' => $payment->id,
                'amount_cents' => $amountCents,
                'method' => $method,
                'new_balance' => $this->balance_cents,
                'new_status' => $this->status,
            ]);

            return $payment;
        });
    }

    /**
     * Void the invoice.
     */
    public function markAsVoid(?string $reason = null): self
    {
        $this->update([
            'status' => 'void',
            'voided_at' => now(),
        ]);

        AuditLog::log($this->id, 'voided', ['reason' => $reason]);

        return $this;
    }

    /**
     * Duplicate invoice as a new draft.
     */
    public function duplicate(): self
    {
        $profile = BusinessProfile::current();
        $newNumber = $this->type === 'quote' 
            ? $profile->generateNextQuoteNumber() 
            : $profile->generateNextInvoiceNumber();

        $clone = $this->replicate([
            'finalized_at',
            'sent_at',
            'voided_at',
            'amount_paid_cents',
        ]);

        $clone->invoice_number = $newNumber;
        $clone->status = 'draft';
        $clone->issue_date = now()->toDateString();
        $clone->due_date = now()->addDays($profile->default_payment_terms_days)->toDateString();
        $clone->amount_paid_cents = 0;
        $clone->balance_cents = $this->total_cents;
        $clone->save();

        foreach ($this->items as $item) {
            $cloneItem = $item->replicate();
            $cloneItem->invoice_id = $clone->id;
            $cloneItem->save();
        }

        AuditLog::log($clone->id, 'created_from_duplicate', ['source_invoice_id' => $this->id, 'source_number' => $this->invoice_number]);

        return $clone;
    }

    /**
     * Convert quote to formal invoice.
     */
    public function convertQuoteToInvoice(): self
    {
        if ($this->type !== 'quote') {
            throw new \DomainException("Only quotes can be converted to invoices.");
        }

        $profile = BusinessProfile::current();
        $invoiceNumber = $profile->generateNextInvoiceNumber();

        $invoice = $this->replicate([
            'finalized_at',
            'sent_at',
            'voided_at',
            'amount_paid_cents',
        ]);

        $invoice->type = 'invoice';
        $invoice->invoice_number = $invoiceNumber;
        $invoice->parent_invoice_id = $this->id;
        $invoice->status = 'draft';
        $invoice->issue_date = now()->toDateString();
        $invoice->due_date = now()->addDays($profile->default_payment_terms_days)->toDateString();
        $invoice->amount_paid_cents = 0;
        $invoice->balance_cents = $this->total_cents;
        $invoice->save();

        foreach ($this->items as $item) {
            $cloneItem = $item->replicate();
            $cloneItem->invoice_id = $invoice->id;
            $cloneItem->save();
        }

        AuditLog::log($this->id, 'quote_converted', ['converted_to_invoice_id' => $invoice->id, 'invoice_number' => $invoiceNumber]);
        AuditLog::log($invoice->id, 'created_from_quote', ['source_quote_id' => $this->id, 'quote_number' => $this->invoice_number]);

        return $invoice;
    }

    /**
     * Create a credit note for this invoice.
     */
    public function createCreditNote(): self
    {
        $profile = BusinessProfile::current();
        $cnNumber = $profile->generateNextCreditNoteNumber();

        $cn = $this->replicate([
            'finalized_at',
            'sent_at',
            'voided_at',
            'amount_paid_cents',
        ]);

        $cn->type = 'credit_note';
        $cn->invoice_number = $cnNumber;
        $cn->parent_invoice_id = $this->id;
        $cn->status = 'draft';
        $cn->issue_date = now()->toDateString();
        $cn->due_date = now()->toDateString();
        $cn->notes = "Credit note for invoice {$this->invoice_number}. " . $this->notes;
        $cn->save();

        foreach ($this->items as $item) {
            $cloneItem = $item->replicate();
            $cloneItem->invoice_id = $cn->id;
            $cloneItem->save();
        }

        AuditLog::log($this->id, 'credit_note_issued', ['credit_note_id' => $cn->id, 'credit_note_number' => $cnNumber]);
        AuditLog::log($cn->id, 'created_as_credit_note', ['source_invoice_id' => $this->id, 'source_invoice_number' => $this->invoice_number]);

        return $cn;
    }

    // Money formatters
    public function getFormattedSubtotalAttribute(): string
    {
        return InvoiceCalculationService::formatMoney($this->subtotal_cents, $this->currency, $this->locale);
    }

    public function getFormattedDiscountTotalAttribute(): string
    {
        return InvoiceCalculationService::formatMoney($this->discount_total_cents, $this->currency, $this->locale);
    }

    public function getFormattedTaxTotalAttribute(): string
    {
        return InvoiceCalculationService::formatMoney($this->tax_total_cents, $this->currency, $this->locale);
    }

    public function getFormattedShippingFeeAttribute(): string
    {
        return InvoiceCalculationService::formatMoney($this->shipping_fee_cents, $this->currency, $this->locale);
    }

    public function getFormattedTotalAttribute(): string
    {
        return InvoiceCalculationService::formatMoney($this->total_cents, $this->currency, $this->locale);
    }

    public function getFormattedAmountPaidAttribute(): string
    {
        return InvoiceCalculationService::formatMoney($this->amount_paid_cents, $this->currency, $this->locale);
    }

    public function getFormattedBalanceAttribute(): string
    {
        return InvoiceCalculationService::formatMoney($this->balance_cents, $this->currency, $this->locale);
    }
}
