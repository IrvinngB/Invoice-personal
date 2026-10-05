@php
    $t = function($key) use ($locale) {
        return __("invoice.{$key}", [], $locale);
    };
    $isPaid = $invoice->status === 'paid';
    $isVoid = $invoice->status === 'void';
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 25mm 20mm;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12px;
            color: #111827;
            line-height: 1.5;
            background: #ffffff;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #111827;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 22px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .doc-title {
            font-size: 24px;
            font-weight: bold;
            text-align: right;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 25px;
        }
        .info-col {
            vertical-align: top;
            width: 50%;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .items-table th {
            border-bottom: 1px solid #111827;
            border-top: 1px solid #111827;
            padding: 8px 4px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
        }
        .items-table td {
            border-bottom: 1px dotted #d1d5db;
            padding: 8px 4px;
            vertical-align: top;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        .sum-grand {
            border-top: 2px solid #111827;
            border-bottom: 2px solid #111827;
            font-weight: bold;
        }
    </style>
</head>
<body>
<table class="header-table">
    <tr>
        <td style="width: 60%; vertical-align: top;">
            <div class="company-name">{{ $profile->business_name }}</div>
            <div>{!! nl2br(e($profile->address)) !!}</div>
            @if($profile->tax_id) <div>Tax ID: {{ $profile->tax_id }}</div> @endif
            @if($profile->email) <div>{{ $profile->email }}</div> @endif
        </td>
        <td style="width: 40%; text-align: right; vertical-align: top;">
            <div class="doc-title">{{ $invoice->type === 'quote' ? $t('quote') : ($invoice->type === 'credit_note' ? $t('credit_note') : $t('invoice')) }}</div>
            <div style="font-size: 14px; font-weight: bold;">{{ $invoice->invoice_number }}</div>
            <div style="margin-top: 8px;">{{ $t('issue_date') }}: {{ $invoice->issue_date->format('d/m/Y') }}</div>
            <div>{{ $t('due_date') }}: {{ $invoice->due_date->format('d/m/Y') }}</div>
        </td>
    </tr>
</table>

<table class="info-table">
    <tr>
        <td class="info-col">
            <div style="font-weight: bold; text-transform: uppercase; margin-bottom: 4px;">{{ $t('billed_to') }}:</div>
            <div style="font-size: 14px; font-weight: bold;">{{ $client->company ?: $client->name }}</div>
            @if($client->company && $client->name !== $client->company) <div>Attn: {{ $client->name }}</div> @endif
            @if($client->billing_address) <div>{!! nl2br(e($client->billing_address)) !!}</div> @endif
            @if($client->tax_id) <div>Tax ID: {{ $client->tax_id }}</div> @endif
        </td>
    </tr>
</table>

<table class="items-table">
    <thead>
        <tr>
            <th style="width: 50%;">{{ $t('description') }}</th>
            <th style="width: 10%; text-align: center;">{{ $t('qty') }}</th>
            <th style="width: 15%; text-align: right;">{{ $t('price') }}</th>
            <th style="width: 25%; text-align: right;">{{ $t('total') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $item)
        <tr>
            <td>{{ $item->description }}</td>
            <td style="text-align: center;">{{ rtrim(rtrim(number_format((float)$item->quantity, 4), '0'), '.') }} {{ $item->unit }}</td>
            <td style="text-align: right;">{{ \App\Services\InvoiceCalculationService::formatMoney($item->unit_price_cents, $invoice->currency, $locale) }}</td>
            <td style="text-align: right;">{{ \App\Services\InvoiceCalculationService::formatMoney($item->total_cents, $invoice->currency, $locale) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<table class="totals-table">
    <tr>
        <td style="width: 55%; vertical-align: top;">
            @if($profile->bank_iban || $profile->paypal_email)
                <div style="border: 1px solid #9ca3af; padding: 10px; font-size: 11px;">
                    <strong>{{ $t('payment_instructions') }}:</strong><br>
                    @if($profile->bank_name) {{ $t('bank') }}: {{ $profile->bank_name }}<br> @endif
                    @if($profile->bank_iban) {{ $t('iban') }}: {{ $profile->bank_iban }}<br> @endif
                    @if($profile->bank_swift) {{ $t('swift_bic') }}: {{ $profile->bank_swift }}<br> @endif
                    @if($profile->paypal_email) {{ $t('paypal') }}: {{ $profile->paypal_email }} @endif
                </div>
            @endif
        </td>
        <td style="width: 45%; vertical-align: top;">
            <table style="width: 100%; border-collapse: collapse; text-align: right;">
                <tr>
                    <td style="padding: 4px;">{{ $t('subtotal') }}:</td>
                    <td style="padding: 4px; font-weight: bold;">{{ \App\Services\InvoiceCalculationService::formatMoney($invoice->subtotal_cents, $invoice->currency, $locale) }}</td>
                </tr>
                @if($invoice->tax_total_cents > 0)
                <tr>
                    <td style="padding: 4px;">{{ $t('tax') }}:</td>
                    <td style="padding: 4px; font-weight: bold;">{{ \App\Services\InvoiceCalculationService::formatMoney($invoice->tax_total_cents, $invoice->currency, $locale) }}</td>
                </tr>
                @endif
                <tr class="sum-grand">
                    <td style="padding: 6px;">{{ $t('total') }}:</td>
                    <td style="padding: 6px;">{{ \App\Services\InvoiceCalculationService::formatMoney($invoice->total_cents, $invoice->currency, $locale) }}</td>
                </tr>
                <tr>
                    <td style="padding: 6px; font-size: 13px; font-weight: bold;">{{ $t('balance_due') }}:</td>
                    <td style="padding: 6px; font-size: 13px; font-weight: bold;">{{ \App\Services\InvoiceCalculationService::formatMoney($invoice->balance_cents, $invoice->currency, $locale) }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

@if($invoice->notes)
<div style="margin-top: 30px; font-size: 11px;">
    <strong>{{ $t('notes') }}:</strong><br>
    {!! nl2br(e($invoice->notes)) !!}
</div>
@endif

</body>
</html>
