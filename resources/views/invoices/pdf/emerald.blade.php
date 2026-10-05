@php
    $t = function($key) use ($locale) {
        return __("invoice.{$key}", [], $locale);
    };
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 0;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            line-height: 1.4;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }
        .top-bar {
            height: 14px;
            background-color: #0b7a42;
            width: 50%;
            margin-left: 50%;
        }
        .page-content {
            padding: 30px 45px 35px 45px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .owner-title {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 1.5px;
            color: #000000;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .owner-id {
            font-size: 16px;
            font-weight: 700;
            color: #222222;
            margin-bottom: 25px;
        }
        .client-section {
            font-size: 11px;
            line-height: 1.45;
            color: #111111;
        }
        .client-name {
            font-weight: 800;
            font-size: 12px;
            margin-bottom: 2px;
        }
        .meta-right {
            text-align: right;
            vertical-align: top;
            font-size: 11px;
            line-height: 1.5;
            color: #222222;
        }
        .invoice-title {
            font-size: 13px;
            font-weight: 700;
            color: #000000;
            margin-bottom: 3px;
        }
        .logo-box {
            text-align: right;
            margin-bottom: 15px;
        }
        .logo-img {
            max-height: 65px;
            max-width: 140px;
        }

        /* Green Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            margin-bottom: 10px;
        }
        .items-table th {
            background-color: #097939;
            color: #ffffff;
            font-size: 10.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 10px 14px;
            border: none;
        }
        .items-table td {
            padding: 12px 14px;
            font-size: 11px;
            vertical-align: middle;
            border-bottom: 1px solid #e5e7eb;
        }
        .col-desc { width: 55%; text-align: left; }
        .col-qty { width: 20%; text-align: center; }
        .col-price { width: 25%; text-align: right; background-color: #edebe9; }

        .items-table th.col-price {
            background-color: #62ab36;
            text-align: right;
        }

        /* Total row */
        .total-box {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            border-top: 2px solid #097939;
            border-bottom: 3px solid #097939;
            background-color: #ffffff;
        }
        .total-box td {
            padding: 11px 14px;
            font-size: 12px;
            font-weight: 800;
        }
        .total-label {
            width: 75%;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #000000;
        }
        .total-amount {
            width: 25%;
            text-align: right;
            background-color: #edebe9;
            color: #000000;
            font-size: 13px;
        }

        /* Footer */
        .footer-section {
            margin-top: 70px;
            font-size: 10px;
            color: #333333;
            line-height: 1.6;
        }
        .footer-heading {
            font-weight: 800;
            text-transform: lowercase;
            margin-bottom: 3px;
        }
        .footer-link {
            color: #111111;
            text-decoration: underline;
            display: block;
        }

        .bottom-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 18px;
            background-color: #6cc24a;
        }
    </style>
</head>
<body>

<div class="top-bar"></div>

<div class="page-content">
    <table class="header-table">
        <tr>
            <td style="width: 55%; vertical-align: top;">
                <div class="owner-title">{{ $profile->owner_name ?: $profile->business_name }}</div>
                @if($profile->tax_id)
                    <div class="owner-id">{{ $profile->tax_id }}</div>
                @endif

                <div class="client-section">
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
                        <img src="{{ public_path('images/brand-ib.svg') }}" style="height: 52px; width: auto; display: inline-block;" alt="iB Logo">
                    @else
                        <!-- Vector iB Logo fallback -->
                        <div style="display: inline-block; font-size: 38px; font-weight: 900; line-height: 1; letter-spacing: -2px;">
                            <span style="color: #097939;">i</span><span style="color: #2b7a42;">B</span>
                        </div>
                    @endif
                </div>

                <div class="invoice-title">Factura No. {{ $invoice->invoice_number }}</div>
                <div>{{ $invoice->issue_date->isoFormat('D MMMM, Y') }}</div>
                @if($profile->address)
                    <div>{!! nl2br(e($profile->address)) !!}</div>
                @endif
                @if($profile->email)
                    <div>{{ $profile->email }}</div>
                @endif
            </td>
        </tr>
    </table>

    <!-- Line items -->
    <table class="items-table">
        <thead>
            <tr>
                <th class="col-desc">DESCRIPCION</th>
                <th class="col-qty">CANTIDAD (HORAS)</th>
                <th class="col-price">PRECIO</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td class="col-desc">{{ $item->description }}</td>
                <td class="col-qty">{{ str_pad((string)(int)$item->quantity, 2, '0', STR_PAD_LEFT) }}</td>
                <td class="col-price">{{ \App\Services\InvoiceCalculationService::formatMoney($item->total_cents, $invoice->currency, $locale) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Total -->
    <table class="total-box">
        <tr>
            <td class="total-label">MONTO TOTAL:</td>
            <td class="total-amount">{{ \App\Services\InvoiceCalculationService::formatMoney($invoice->total_cents, $invoice->currency, $locale) }}</td>
        </tr>
    </table>

    <!-- Contact links -->
    <div class="footer-section">
        <div class="footer-heading">contacto:</div>
        <a href="{{ $profile->website ?: 'https://irvincodes.dev' }}" class="footer-link">Pagina Web</a>
        <a href="https://github.com/IrvinngB" class="footer-link">GitHub</a>
        <a href="https://www.linkedin.com/in/irvin-benitez-11313231b/" class="footer-link">LinkedIn</a>
    </div>
</div>

<div class="bottom-bar"></div>

</body>
</html>
