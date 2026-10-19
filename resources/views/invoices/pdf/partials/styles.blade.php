@php
    $th = $ctx->theme;
    $sz = $th['size'];
    $mg = $th['margin'];
    $r = $th['radius'];
    $label = $th['uppercase_labels'] ? 'uppercase' : 'none';
@endphp
<style>
    @page { margin: {{ $mg['top'] }}mm {{ $mg['right'] }}mm {{ $mg['bottom'] }}mm {{ $mg['left'] }}mm; }
    body {
        font-family: '{{ $th['font'] }}', Helvetica, Arial, sans-serif;
        font-size: {{ $sz['base'] }}pt;
        color: {{ $th['text'] }};
        line-height: 1.5;
        margin: 0;
        padding: 0;
    }
    table { border-collapse: collapse; }
    td { vertical-align: top; }
    .w-full { width: 100%; }
    .right { text-align: right; }
    .center { text-align: center; }
    .muted { color: {{ $th['muted'] }}; }
    .ink { color: {{ $th['ink'] }}; }
    .small { font-size: {{ $sz['small'] }}pt; }
    .bold { font-weight: bold; }

    .label {
        font-size: {{ $sz['label'] }}pt;
        font-weight: bold;
        text-transform: {{ $label }};
        letter-spacing: 0.8pt;
        color: {{ $th['muted'] }};
        margin-bottom: 4pt;
    }

    /* ---------- Header ---------- */
    .logo { max-height: 48pt; max-width: 170pt; margin-bottom: 6pt; }
    .biz-name { font-size: {{ $sz['name'] }}pt; font-weight: bold; color: {{ $th['ink'] }}; }
    .biz-meta { font-size: {{ $sz['small'] }}pt; color: {{ $th['muted'] }}; line-height: 1.45; }
    .doc-title {
        font-size: {{ $sz['title'] }}pt;
        font-weight: bold;
        color: {{ $th['ink'] }};
        text-transform: uppercase;
        letter-spacing: 1pt;
        line-height: 1.1;
    }
    .doc-number { font-size: {{ $sz['base'] + 3 }}pt; font-weight: bold; color: {{ $th['accent'] }}; margin-top: 3pt; }
    .status-pill {
        display: inline-block;
        padding: 2pt 7pt;
        font-size: {{ $sz['label'] }}pt;
        font-weight: bold;
        letter-spacing: 0.6pt;
        text-transform: uppercase;
        border-radius: {{ min($r, 10) }}pt;
    }

    .band {
        background: {{ $th['accent'] }};
        color: {{ $th['accent_ink'] }};
        border-radius: {{ $r }}pt;
        padding: 16pt 18pt;
    }
    .band .biz-name, .band .doc-title, .band .doc-number, .band .biz-meta { color: {{ $th['accent_ink'] }}; }
    .band .biz-meta { opacity: 0.85; }

    .rule-double { border-top: 0.6pt solid {{ $th['ink'] }}; border-bottom: 0.6pt solid {{ $th['ink'] }}; height: 2.5pt; }

    /* ---------- Parties & meta ---------- */
    .party-name { font-size: {{ $sz['base'] + 2 }}pt; font-weight: bold; color: {{ $th['ink'] }}; margin-bottom: 2pt; }
    .party-detail { font-size: {{ $sz['base'] }}pt; color: {{ $th['text'] }}; line-height: 1.5; }
    .meta-table td { padding: 2.5pt 0; font-size: {{ $sz['base'] }}pt; }
    .meta-key { color: {{ $th['muted'] }}; padding-right: 14pt; }
    .meta-val { color: {{ $th['ink'] }}; font-weight: bold; text-align: right; white-space: nowrap; }

    /* ---------- Items ---------- */
    .items { width: 100%; }
    .items th {
        font-size: {{ $sz['label'] }}pt;
        font-weight: bold;
        text-transform: {{ $label }};
        letter-spacing: 0.7pt;
        padding: 8pt 8pt;
        text-align: left;
    }
    .items td { padding: 7pt 8pt; font-size: {{ $sz['base'] }}pt; vertical-align: top; }
    .items .num { text-align: right; white-space: nowrap; }
    .items .qty { text-align: center; white-space: nowrap; }
    .item-title { font-weight: bold; color: {{ $th['ink'] }}; }
    .item-sub { font-size: {{ $sz['small'] }}pt; color: {{ $th['muted'] }}; margin-top: 1.5pt; }

@if($th['table'] === 'filled')
    .items th { background: {{ $th['surface'] }}; color: {{ $th['muted'] }}; border-bottom: 1.5pt solid {{ $th['line'] }}; }
    .items td { border-bottom: 0.6pt solid {{ $th['line'] }}; }
@elseif($th['table'] === 'solid')
    .items th { background: {{ $th['accent'] }}; color: {{ $th['accent_ink'] }}; }
    .items td { border-bottom: 0.6pt solid {{ $th['line'] }}; }
    .items tbody tr.alt td { background: {{ $th['surface'] }}; }
@elseif($th['table'] === 'ruled')
    .items th { color: {{ $th['ink'] }}; border-top: 1pt solid {{ $th['ink'] }}; border-bottom: 0.6pt solid {{ $th['ink'] }}; }
    .items td { border-bottom: 0.4pt solid {{ $th['line'] }}; }
    .items tbody tr.last td { border-bottom: 1pt solid {{ $th['ink'] }}; }
@else
    .items th { color: {{ $th['muted'] }}; border-bottom: 0.8pt solid {{ $th['ink'] }}; padding-left: 0; }
    .items td { border-bottom: 0.5pt solid {{ $th['line'] }}; padding: 11pt 8pt; }
    .items td:first-child { padding-left: 0; }
    .items .num:last-child, .items th.num:last-child { padding-right: 0; }
@endif

    /* ---------- Summary ---------- */
    .summary { width: 100%; }
    .summary td { padding: 4pt 0; font-size: {{ $sz['base'] }}pt; }
    .summary .k { color: {{ $th['muted'] }}; }
    .summary .v { text-align: right; color: {{ $th['ink'] }}; font-weight: bold; }
    .summary .sep td { border-top: 0.8pt solid {{ $th['line'] }}; padding-top: 6pt; }
    .summary .sep .k { color: {{ $th['ink'] }}; font-weight: bold; }
    .summary .sep .v { font-size: {{ $sz['base'] + 1.5 }}pt; }

    .balance { width: 100%; margin-top: 8pt; }
    .balance td { padding: 10pt 12pt; }
    .balance .k { font-size: {{ $sz['small'] + 0.5 }}pt; font-weight: bold; text-transform: {{ $label }}; letter-spacing: 0.7pt; }
    .balance .v { text-align: right; font-size: {{ $sz['total'] }}pt; font-weight: bold; }
@if($th['total'] === 'box')
    .balance td { background: {{ $th['accent_soft'] }}; color: {{ $th['accent'] }}; }
    .balance td:first-child { border-radius: {{ $r }}pt 0 0 {{ $r }}pt; }
    .balance td:last-child { border-radius: 0 {{ $r }}pt {{ $r }}pt 0; }
@elseif($th['total'] === 'solid')
    .balance td { background: {{ $th['accent'] }}; color: {{ $th['accent_ink'] }}; }
    .balance td:first-child { border-radius: {{ $r }}pt 0 0 {{ $r }}pt; }
    .balance td:last-child { border-radius: 0 {{ $r }}pt {{ $r }}pt 0; }
@else
    .balance td { color: {{ $th['ink'] }}; border-top: 1.2pt solid {{ $th['ink'] }}; padding-left: 0; padding-right: 0; }
@endif

    /* ---------- Cards ---------- */
    .card {
        background: {{ $th['surface'] }};
        border: 0.6pt solid {{ $th['line'] }};
        border-radius: {{ $r }}pt;
        padding: 11pt 13pt;
    }
    .card-row { font-size: {{ $sz['small'] + 0.5 }}pt; color: {{ $th['text'] }}; margin-bottom: 2pt; }
    .card-row strong { color: {{ $th['ink'] }}; }
    .qr { width: 66pt; height: 66pt; }
    .qr-caption { font-size: {{ $sz['label'] }}pt; color: {{ $th['muted'] }}; margin-top: 2pt; text-align: center; }
    .signature-img { max-height: 40pt; max-width: 140pt; }
    .signature-line { border-top: 0.6pt solid {{ $th['line'] }}; margin-top: 3pt; padding-top: 3pt; font-size: {{ $sz['label'] }}pt; color: {{ $th['muted'] }}; text-transform: {{ $label }}; letter-spacing: 0.6pt; }

    /* ---------- Footer & watermark ---------- */
    .page-footer {
        position: fixed;
        left: 0; right: 0; bottom: -{{ $mg['bottom'] - 12 }}mm;
        border-top: 0.6pt solid {{ $th['line'] }};
        padding-top: 7pt;
        font-size: {{ $sz['small'] }}pt;
        color: {{ $th['muted'] }};
    }
    .watermark {
        position: fixed;
        top: 38%; left: 12%;
        font-size: 90pt;
        font-weight: bold;
        letter-spacing: 6pt;
        text-transform: uppercase;
        transform: rotate(-30deg);
    }
</style>
