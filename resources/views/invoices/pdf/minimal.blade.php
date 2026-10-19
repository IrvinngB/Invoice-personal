@php
    $t = function($key) use ($locale) {
        return __("invoice.{$key}", [], $locale);
    };
    $dateFormat = $locale === 'es' ? 'D [de] MMMM [de] Y' : 'MMMM D, Y';
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 20mm;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            font-size: 10px;
            color: #27272a;
            line-height: 1.5;
            background: #ffffff;
        }
        .header { margin-bottom: 40px; }
        .title { font-size: 16px; font-weight: 600; letter-spacing: -0.5px; }
        .meta-grid { width: 100%; margin-bottom: 30px; border-collapse: collapse; }
        .meta-cell { vertical-align: top; width: 33%; font-size: 10px; }
        .label { color: #a1a1aa; font-size: 8px; text-transform: uppercase; margin-bottom: 2px; }
        .items { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        .items th { border-bottom: 1px solid #18181b; padding: 6px 0; text-align: left; font-size: 9px; font-weight: 500; }
        .items td { padding: 8px 0; border-bottom: 1px solid #f4f4f5; font-size: 10px; }
        .summary { width: 40%; margin-left: auto; border-collapse: collapse; }
        .summary td { padding: 4px 0; font-size: 10px; }
        .total-row td { border-top: 1px solid #18181b; font-weight: 600; font-size: 12px; padding-top: 8px; }
    </style>
</head>
<body>
<div class="header">
    <table style="width: 100%;">
        <tr>
            <td>
                <div class="title">{{ $profile->business_name }}</div>
                <div style="color: #71717a;">{{ $profile->email }} · {{ $profile->website }}</div>
            </td>
            <td style="text-align: right;">
                <div style="font-size: 10px; font-weight: 600; color: #71717a; text-transform: uppercase;">{{ $invoice->type === 'credit_note' ? $t('credit_note') : $t($invoice->type) }}</div>
                <div style="font-size: 16px; font-weight: 600;">{{ $invoice->invoice_number }}</div>
                <div style="color: #71717a;">{{ $t('status_' . $invoice->status) }}</div>
            </td>
        </tr>
    </table>
</div>

<table class="meta-grid">
    <tr>
        <td class="meta-cell">
            <div class="label">{{ $t('billed_to') }}</div>
            <div style="font-weight: 600;">{{ $client->company ?: $client->name }}</div>
            <div>{!! nl2br(e($client->billing_address)) !!}</div>
            @if($client->tax_id) <div>{{ $client->tax_id }}</div> @endif
        </td>
        <td class="meta-cell">
            <div class="label">{{ $t('issue_date') }}</div>
            <div>{{ $invoice->issue_date->locale($locale)->isoFormat($dateFormat) }}</div>
            <div class="label" style="margin-top: 8px;">{{ $t('due_date') }}</div>
            <div>{{ $invoice->due_date->locale($locale)->isoFormat($dateFormat) }}</div>
        </td>
        <td class="meta-cell" style="text-align: right;">
            <div class="label">{{ $t('balance_due') }}</div>
            <div style="font-size: 16px; font-weight: 700; color: #18181b;">{{ \App\Services\InvoiceCalculationService::formatMoney($invoice->balance_cents, $invoice->currency, $locale) }}</div>
            <div style="color: #71717a; font-size: 9px;">{{ $invoice->currency }}</div>
        </td>
    </tr>
</table>

<table class="items">
    <thead>
        <tr>
            <th style="width: 60%;">{{ $t('description') }}</th>
            <th style="width: 10%; text-align: center;">{{ $t('qty') }}</th>
            <th style="width: 15%; text-align: right;">{{ $t('price') }}</th>
            <th style="width: 15%; text-align: right;">{{ $t('amount') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $item)
        <tr>
            <td>{{ $item->description }}</td>
            <td style="text-align: center;">{{ rtrim(rtrim(number_format((float)$item->quantity, 4), '0'), '.') }}</td>
            <td style="text-align: right;">{{ \App\Services\InvoiceCalculationService::formatMoney($item->unit_price_cents, $invoice->currency, $locale) }}</td>
            <td style="text-align: right;">{{ \App\Services\InvoiceCalculationService::formatMoney($item->total_cents, $invoice->currency, $locale) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<table class="summary">
    <tr>
        <td style="color: #71717a;">{{ $t('subtotal') }}</td>
        <td style="text-align: right;">{{ \App\Services\InvoiceCalculationService::formatMoney($invoice->subtotal_cents, $invoice->currency, $locale) }}</td>
    </tr>
    @if($invoice->tax_total_cents > 0)
    <tr>
        <td style="color: #71717a;">{{ $t('tax') }}</td>
        <td style="text-align: right;">{{ \App\Services\InvoiceCalculationService::formatMoney($invoice->tax_total_cents, $invoice->currency, $locale) }}</td>
    </tr>
    @endif
    <tr class="total-row">
        <td>{{ $t('total') }}</td>
        <td style="text-align: right;">{{ \App\Services\InvoiceCalculationService::formatMoney($invoice->total_cents, $invoice->currency, $locale) }}</td>
    </tr>
</table>

@if($profile->bank_iban || $invoice->payment_link)
<div style="margin-top: 40px; padding-top: 15px; border-top: 1px solid #f4f4f5; font-size: 9px; color: #71717a;">
    <div><strong>{{ $t('payment') }}:</strong> {{ $profile->bank_iban ? $t('iban') . ': ' . $profile->bank_iban : '' }} {{ $invoice->payment_link ? '· ' . $invoice->payment_link : '' }}</div>
</div>
@endif

</body>
</html>
