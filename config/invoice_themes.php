<?php

/*
 * PDF design tokens. DomPDF has no CSS custom properties, so every value here
 * is interpolated into the stylesheet by invoices/pdf/partials/styles.blade.php.
 *
 * Enumerations:
 *   header: split | band | centered | stacked
 *   table:  filled | solid | ruled | minimal
 *   total:  box | solid | line
 */

$base = [
    'font' => 'Helvetica',
    'accent' => '#2563eb',
    'accent_ink' => '#ffffff',
    'accent_soft' => '#eff4ff',
    'ink' => '#0f172a',
    'text' => '#334155',
    'muted' => '#64748b',
    'line' => '#e2e8f0',
    'surface' => '#f8fafc',
    'positive' => '#15803d',
    'size' => ['base' => 9.5, 'small' => 8, 'label' => 7, 'title' => 24, 'total' => 15, 'name' => 15],
    'radius' => 6,
    'margin' => ['top' => 16, 'right' => 16, 'bottom' => 24, 'left' => 16],
    'header' => 'split',
    'table' => 'filled',
    'total' => 'box',
    'uppercase_labels' => true,
    'show' => array_fill_keys(\App\Services\ThemeTokens::showKeys(), true),
];

return [
    'presets' => [
        'modern' => array_replace($base, [
            'header' => 'split',
            'table' => 'filled',
            'total' => 'box',
        ]),

        'emerald' => array_replace($base, [
            'accent' => '#1f7a4d',
            'accent_soft' => '#eaf5ef',
            'ink' => '#10261b',
            'text' => '#2c4338',
            'muted' => '#5f7a6c',
            'line' => '#d5e6dc',
            'surface' => '#f5faf7',
            'header' => 'band',
            'table' => 'solid',
            'total' => 'solid',
            'radius' => 8,
        ]),

        'classic' => array_replace($base, [
            'font' => 'Times',
            'accent' => '#5b21b6',
            'accent_soft' => '#f3eefc',
            'ink' => '#1e1033',
            'text' => '#2b2140',
            'muted' => '#5b5470',
            'line' => '#a89fbd',
            'surface' => '#ffffff',
            'size' => ['base' => 10.5, 'small' => 9, 'label' => 8, 'title' => 22, 'total' => 14, 'name' => 18],
            'radius' => 0,
            'header' => 'centered',
            'table' => 'ruled',
            'total' => 'line',
        ]),

        'minimal' => array_replace($base, [
            'accent' => '#111111',
            'accent_ink' => '#ffffff',
            'accent_soft' => '#f5f5f5',
            'ink' => '#111111',
            'text' => '#404040',
            'muted' => '#8a8a8a',
            'line' => '#e5e5e5',
            'surface' => '#fafafa',
            'size' => ['base' => 9.5, 'small' => 8, 'label' => 7, 'title' => 30, 'total' => 22, 'name' => 11],
            'radius' => 0,
            'margin' => ['top' => 20, 'right' => 20, 'bottom' => 24, 'left' => 20],
            'header' => 'stacked',
            'table' => 'minimal',
            'total' => 'line',
        ]),
    ],
];
