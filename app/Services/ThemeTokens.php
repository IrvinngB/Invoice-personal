<?php

namespace App\Services;

/**
 * The only tokens a user may override, and how they are validated.
 *
 * Every value ends up interpolated into the PDF stylesheet, so this class is
 * the security boundary: anything not matching a whitelist is dropped.
 */
class ThemeTokens
{
    public const FONTS = ['Helvetica', 'Times', 'DejaVu Sans'];
    public const HEADERS = ['split', 'band', 'centered', 'stacked'];
    public const TABLES = ['filled', 'solid', 'ruled', 'minimal'];
    public const TOTALS = ['box', 'solid', 'line'];
    public const RADII = [0, 4, 8, 12];
    public const SCALES = ['compact' => 0.92, 'normal' => 1.0, 'large' => 1.1];

    /** Elements a theme can hide, grouped for the editor. Everything is visible by default. */
    public const SHOW_GROUPS = [
        'header' => ['logo', 'seller_owner', 'seller_tax_id', 'seller_address', 'seller_email', 'seller_phone', 'seller_website', 'status'],
        'client' => ['client_contact', 'client_tax_id', 'client_address', 'client_email', 'client_phone'],
        'dates' => ['meta_due_date', 'meta_currency'],
        'footer' => ['notes', 'footer'],
    ];

    public const LABEL_MAX_LENGTH = 40;

    /** Texts a theme can rename or blank out (keys of the invoice lang files), grouped for the editor. */
    public const LABEL_GROUPS = [
        'document' => ['invoice', 'quote', 'credit_note'],
        'parties' => ['billed_to', 'ship_to', 'attention', 'tax_id'],
        'dates' => ['issue_date', 'due_date', 'currency', 'payment_terms', 'reference'],
        'table' => ['description', 'qty', 'price', 'tax', 'amount'],
        'totals' => ['subtotal', 'discount', 'shipping_fee', 'total', 'amount_paid', 'balance_due'],
        'payment' => ['payment_instructions', 'bank', 'account_name', 'iban', 'swift_bic', 'paypal', 'payment_link', 'scan_to_pay'],
        'other' => ['notes', 'authorized_signature', 'thank_you'],
    ];

    /** @return list<string> */
    public static function labelKeys(): array
    {
        return array_merge(...array_values(self::LABEL_GROUPS));
    }

    /**
     * Turn the editor's two inputs into the stored shape: key => custom text,
     * where an empty string means "show the value with no label at all".
     * An empty text box without the "no label" flag simply keeps the default.
     *
     * @param array<string, mixed> $texts
     * @param array<string, mixed> $blank
     * @return array<string, string>
     */
    public static function labelsFromForm(array $texts, array $blank): array
    {
        $labels = [];
        foreach (self::labelKeys() as $key) {
            if (filter_var($blank[$key] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                $labels[$key] = '';
            } elseif (is_scalar($texts[$key] ?? null) && trim((string) $texts[$key]) !== '') {
                $labels[$key] = (string) $texts[$key];
            }
        }

        return $labels;
    }

    /** @return list<string> */
    public static function showKeys(): array
    {
        return array_merge(...array_values(self::SHOW_GROUPS));
    }

    public static function rules(): array
    {
        return [
            'accent' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'font' => ['nullable', 'in:' . implode(',', self::FONTS)],
            'header' => ['nullable', 'in:' . implode(',', self::HEADERS)],
            'table' => ['nullable', 'in:' . implode(',', self::TABLES)],
            'total' => ['nullable', 'in:' . implode(',', self::TOTALS)],
            'radius' => ['nullable', 'in:' . implode(',', self::RADII)],
            'scale' => ['nullable', 'in:' . implode(',', array_keys(self::SCALES))],
            'uppercase_labels' => ['nullable', 'boolean'],
            'show' => ['nullable', 'array'],
            'show.*' => ['nullable', 'boolean'],
            'labels' => ['nullable', 'array'],
            'labels.*' => ['nullable', 'string', 'max:' . self::LABEL_MAX_LENGTH],
            'labels_blank' => ['nullable', 'array'],
            'labels_blank.*' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Keep only known, valid overrides (normalised to their final types).
     */
    public static function sanitize(array $input): array
    {
        $out = [];

        if (isset($input['accent']) && preg_match('/^#[0-9a-fA-F]{6}$/', (string) $input['accent'])) {
            $out['accent'] = strtolower($input['accent']);
        }
        foreach (['font' => self::FONTS, 'header' => self::HEADERS, 'table' => self::TABLES, 'total' => self::TOTALS] as $key => $allowed) {
            if (isset($input[$key]) && in_array($input[$key], $allowed, true)) {
                $out[$key] = $input[$key];
            }
        }
        if (isset($input['radius']) && is_numeric($input['radius']) && in_array((int) $input['radius'], self::RADII, true)) {
            $out['radius'] = (int) $input['radius'];
        }
        if (isset($input['scale']) && array_key_exists($input['scale'], self::SCALES)) {
            $out['scale'] = $input['scale'];
        }
        if (array_key_exists('uppercase_labels', $input) && $input['uppercase_labels'] !== null) {
            $out['uppercase_labels'] = filter_var($input['uppercase_labels'], FILTER_VALIDATE_BOOLEAN);
        }
        if (isset($input['show']) && is_array($input['show'])) {
            foreach (self::showKeys() as $key) {
                if (array_key_exists($key, $input['show']) && $input['show'][$key] !== null) {
                    $out['show'][$key] = filter_var($input['show'][$key], FILTER_VALIDATE_BOOLEAN);
                }
            }
        }
        if (isset($input['labels']) && is_array($input['labels'])) {
            foreach (self::labelKeys() as $key) {
                if (array_key_exists($key, $input['labels']) && is_scalar($input['labels'][$key])) {
                    $text = preg_replace('/[\x00-\x1F\x7F]/u', '', (string) $input['labels'][$key]) ?? '';
                    $out['labels'][$key] = mb_substr(trim($text), 0, self::LABEL_MAX_LENGTH);
                }
            }
        }

        return $out;
    }

    /**
     * Apply sanitised overrides on top of a preset, deriving the colours and
     * sizes that depend on them.
     */
    public static function apply(array $theme, array $overrides): array
    {
        $overrides = self::sanitize($overrides);

        if (isset($overrides['accent'])) {
            $theme['accent'] = $overrides['accent'];
            $theme['accent_soft'] = self::mix($overrides['accent'], '#ffffff', 0.9);
            $theme['accent_ink'] = self::luminance($overrides['accent']) > 0.55 ? '#111111' : '#ffffff';
        }
        foreach (['font', 'header', 'table', 'total', 'radius', 'uppercase_labels'] as $key) {
            if (array_key_exists($key, $overrides)) {
                $theme[$key] = $overrides[$key];
            }
        }
        if (isset($overrides['show'])) {
            $theme['show'] = array_merge($theme['show'] ?? array_fill_keys(self::showKeys(), true), $overrides['show']);
        }
        if (isset($overrides['labels'])) {
            $theme['labels'] = array_merge($theme['labels'] ?? [], $overrides['labels']);
        }
        if (isset($overrides['scale'])) {
            $factor = self::SCALES[$overrides['scale']];
            $theme['size'] = array_map(fn ($s) => round($s * $factor, 2), $theme['size']);
        }

        return $theme;
    }

    /** Blend $hex towards $toward; $weight is the share of $toward (0..1). */
    private static function mix(string $hex, string $toward, float $weight): string
    {
        $a = self::rgb($hex);
        $b = self::rgb($toward);
        $mixed = array_map(fn ($x, $y) => (int) round($x * (1 - $weight) + $y * $weight), $a, $b);

        return sprintf('#%02x%02x%02x', ...$mixed);
    }

    /** Relative luminance (WCAG) in 0..1. */
    private static function luminance(string $hex): float
    {
        [$r, $g, $b] = array_map(function ($c) {
            $c /= 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, self::rgb($hex));

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /** @return int[] */
    private static function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }
}
