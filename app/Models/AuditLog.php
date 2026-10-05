<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public static function log(int $invoiceId, string $event, ?array $payload = null, ?string $userIp = null): self
    {
        return static::create([
            'invoice_id' => $invoiceId,
            'event' => $event,
            'user_ip' => $userIp ?? request()->ip(),
            'payload' => $payload,
            'created_at' => now(),
        ]);
    }
}
