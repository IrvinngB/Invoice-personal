<?php

namespace App\Models;

use App\Services\InvoiceCalculationService;
use Illuminate\Database\Eloquent\Model;

class CatalogItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'unit_price_cents' => 'integer',
        'tax_rate_bps' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getFormattedPriceAttribute(): string
    {
        return InvoiceCalculationService::formatMoney($this->unit_price_cents);
    }
}
