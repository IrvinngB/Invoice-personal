@php
    $t = function($key) use ($locale) {
        return __("invoice.{$key}", [], $locale);
    };

    // Format dates with Carbon localization
    $formattedIssueDate = $locale === 'es'
        ? $invoice->issue_date->locale('es')->isoFormat('D [de] MMMM [de] Y')
        : $invoice->issue_date->locale('en')->isoFormat('MMMM D, Y');

    $formattedDueDate = $invoice->due_date
        ? ($locale === 'es' ? $invoice->due_date->locale('es')->isoFormat('D [de] MMMM [de] Y') : $invoice->due_date->locale('en')->isoFormat('MMMM D, Y'))
        : null;

    $isQuote = ($invoice->type ?? 'invoice') === 'quote';
    $docTitle = $isQuote 
        ? ($locale === 'es' ? 'Cotización de desarrollo web' : 'Web Development Quote') 
        : ($locale === 'es' ? 'Factura de desarrollo web' : 'Web Development Invoice');

    $formatMoney = function($cents) use ($invoice) {
        return $invoice->currency . ' ' . number_format($cents / 100, 2, '.', ',');
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
            size: {{ $pageSize ?? 'letter' }};
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10.5px;
            color: #111111;
            line-height: 1.45;
            background: #ffffff;
            margin: 0;
            padding: 38px 46px 40px 46px;
        }

        /* Top Header Grid */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .logo-box {
            text-align: left;
            vertical-align: top;
            width: 35%;
        }
        .logo-img {
            max-height: 72px;
            max-width: 160px;
        }
        .title-meta-box {
            text-align: right;
            vertical-align: top;
            width: 65%;
        }
        .main-title {
            font-size: 24px;
            font-weight: 800;
            color: #000000;
            letter-spacing: -0.4px;
            margin-bottom: 12px;
            line-height: 1.1;
        }
        .meta-table {
            float: right;
            border-collapse: collapse;
            font-size: 10px;
        }
        .meta-table td {
            padding: 2.5px 0;
            vertical-align: middle;
        }
        .meta-label {
            color: #737373;
            text-align: right;
            padding-right: 20px;
            white-space: nowrap;
        }
        .meta-value {
            color: #000000;
            font-weight: 700;
            text-align: right;
            white-space: nowrap;
        }

        /* Parties Grid (De / Cobrar a) */
        .parties-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            margin-bottom: 32px;
        }
        .party-col-left {
            width: 50%;
            vertical-align: top;
            text-align: left;
            font-size: 10px;
            line-height: 1.45;
            color: #333333;
        }
        .party-col-right {
            width: 50%;
            vertical-align: top;
            text-align: right;
            font-size: 10px;
            line-height: 1.45;
            color: #333333;
        }
        .section-label {
            font-size: 11px;
            font-weight: 700;
            color: #000000;
            margin-bottom: 5px;
        }
        .party-name {
            font-size: 15px;
            font-weight: 800;
            color: #000000;
            margin-bottom: 5px;
            line-height: 1.2;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            margin-bottom: 26px;
        }
        .items-table thead th {
            background-color: #2E7A42;
            color: #ffffff;
            font-size: 8.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 10px 12px;
            border: none;
        }
        .items-table tbody td {
            padding: 11px 12px;
            font-size: 10px;
            vertical-align: middle;
            color: #111111;
        }
        .row-shaded {
            background-color: #F4FAF0;
        }
        .row-white {
            background-color: #ffffff;
        }
        .col-desc { width: 38%; text-align: left; }
        .col-rate { width: 14%; text-align: right; font-variant-numeric: tabular-nums; }
        .col-qty { width: 11%; text-align: center; font-variant-numeric: tabular-nums; }
        .col-tax { width: 11%; text-align: right; font-variant-numeric: tabular-nums; }
        .col-disc { width: 11%; text-align: right; font-variant-numeric: tabular-nums; }
        .col-amount { width: 15%; text-align: right; font-weight: 600; font-variant-numeric: tabular-nums; }

        .item-title {
            font-weight: 700;
            color: #000000;
            display: block;
        }
        .item-sub {
            font-size: 8.5px;
            color: #555555;
            margin-top: 2px;
            line-height: 1.35;
        }

        /* Bottom Section Grid */
        .bottom-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        .bottom-left-col {
            width: 53%;
            vertical-align: top;
            padding-right: 28px;
        }
        .bottom-right-col {
            width: 47%;
            vertical-align: top;
        }

        .block-title {
            font-size: 11.5px;
            font-weight: 800;
            color: #000000;
            margin-bottom: 6px;
        }
        .info-row-label {
            font-size: 10px;
            color: #222222;
        }
        .info-row-val {
            font-size: 10px;
            color: #555555;
            margin-bottom: 6px;
        }

        /* Financial Breakdown */
        .breakdown-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        .breakdown-table td {
            padding: 3.5px 0;
            vertical-align: middle;
        }
        .breakdown-label {
            color: #222222;
            text-align: left;
        }
        .breakdown-label.bold {
            font-weight: 800;
            color: #000000;
            font-size: 10.5px;
        }
        .breakdown-value {
            text-align: right;
            color: #222222;
            font-variant-numeric: tabular-nums;
        }
        .breakdown-value.bold {
            font-weight: 800;
            color: #000000;
            font-size: 10.5px;
        }

        /* Balance Due Container */
        .balance-container {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background-color: #EAF3E4;
            border-radius: 3px;
        }
        .balance-container td {
            padding: 9px 12px;
            font-weight: 800;
            color: #071F1F;
        }
        .balance-label {
            font-size: 11.5px;
            text-align: left;
        }
        .balance-amount {
            font-size: 12.5px;
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        /* Signature area */
        .signature-box {
            text-align: right;
            margin-top: 22px;
            padding-right: 8px;
        }
        .signature-img {
            height: 48px;
            width: auto;
            display: inline-block;
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <td class="logo-box">
                @if($profile->logo_path && file_exists(public_path($profile->logo_path)))
                    <img src="{{ public_path($profile->logo_path) }}" class="logo-img" alt="Logo">
                @elseif(file_exists(public_path('images/brand-ib.svg')))
                    <img src="{{ public_path('images/brand-ib.svg') }}" style="height: 64px; width: auto;" alt="Logo">
                @else
                    <div style="font-size: 42px; font-weight: 900; line-height: 1; letter-spacing: -2px; color: #2E7A42;">
                        i<span style="color: #B4D333;">B</span>
                    </div>
                @endif
            </td>
            <td class="title-meta-box">
                <div class="main-title">{{ $docTitle }}</div>
                <table class="meta-table">
                    <tr>
                        <td class="meta-label">{{ $locale === 'es' ? 'Orden de compra número.:' : 'Purchase order no.:' }}</td>
                        <td class="meta-value">{{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">{{ $locale === 'es' ? 'Fecha de orden:' : 'Order date:' }}</td>
                        <td class="meta-value">{{ $formattedIssueDate }}</td>
                    </tr>
                    @if($formattedDueDate)
                    <tr>
                        <td class="meta-label">{{ $locale === 'es' ? 'Fecha de vencimiento:' : 'Due date:' }}</td>
                        <td class="meta-value">{{ $formattedDueDate }}</td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <!-- Parties Section (De / Cobrar a) -->
    <table class="parties-table">
        <tr>
            <!-- De (Sender) -->
            <td class="party-col-left">
                <div class="section-label">{{ $locale === 'es' ? 'De' : 'From' }}</div>
                <div class="party-name">{{ $profile->business_name ?: $profile->owner_name }}</div>
                @if($profile->owner_name && $profile->owner_name !== $profile->business_name)
                    <div>{{ $profile->owner_name }}</div>
                @endif
                @if($profile->email)
                    <div>{{ $profile->email }}</div>
                @endif
                @if($profile->phone)
                    <div>{{ $profile->phone }}</div>
                @endif
                @if($profile->website)
                    <div>{{ str_replace(['https://', 'http://'], '', $profile->website) }}</div>
                @endif
                @if($profile->address)
                    <div>{!! nl2br(e($profile->address)) !!}</div>
                @endif
                @if($profile->tax_id)
                    <div>Cédula / RUC: {{ $profile->tax_id }}</div>
                @endif
            </td>

            <!-- Cobrar a (Client) -->
            <td class="party-col-right">
                <div class="section-label">{{ $locale === 'es' ? 'Cobrar a' : 'Bill to' }}</div>
                <div class="party-name">{{ $client->company ?: $client->name }}</div>
                @if($client->company && $client->name)
                    <div>{{ $client->name }}</div>
                @endif
                @if($client->email)
                    <div>{{ $client->email }}</div>
                @endif
                @if($client->phone)
                    <div>{{ $client->phone }}</div>
                @endif
                @if($client->billing_address)
                    <div>{!! nl2br(e($client->billing_address)) !!}</div>
                @endif
                @if($client->tax_id)
                    <div>RUC: {{ $client->tax_id }}</div>
                @endif

                @if($client->shipping_address)
                    <div class="section-label" style="margin-top: 10px;">{{ $locale === 'es' ? 'Envie a' : 'Ship to' }}</div>
                    <div>{!! nl2br(e($client->shipping_address)) !!}</div>
                @endif
            </td>
        </tr>
    </table>

    <!-- Line Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th class="col-desc">{{ $locale === 'es' ? 'DESCRIPCIÓN' : 'DESCRIPTION' }}</th>
                <th class="col-rate">{{ $locale === 'es' ? 'RATE, CADA' : 'RATE, EACH' }}</th>
                <th class="col-qty">{{ $locale === 'es' ? 'CANTIDAD' : 'QUANTITY' }}</th>
                <th class="col-tax">{{ $locale === 'es' ? 'FISCAL' : 'TAX' }}</th>
                <th class="col-disc">{{ $locale === 'es' ? 'DESCT' : 'DISC' }}</th>
                <th class="col-amount">{{ $locale === 'es' ? 'AMOUNT, CADA' : 'AMOUNT, EACH' }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $index => $item)
            @php
                $qty = (float) $item->quantity;
                $formattedQty = ($qty == (int)$qty) ? (string)(int)$qty : rtrim(rtrim(number_format($qty, 2), '0'), '.');
                $rowClass = ($index % 2 === 0) ? 'row-white' : 'row-shaded';
                $unitPrice = number_format($item->unit_price_cents / 100, 2, '.', ',');
                $lineTotal = number_format($item->total_cents / 100, 2, '.', ',');
                $taxPct = number_format(($item->tax_rate_bps ?? 0) / 100, 2) . '%';
                $discPct = number_format(($item->discount_bps ?? 0) / 100, 2) . '%';
            @endphp
            <tr class="{{ $rowClass }}">
                <td class="col-desc">
                    <span class="item-title">{{ $item->description }}</span>
                    @if($item->catalogItem && $item->catalogItem->description && $item->catalogItem->description !== $item->description)
                        <div class="item-sub">{{ $item->catalogItem->description }}</div>
                    @endif
                </td>
                <td class="col-rate">{{ $unitPrice }}</td>
                <td class="col-qty">{{ $formattedQty }}</td>
                <td class="col-tax">{{ $taxPct }}</td>
                <td class="col-disc">{{ $discPct }}</td>
                <td class="col-amount">{{ $lineTotal }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Bottom Section: Payment Instructions + Notes (Left) & Financial Breakdown + Signature (Right) -->
    <table class="bottom-table">
        <tr>
            <!-- Left Column: Instructions and Notes -->
            <td class="bottom-left-col">
                <div class="block-title">{{ $locale === 'es' ? 'Instrucciones de pago' : 'Payment Instructions' }}</div>

                @if($profile->paypal_email)
                    <div class="info-row-label">E-mail de Paypal</div>
                    <div class="info-row-val">{{ $profile->paypal_email }}</div>
                @endif

                @if($profile->bank_account_name || $profile->owner_name)
                    <div class="info-row-label">{{ $locale === 'es' ? 'Cheques a nombre de' : 'Checks payable to' }}</div>
                    <div class="info-row-val">{{ $profile->bank_account_name ?: $profile->owner_name }}</div>
                @endif

                @if($profile->bank_name || $profile->bank_iban)
                    <div class="info-row-label">{{ $locale === 'es' ? 'Transferencia bancaria' : 'Bank Transfer' }}</div>
                    <div class="info-row-val">
                        @if($profile->bank_name)
                            {{ $profile->bank_name }}<br>
                        @endif
                        @if($profile->bank_iban)
                            {{ $locale === 'es' ? 'Cuenta / Cédula ACH:' : 'Account / IBAN:' }} {{ $profile->bank_iban }}<br>
                        @endif
                        @if($profile->bank_swift)
                            {{ $locale === 'es' ? 'Enrutamiento (ABA/SWIFT):' : 'Routing (ABA/SWIFT):' }} {{ $profile->bank_swift }}
                        @endif
                    </div>
                @endif

                @if($invoice->payment_instructions)
                    <div class="info-row-val" style="margin-top: 4px;">
                        {!! nl2br(e($invoice->payment_instructions)) !!}
                    </div>
                @endif

                @if($invoice->notes || $profile->default_notes)
                    <div class="block-title" style="margin-top: 14px;">{{ $locale === 'es' ? 'Notas' : 'Notes' }}</div>
                    <div class="info-row-val" style="line-height: 1.45;">
                        {!! nl2br(e($invoice->notes ?: $profile->default_notes)) !!}
                    </div>
                @endif
            </td>

            <!-- Right Column: Financial Totals Breakdown -->
            <td class="bottom-right-col">
                <table class="breakdown-table">
                    <tr>
                        <td class="breakdown-label bold">{{ $locale === 'es' ? 'Total parcial:' : 'Subtotal:' }}</td>
                        <td class="breakdown-value bold">{{ $formatMoney($invoice->subtotal_cents) }}</td>
                    </tr>
                    @if($invoice->discount_total_cents > 0)
                    <tr>
                        <td class="breakdown-label">{{ $locale === 'es' ? 'Descuento:' : 'Discount:' }}</td>
                        <td class="breakdown-value">-{{ $formatMoney($invoice->discount_total_cents) }}</td>
                    </tr>
                    @endif
                    @if($invoice->shipping_fee_cents > 0)
                    <tr>
                        <td class="breakdown-label">{{ $locale === 'es' ? 'Costo de envío:' : 'Shipping fee:' }}</td>
                        <td class="breakdown-value">{{ $formatMoney($invoice->shipping_fee_cents) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="breakdown-label">{{ $locale === 'es' ? 'Impuesto de venta:' : 'Sales tax:' }}</td>
                        <td class="breakdown-value">{{ $formatMoney($invoice->tax_total_cents) }}</td>
                    </tr>
                    <tr>
                        <td class="breakdown-label bold" style="padding-top: 6px;">{{ $locale === 'es' ? 'Total:' : 'Total:' }}</td>
                        <td class="breakdown-value bold" style="padding-top: 6px; font-size: 11.5px;">{{ $formatMoney($invoice->total_cents) }}</td>
                    </tr>
                    <tr>
                        <td class="breakdown-label">{{ $locale === 'es' ? 'Cantidad pagada:' : 'Amount paid:' }}</td>
                        <td class="breakdown-value">{{ $formatMoney($invoice->amount_paid_cents) }}</td>
                    </tr>
                </table>

                <!-- Balance Due Box -->
                <table class="balance-container">
                    <tr>
                        <td class="balance-label">{{ $locale === 'es' ? 'Saldo adeudado:' : 'Balance due:' }}</td>
                        <td class="balance-amount">{{ $formatMoney($invoice->balance_cents) }}</td>
                    </tr>
                </table>

                <!-- Handwritten Signature Flourish -->
                @if(file_exists(public_path('images/signature-accent.svg')))
                <div class="signature-box">
                    <img src="{{ public_path('images/signature-accent.svg') }}" class="signature-img" alt="Signature">
                </div>
                @endif
            </td>
        </tr>
    </table>

</body>
</html>
