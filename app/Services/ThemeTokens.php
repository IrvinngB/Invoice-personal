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
