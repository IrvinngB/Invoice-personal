<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'default_tax_rate_bps' => 'integer',
        'default_payment_terms_days' => 'integer',
    ];

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function recurringInvoices(): HasMany
    {
        return $this->hasMany(RecurringInvoice::class);
    }

    /**
     * Scope to search clients by name, company, email or tax ID.
     */
    public function scopeSearch($query, ?string $term)
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('company', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%")
              ->orWhere('tax_id', 'like', "%{$term}%");
        });
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->company && $this->company !== $this->name) {
            return "{$this->company} ({$this->name})";
        }
        return $this->name;
    }
}
