<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceTheme extends Model
{
    public const KEY_PREFIX = 'custom:';

    protected $guarded = ['id'];

    protected $casts = [
        'overrides' => 'array',
    ];

    public function key(): string
    {
        return self::KEY_PREFIX . $this->id;
    }

    /**
     * Extract the id from a "custom:{id}" template key, or null for presets.
     */
    public static function idFromKey(?string $key): ?int
    {
        if ($key === null || !str_starts_with($key, self::KEY_PREFIX)) {
            return null;
        }

        $id = substr($key, strlen(self::KEY_PREFIX));

        return ctype_digit($id) ? (int) $id : null;
    }
}
