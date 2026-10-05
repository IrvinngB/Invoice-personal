@php
    $t = function($key) use ($locale) {
        return __("invoice.{$key}", [], $locale);
    };

    // Format dates with Carbon localization
    $formattedIssueDate = $locale === 'es'
        ? $invoice->issue_date->locale('es')->isoFormat('D [de] MMMM, Y')
        : $invoice->issue_date->locale('en')->isoFormat('MMMM D, Y');

    $formattedDueDate = $invoice->due_date
        ? ($locale === 'es' ? $invoice->due_date->locale('es')->isoFormat('D [de] MMMM, Y') : $invoice->due_date->locale('en')->isoFormat('MMMM D, Y'))
        : null;

    $isQuote = ($invoice->type ?? 'invoice') === 'quote';
    $docTitle = $isQuote 
        ? ($locale === 'es' ? 'Cotización No.' : 'Quote No.') 
        : ($locale === 'es' ? 'Factura No.' : 'Invoice No.');
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 0;
            size: {{ $pageSize ?? 'letter' }};
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #071F1F;
            line-height: 1.45;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }

        /* Top Branded Bar (Emerald & Lime accent) */
        .top-accent-wrapper {
            width: 100%;
            height: 12px;
            background: #ffffff;
            position: relative;
        }
        .top-bar-emerald {
            height: 12px;
            background-color: #2E7A42;
            width: 48%;
            margin-left: 52%;
        }
        .top-bar-lime {
            position: absolute;
            top: 10px;
            right: 0;
            width: 20%;
            height: 3px;
            background-color: #B4D333;
        }

        .page-content {
            padding: 30px 48px 40px 48px;
        }

        /* Header Layout */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 22px;
        }
        .owner-title {
            font-size: 21px;
            font-weight: 800;
            letter-spacing: 0.8px;
            color: #071F1F;
            text-transform: uppercase;
            margin-bottom: 2px;
            line-height: 1.1;
        }
        .owner-id {
            font-size: 15px;
            font-weight: 700;
            color: #2E7A42;
            margin-bottom: 22px;
            letter-spacing: 0.3px;
        }

        .client-section {
            font-size: 11px;
            line-height: 1.45;
            color: #1a2e26;
        }
        .client-label {
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #4D6659;
            margin-bottom: 4px;
        }
        .client-name {
            font-weight: 800;
            font-size: 12px;
            color: #071F1F;
            margin-bottom: 2px;
        }

        .meta-right {
            text-align: right;
            vertical-align: top;
            font-size: 11px;
            line-height: 1.45;
            color: #334A3E;
        }
        .logo-box {
            text-align: right;
            margin-bottom: 12px;
        }
        .logo-img {
            max-height: 60px;
            max-width: 140px;
        }
        .invoice-title {
            font-size: 14px;
            font-weight: 800;
            color: #071F1F;
            margin-bottom: 4px;
            letter-spacing: 0.2px;
        }
        .invoice-meta-date {
            font-weight: 600;
            color: #1F382E;
            margin-bottom: 3px;
        }
        .invoice-meta-sub {
            font-size: 10px;
            color: #4D6659;
            line-height: 1.4;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
            margin-bottom: 0px;
        }
        .items-table th {
            background-color: #2E7A42;
            color: #ffffff;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            padding: 10px 14px;
            border: none;
        }
        .items-table th.col-price {
            background-color: #3FA35B;
            text-align: right;
        }
        .items-table td {
            padding: 12px 14px;
            font-size: 11px;
            vertical-align: middle;
            border-bottom: 1px solid #E2EFE0;
            color: #071F1F;
        }
        .col-desc { width: 55%; text-align: left; }
        .col-qty { width: 20%; text-align: center; font-variant-numeric: tabular-nums; }
        .col-price { 
            width: 25%; 
            text-align: right; 
            background-color: #F4FAF0;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }

        /* Total Section */
        .total-box {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            border-top: 2px solid #2E7A42;
            border-bottom: 3px solid #2E7A42;
            background-color: #ffffff;
        }
        .total-box td {
            padding: 11px 14px;
            font-size: 11.5px;
            font-weight: 800;
        }
        .total-label {
            width: 75%;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #071F1F;
        }
        .total-amount {
            width: 25%;
            text-align: right;
            background-color: #EAF3E4;
            color: #071F1F;
            font-size: 13.5px;
            font-variant-numeric: tabular-nums;
        }

        /* Payment Details Box */
        .payment-box {
            margin-top: 24px;
            padding: 10px 14px;
            background-color: #F4FAF0;
            border-left: 3px solid #2E7A42;
            border-radius: 2px;
            font-size: 10px;
            color: #1F382E;
            line-height: 1.5;
        }
        .payment-heading {
            font-size: 9.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #2E7A42;
            margin-bottom: 3px;
        }

        /* Notes & Terms */
        .notes-section {
            margin-top: 16px;
            font-size: 10px;
            color: #4D6659;
            line-height: 1.4;
            font-style: italic;
        }

        /* Footer / Contact */
        .footer-section {
            margin-top: 36px;
            font-size: 10px;
            color: #334A3E;
            line-height: 1.6;
        }
        .footer-heading {
            font-weight: 800;
            text-transform: lowercase;
            margin-bottom: 3px;
            color: #071F1F;
            letter-spacing: 0.3px;
        }
        .footer-link {
            color: #1F382E;
            text-decoration: underline;
            display: block;
        }
        .footer-link-inline {
            color: #1F382E;
            text-decoration: none;
            border-bottom: 1px dotted #2E7A42;
        }

        /* Bottom Accent Stripe */
        .bottom-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 14px;
            background-color: #2E7A42;
        }
        .bottom-sub-bar {
            position: absolute;
            bottom: 14px;
            left: 0;
            right: 0;
            height: 3px;
            background-color: #B4D333;
        }
    </style>
</head>
<body>

<div class="top-accent-wrapper">
    <div class="top-bar-emerald"></div>
    <div class="top-bar-lime"></div>
</div>

<div class="page-content">
    <table class="header-table">
        <tr>
            <td style="width: 55%; vertical-align: top;">
                <div class="owner-title">{{ $profile->owner_name ?: $profile->business_name }}</div>
                @if($profile->tax_id)
                    <div class="owner-id">{{ $profile->tax_id }}</div>
                @endif

                <div class="client-section">
                    <div class="client-label">{{ $locale === 'es' ? 'Facturar a' : 'Bill to' }}:</div>
                    <div class="client-name">{{ $client->company ?: $client->name }}</div>
                    @if($client->billing_address)
                        <div>{!! nl2br(e($client->billing_address)) !!}</div>
                    @endif
                    @if($client->tax_id)
                        <div>RUC: {{ $client->tax_id }}</div>
                    @endif
                </div>
            </td>
            <td class="meta-right" style="width: 45%;">
                <div class="logo-box">
                    @if($profile->logo_path && file_exists(public_path($profile->logo_path)))
                        <img src="{{ public_path($profile->logo_path) }}" class="logo-img" alt="Logo">
                    @elseif(file_exists(public_path('images/brand-ib.svg')))
                        <img src="{{ public_path('images/brand-ib.svg') }}" style="height: 48px; width: auto; display: inline-block;" alt="iB Logo">
                    @else
                        <!-- Vector iB Logo fallback -->
                        <div style="display: inline-block; font-size: 34px; font-weight: 900; line-height: 1; letter-spacing: -2px;">
                            <span style="color: #3FA35B;">i</span><span style="color: #2E7A42;">B</span>
                        </div>
                    @endif
                </div>

                <div class="invoice-title">{{ $docTitle }} {{ $invoice->invoice_number }}</div>
                <div class="invoice-meta-date">{{ $formattedIssueDate }}</div>
                @if($formattedDueDate)
                    <div class="invoice-meta-sub">{{ $locale === 'es' ? 'Vencimiento' : 'Due date' }}: {{ $formattedDueDate }}</div>
                @endif
                @if($profile->address)
                    <div class="invoice-meta-sub">{!! nl2br(e($profile->address)) !!}</div>
                @endif
                @if($profile->email)
                    <div class="invoice-meta-sub" style="color: #2E7A42; font-weight: 600;">{{ $profile->email }}</div>
                @endif
            </td>
        </tr>
    </table>

    <!-- Line items -->
    <table class="items-table">
        <thead>
            <tr>
                <th class="col-desc">{{ $locale === 'es' ? 'DESCRIPCIÓN' : 'DESCRIPTION' }}</th>
                <th class="col-qty">{{ $locale === 'es' ? 'CANTIDAD (HORAS)' : 'QUANTITY (HOURS)' }}</th>
                <th class="col-price">{{ $locale === 'es' ? 'PRECIO' : 'AMOUNT' }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            @php
                $qty = (float) $item->quantity;
                $formattedQty = ($qty == (int)$qty) 
                    ? str_pad((string)(int)$qty, 2, '0', STR_PAD_LEFT) 
                    : rtrim(rtrim(number_format($qty, 2), '0'), '.');
            @endphp
            <tr>
                <td class="col-desc">{{ $item->description }}</td>
                <td class="col-qty">{{ $formattedQty }}</td>
                <td class="col-price">{{ \App\Services\InvoiceCalculationService::formatMoney($item->total_cents, $invoice->currency, $locale) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Total -->
    <table class="total-box">
        <tr>
            <td class="total-label">{{ $locale === 'es' ? 'MONTO TOTAL:' : 'TOTAL AMOUNT:' }}</td>
            <td class="total-amount">{{ \App\Services\InvoiceCalculationService::formatMoney($invoice->total_cents, $invoice->currency, $locale) }}</td>
        </tr>
    </table>

    <!-- Payment details if available -->
    @if($profile->bank_name || $profile->bank_iban || $profile->paypal_email)
    <div class="payment-box">
        <div class="payment-heading">{{ $locale === 'es' ? 'Datos de pago / transferencia' : 'Payment Details' }}:</div>
        @if($profile->bank_name)
            <div><strong>{{ $locale === 'es' ? 'Banco' : 'Bank' }}:</strong> {{ $profile->bank_name }} @if($profile->bank_account_name)· {{ $profile->bank_account_name }}@endif</div>
        @endif
        @if($profile->bank_iban)
            <div><strong>{{ $locale === 'es' ? 'Cuenta / Cédula ACH' : 'Account / IBAN' }}:</strong> {{ $profile->bank_iban }}</div>
        @endif
        @if($profile->paypal_email)
            <div><strong>PayPal:</strong> {{ $profile->paypal_email }}</div>
        @endif
    </div>
    @endif

    <!-- Notes if available -->
    @if($invoice->notes)
        <div class="notes-section">
            {{ $invoice->notes }}
        </div>
    @endif

    <!-- Contact links -->
    <div class="footer-section">
        <div class="footer-heading">{{ $locale === 'es' ? 'contacto:' : 'contact:' }}</div>
        <table style="width: 100%; border-collapse: collapse; margin-top: 3px;">
            <tr>
                <td style="font-size: 10px; color: #1F382E; padding: 0;">
                    <a href="{{ $profile->website ?: 'https://irvincodes.dev' }}" class="footer-link-inline"><strong>Web:</strong> {{ str_replace(['https://', 'http://'], '', $profile->website ?: 'https://irvincodes.dev') }}</a>
                    &nbsp;&nbsp;&bull;&nbsp;&nbsp;
                    <a href="https://github.com/IrvinngB" class="footer-link-inline"><strong>GitHub:</strong> github.com/IrvinngB</a>
                    &nbsp;&nbsp;&bull;&nbsp;&nbsp;
                    <a href="https://www.linkedin.com/in/irvin-benitez-11313231b/" class="footer-link-inline"><strong>LinkedIn:</strong> linkedin.com/in/irvin-benitez</a>
                </td>
            </tr>
        </table>
    </div>
</div>

<div class="bottom-sub-bar"></div>
<div class="bottom-bar"></div>

</body>
</html>
