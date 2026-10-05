<?php

namespace App\Models;

use App\Services\InvoiceCalculationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'sort_order' => 'integer',
        'quantity' => 'decimal:4',
        'unit_price_cents' => 'integer',
        'discount_bps' => 'integer',
        'tax_rate_bps' => 'integer',
        'subtotal_cents' => 'integer',
        'discount_amount_cents' => 'integer',
        'tax_amount_cents' => 'integer',
        'total_cents' => 'integer',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    public function getFormattedUnitPriceAttribute(): string
    {
        return InvoiceCalculationService::formatMoney($this->unit_price_cents, $this->invoice?->currency ?? 'USD');
    }

    public function getFormattedTotalAttribute(): string
    {
        return InvoiceCalculationService::formatMoney($this->total_cents, $this->invoice?->currency ?? 'USD');
    }
}
