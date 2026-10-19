@php
    $t = function($key) use ($locale) {
        return __("invoice.{$key}", [], $locale);
    };
    $isPaid = $invoice->status === 'paid';
    $isVoid = $invoice->status === 'void';
    $dateFormat = $locale === 'es' ? 'D [de] MMMM [de] Y' : 'MMMM D, Y';
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 28mm 18mm 25mm 18mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.45;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .logo {
            max-height: 55px;
            max-width: 180px;
            margin-bottom: 8px;
        }
        .company-name {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.3px;
        }
        .company-meta {
            color: #64748b;
            font-size: 10px;
            line-height: 1.4;
        }
        .doc-title {
            font-size: 26px;
            font-weight: 800;
            text-align: right;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 4px 0;
        }
        .doc-number {
            font-size: 14px;
            font-weight: 600;
            color: #2563eb;
            text-align: right;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            border-radius: 4px;
            letter-spacing: 0.5px;
        }
        .badge-paid { background: #dcfce7; color: #15803d; }
        .badge-draft { background: #f1f5f9; color: #475569; }
        .badge-sent { background: #e0f2fe; color: #0369a1; }
        .badge-overdue { background: #fee2e2; color: #b91c1c; }
        .badge-void { background: #f1f5f9; color: #94a3b8; text-decoration: line-through; }

        /* Meta block */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .info-box {
            vertical-align: top;
            width: 50%;
        }
        .info-heading {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #94a3b8;
            margin-bottom: 6px;
        }
        .client-name {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
        }
        .client-meta {
            color: #475569;
            font-size: 10px;
            line-height: 1.4;
        }

        .dates-table {
            width: 100%;
            border-collapse: collapse;
        }
        .dates-table td {
            padding: 3px 0;
            font-size: 10px;
        }
        .dates-label {
            color: #64748b;
            font-weight: 500;
            text-align: right;
            padding-right: 12px !important;
        }
        .dates-val {
            color: #0f172a;
            font-weight: 600;
            text-align: right;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th {
            background-color: #f8fafc;
            color: #475569;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 9px 8px;
            border-bottom: 2px solid #e2e8f0;
        }
        .items-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 10.5px;
            vertical-align: top;
        }
        .col-desc { text-align: left; width: 44%; }
        .col-qty { text-align: center; width: 10%; }
        .col-price { text-align: right; width: 14%; }
        .col-tax { text-align: right; width: 14%; }
        .col-total { text-align: right; width: 18%; }

        .item-title {
            font-weight: 600;
            color: #0f172a;
        }
        .item-sub {
            color: #64748b;
            font-size: 9.5px;
            margin-top: 2px;
        }

        /* Totals */
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .totals-left {
            width: 55%;
            vertical-align: top;
            padding-right: 20px;
        }
        .totals-right {
            width: 45%;
            vertical-align: top;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-table td {
            padding: 5px 8px;
            font-size: 11px;
        }
        .sum-label {
            color: #64748b;
            text-align: right;
        }
        .sum-val {
            text-align: right;
            font-weight: 600;
            color: #0f172a;
        }
        .sum-grand {
            background-color: #f8fafc;
            border-top: 2px solid #e2e8f0;
            border-bottom: 2px solid #e2e8f0;
        }
        .sum-grand td {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            padding: 8px 8px;
        }
        .sum-balance {
            background-color: #eff6ff;
            border-radius: 4px;
        }
        .sum-balance td {
            font-size: 14px;
            font-weight: 800;
            color: #1d4ed8;
            padding: 8px 8px;
        }

        /* Instructions & QR */
        .payment-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px;
            margin-top: 15px;
        }
        .payment-box-title {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #334155;
            margin-bottom: 6px;
        }
        .payment-row {
            font-size: 9.5px;
            color: #475569;
            margin-bottom: 2px;
        }
        .payment-row strong {
            color: #0f172a;
        }
        .qr-cell {
            width: 85px;
            text-align: center;
            vertical-align: middle;
            padding-left: 10px;
        }
        .qr-img {
            width: 75px;
            height: 75px;
        }
        .qr-caption {
            font-size: 7.5px;
            color: #64748b;
            margin-top: 2px;
        }

        /* Footer */
        .footer-note {
            margin-top: 25px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            font-size: 9.5px;
            color: #64748b;
            line-height: 1.5;
        }

        .watermark {
            position: absolute;
            top: 35%;
            left: 20%;
            font-size: 75px;
            font-weight: 900;
            color: rgba(239, 68, 68, 0.12);
            transform: rotate(-30deg);
            text-transform: uppercase;
            letter-spacing: 5px;
            z-index: 9999;
        }
    </style>
</head>
<body>

@if($isPaid)
    <div class="watermark" style="color: rgba(34, 197, 94, 0.12);">{{ $t('status_paid') }}</div>
@elseif($isVoid)
    <div class="watermark">{{ $t('status_void') }}</div>
@elseif($invoice->isOverdue())
    <div class="watermark">{{ $t('status_overdue') }}</div>
@endif

<!-- Header -->
<table class="header-table">
    <tr>
        <td style="vertical-align: top; width: 55%;">
            @if($profile->logo_path && file_exists(public_path($profile->logo_path)))
                <img src="{{ public_path($profile->logo_path) }}" class="logo" alt="Logo">
            @endif
            <div class="company-name">{{ $profile->business_name }}</div>
            <div class="company-meta">
                @if($profile->owner_name && $profile->owner_name !== $profile->business_name)
                    <div>{{ $profile->owner_name }}</div>
                @endif
                @if($profile->tax_id)
                    <div>{{ $t('tax_id') }}: {{ $profile->tax_id }}</div>
                @endif
                <div>{!! nl2br(e($profile->address)) !!}</div>
                @if($profile->email) <div>{{ $profile->email }}</div> @endif
                @if($profile->website) <div>{{ $profile->website }}</div> @endif
            </div>
        </td>
        <td style="vertical-align: top; width: 45%; text-align: right;">
            <div class="doc-title">
                @if($invoice->type === 'quote')
                    {{ $t('quote') }}
                @elseif($invoice->type === 'credit_note')
                    {{ $t('credit_note') }}
                @else
                    {{ $t('invoice') }}
                @endif
            </div>
            <div class="doc-number">{{ $invoice->invoice_number }}</div>
            <div style="margin-top: 6px;">
                <span class="status-badge badge-{{ $invoice->status }}">
                    {{ $t('status_' . $invoice->status) }}
                </span>
            </div>
            <table class="dates-table" style="margin-top: 15px;">
                <tr>
                    <td class="dates-label">{{ $t('issue_date') }}:</td>
                    <td class="dates-val">{{ $invoice->issue_date->locale($locale)->isoFormat($dateFormat) }}</td>
                </tr>
                <tr>
                    <td class="dates-label">{{ $t('due_date') }}:</td>
                    <td class="dates-val">{{ $invoice->due_date->locale($locale)->isoFormat($dateFormat) }}</td>
                </tr>
                @if($invoice->parentInvoice)
                <tr>
                    <td class="dates-label">{{ $t('reference') }}:</td>
                    <td class="dates-val">{{ $invoice->parentInvoice->invoice_number }}</td>
                </tr>
                @endif
            </table>
        </td>
    </tr>
</table>

<!-- Client Info -->
<table class="info-table">
    <tr>
        <td class="info-box">
            <div class="info-heading">{{ $t('billed_to') }}</div>
            <div class="client-name">{{ $client->company ?: $client->name }}</div>
            <div class="client-meta">
                @if($client->company && $client->name !== $client->company)
                    <div>{{ $t('attention') }}: {{ $client->name }}</div>
                @endif
                @if($client->tax_id)
                    <div>{{ $t('tax_id') }}: {{ $client->tax_id }}</div>
                @endif
                @if($client->billing_address)
                    <div>{!! nl2br(e($client->billing_address)) !!}</div>
                @endif
                @if($client->email)
                    <div>{{ $client->email }}</div>
                @endif
                @if($client->phone)
                    <div>{{ $client->phone }}</div>
                @endif
            </div>
        </td>
        <td class="info-box" style="text-align: right;">
            <!-- Currency notice or quick status summary -->
            <div class="info-heading">{{ $t('currency_terms') }}</div>
            <div style="font-size: 11px; font-weight: 600; color: #0f172a;">
                {{ $invoice->currency }}
                @if($invoice->exchange_rate && (float)$invoice->exchange_rate != 1.0)
                    <span style="font-size: 9.5px; color: #64748b; font-weight: normal;">({{ $t('rate') }}: {{ $invoice->exchange_rate }})</span>
                @endif
            </div>
            <div style="font-size: 10px; color: #64748b; margin-top: 3px;">
                {{ $invoice->payment_terms ?: $t('payment_due_within_days', ['days' => 14]) }}
            </div>
        </td>
    </tr>
</table>

<!-- Items Table -->
<table class="items-table">
    <thead>
        <tr>
            <th class="col-desc">{{ $t('description') }}</th>
            <th class="col-qty">{{ $t('qty') }}</th>
            <th class="col-price">{{ $t('price') }}</th>
            @if($invoice->tax_total_cents > 0)
                <th class="col-tax">{{ $t('tax') }}</th>
            @endif
            <th class="col-total">{{ $t('amount') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $item)
        <tr>
            <td class="col-desc">
                <div class="item-title">{{ $item->description }}</div>
                @if($item->discount_bps > 0)
                    <div class="item-sub">{{ $t('discount') }}: {{ number_format($item->discount_bps / 100, 1) }}% (-{{ \App\Services\InvoiceCalculationService::formatMoney($item->discount_amount_cents, $invoice->currency, $locale) }})</div>
                @endif
            </td>
            <td class="col-qty">
                {{ rtrim(rtrim(number_format((float)$item->quantity, 4), '0'), '.') }}
                <span style="font-size: 8.5px; color: #64748b;">{{ $item->unit }}</span>
            </td>
            <td class="col-price">{{ \App\Services\InvoiceCalculationService::formatMoney($item->unit_price_cents, $invoice->currency, $locale) }}</td>
            @if($invoice->tax_total_cents > 0)
                <td class="col-tax">
                    @if($item->tax_rate_bps > 0)
                        {{ number_format($item->tax_rate_bps / 100, 1) }}%
                    @else
                        -
                    @endif
                </td>
            @endif
            <td class="col-total">{{ \App\Services\InvoiceCalculationService::formatMoney($item->total_cents, $invoice->currency, $locale) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<!-- Totals & Payment Instructions -->
<table class="totals-table">
    <tr>
        <td class="totals-left">
            @if($profile->bank_iban || $profile->paypal_email || $invoice->payment_instructions || $qrCodeSvg)
            <div class="payment-box">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="vertical-align: top;">
                            <div class="payment-box-title">{{ $t('payment_instructions') }}</div>
                            @if($profile->bank_name)
                                <div class="payment-row">{{ $t('bank') }}: <strong>{{ $profile->bank_name }}</strong></div>
                            @endif
                            @if($profile->bank_account_name)
                                <div class="payment-row">{{ $t('account_name') }}: <strong>{{ $profile->bank_account_name }}</strong></div>
                            @endif
                            @if($profile->bank_iban)
                                <div class="payment-row">{{ $t('iban') }}: <strong>{{ $profile->bank_iban }}</strong></div>
                            @endif
                            @if($profile->bank_swift)
                                <div class="payment-row">{{ $t('swift_bic') }}: <strong>{{ $profile->bank_swift }}</strong></div>
                            @endif
                            @if($profile->paypal_email)
                                <div class="payment-row">{{ $t('paypal') }}: <strong>{{ $profile->paypal_email }}</strong></div>
                            @endif
                            @if($invoice->payment_link)
                                <div class="payment-row">{{ $t('payment_link') }}: <a href="{{ $invoice->payment_link }}" style="color: #2563eb; text-decoration: none;">{{ $invoice->payment_link }}</a></div>
                            @endif
                        </td>
                        @if($qrCodeSvg)
                        <td class="qr-cell">
                            <img src="{{ $qrCodeSvg }}" class="qr-img" alt="QR Code">
                            <div class="qr-caption">{{ $t('scan_to_pay') }}</div>
                        </td>
                        @endif
                    </tr>
                </table>
            </div>
            @endif
        </td>
        <td class="totals-right">
            <table class="summary-table">
                <tr>
                    <td class="sum-label">{{ $t('subtotal') }}:</td>
                    <td class="sum-val">{{ \App\Services\InvoiceCalculationService::formatMoney($invoice->subtotal_cents, $invoice->currency, $locale) }}</td>
                </tr>
                @if($invoice->discount_total_cents > 0)
                <tr>
                    <td class="sum-label">{{ $t('discount') }}:</td>
                    <td class="sum-val" style="color: #16a34a;">-{{ \App\Services\InvoiceCalculationService::formatMoney($invoice->discount_total_cents, $invoice->currency, $locale) }}</td>
                </tr>
                @endif
                @if($invoice->tax_total_cents > 0)
                <tr>
                    <td class="sum-label">{{ $t('tax') }} ({{ $invoice->tax_inclusive ? $t('tax_included') : $t('tax_excluded') }}):</td>
                    <td class="sum-val">{{ \App\Services\InvoiceCalculationService::formatMoney($invoice->tax_total_cents, $invoice->currency, $locale) }}</td>
                </tr>
                @endif
                @if($invoice->shipping_fee_cents > 0)
                <tr>
                    <td class="sum-label">{{ $t('shipping_handling') }}:</td>
                    <td class="sum-val">{{ \App\Services\InvoiceCalculationService::formatMoney($invoice->shipping_fee_cents, $invoice->currency, $locale) }}</td>
                </tr>
                @endif
                <tr class="sum-grand">
                    <td class="sum-label" style="color: #0f172a; font-weight: 700;">{{ $t('total') }}:</td>
                    <td class="sum-val" style="font-size: 13px;">{{ \App\Services\InvoiceCalculationService::formatMoney($invoice->total_cents, $invoice->currency, $locale) }}</td>
                </tr>
                @if($invoice->amount_paid_cents > 0)
                <tr>
                    <td class="sum-label">{{ $t('amount_paid') }}:</td>
                    <td class="sum-val" style="color: #16a34a;">-{{ \App\Services\InvoiceCalculationService::formatMoney($invoice->amount_paid_cents, $invoice->currency, $locale) }}</td>
                </tr>
                @endif
                <tr class="sum-balance">
                    <td class="sum-label" style="color: #1d4ed8; font-weight: 800;">{{ $t('balance_due') }}:</td>
                    <td class="sum-val" style="color: #1d4ed8; font-size: 14px;">{{ \App\Services\InvoiceCalculationService::formatMoney($invoice->balance_cents, $invoice->currency, $locale) }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- Notes and Footer -->
@if($invoice->notes || $profile->default_footer)
<div class="footer-note">
    @if($invoice->notes)
        <div style="font-weight: 700; color: #334155; margin-bottom: 2px;">{{ $t('notes') }}:</div>
        <div>{!! nl2br(e($invoice->notes)) !!}</div>
    @endif
    @if($profile->default_footer)
        <div style="margin-top: 6px; font-size: 9px; color: #94a3b8;">
            {!! nl2br(e($profile->default_footer)) !!}
        </div>
    @endif
    <div style="text-align: center; margin-top: 12px; font-style: italic; color: #94a3b8;">
        {{ $t('thank_you') }}
    </div>
</div>
@endif

</body>
</html>
