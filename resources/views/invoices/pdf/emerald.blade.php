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

    $docTitle = $invoice->type === 'quote'
        ? $t('quote')
        : ($invoice->type === 'credit_note' ? $t('credit_note') : $t('invoice'));

    $formatMoney = function($cents) use ($invoice, $locale) {
        return \App\Services\InvoiceCalculationService::formatMoney($cents, $invoice->currency, $locale);
    };

    $showSig = $showSignature ?? ($invoice->show_signature ?? ($profile->show_signature ?? false));
    $customSig = !empty($signaturePath) && file_exists(public_path($signaturePath)) ? public_path($signaturePath) : null;
    $defaultSig = file_exists(public_path('images/signature-accent.svg')) ? public_path('images/signature-accent.svg') : null;
    $activeSig = $customSig ?: $defaultSig;
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 14mm 16mm 14mm 16mm;
            size: {{ $pageSize ?? 'letter' }};
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #1e293b;
            line-height: 1.45;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }

        /* Top Header Grid */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .logo-box {
            text-align: left;
            vertical-align: middle;
            width: 45%;
        }
        .logo-img {
            max-height: 64px;
            max-width: 180px;
        }
        .title-meta-box {
            text-align: right;
            vertical-align: middle;
            width: 55%;
        }
        .main-title {
            font-size: 24px;
            font-weight: 800;
            color: #0d2818;
            letter-spacing: -0.5px;
            margin-bottom: 6px;
            line-height: 1.1;
        }
        .meta-table {
            float: right;
            border-collapse: collapse;
            font-size: 9.5px;
        }
        .meta-table td {
            padding: 2px 0;
            vertical-align: middle;
        }
        .meta-label {
            color: #64748b;
            text-align: right;
            padding-right: 14px;
            white-space: nowrap;
        }
        .meta-value {
            color: #0f172a;
            font-weight: 700;
            text-align: right;
            white-space: nowrap;
        }

        /* Parties Grid (De / Cobrar a) */
        .parties-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 12px 0;
            margin-left: -12px;
            margin-right: -12px;
            margin-bottom: 22px;
        }
        .party-col {
            width: 50%;
            vertical-align: top;
        }
        .party-card {
            background-color: #f8faf7;
            border: 1px solid #e1ece0;
            border-radius: 5px;
            padding: 10px 14px 12px 14px;
            min-height: 105px;
            font-size: 9.5px;
            line-height: 1.45;
            color: #334155;
        }
        .party-badge {
            display: inline-block;
            font-size: 8px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #2E7A42;
            margin-bottom: 4px;
        }
        .party-name {
            font-size: 13.5px;
            font-weight: 800;
            color: #0d2818;
            margin-bottom: 4px;
            line-height: 1.25;
        }
        .party-detail {
            color: #475569;
        }
        .party-tax {
            margin-top: 4px;
            font-weight: 600;
            color: #1e293b;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 22px;
            border-bottom: 2px solid #2E7A42;
        }
        .items-table thead th {
            background-color: #2E7A42;
            color: #ffffff;
            font-size: 8.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 8px 10px;
            border: none;
        }
        .items-table tbody td {
            padding: 9px 10px;
            font-size: 9.5px;
            vertical-align: middle;
            color: #1e293b;
            border-top: 1px solid #edf4ec;
        }
        .row-shaded {
            background-color: #f8fbf7;
        }
        .row-white {
            background-color: #ffffff;
        }
        .col-desc { width: 40%; text-align: left; }
        .col-rate { width: 13%; text-align: right; font-variant-numeric: tabular-nums; }
        .col-qty { width: 11%; text-align: center; font-variant-numeric: tabular-nums; }
        .col-tax { width: 11%; text-align: right; font-variant-numeric: tabular-nums; }
        .col-disc { width: 10%; text-align: right; font-variant-numeric: tabular-nums; }
        .col-amount { width: 15%; text-align: right; font-weight: 700; font-variant-numeric: tabular-nums; color: #0d2818; }

        .item-title {
            font-weight: 700;
            color: #0f172a;
            display: block;
        }
        .item-sub {
            font-size: 8.5px;
            color: #64748b;
            margin-top: 2px;
            line-height: 1.35;
        }

        /* Bottom Section Grid */
        .bottom-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            margin-bottom: 20px;
        }
        .bottom-left-col {
            width: 54%;
            vertical-align: top;
            padding-right: 20px;
        }
        .bottom-right-col {
            width: 46%;
            vertical-align: top;
        }

        /* Payment Card */
        .payment-card {
            background-color: #f8faf7;
            border: 1px solid #e1ece0;
            border-radius: 5px;
            padding: 11px 14px;
            margin-bottom: 12px;
        }
        .block-title {
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #2E7A42;
            margin-bottom: 8px;
        }
        .payment-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .payment-info-cell {
            vertical-align: top;
        }
        .payment-qr-cell {
            vertical-align: top;
            text-align: right;
            width: 72px;
            padding-left: 10px;
        }
        .qr-img {
            width: 64px;
            height: 64px;
            display: inline-block;
        }
        .qr-label {
            font-size: 7px;
            font-weight: 700;
            color: #64748b;
            text-align: center;
            margin-top: 2px;
            text-transform: uppercase;
        }

        .info-row-label {
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.4px;
        }
        .info-row-val {
            font-size: 9.5px;
            color: #1e293b;
            margin-bottom: 6px;
            line-height: 1.35;
        }

        /* Notes Card */
        .notes-card {
            background-color: #fbfdfa;
            border: 1px solid #e6eee5;
            border-left: 3px solid #2E7A42;
            border-radius: 4px;
            padding: 9px 12px;
        }
        .notes-title {
            font-size: 8.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #2E7A42;
            margin-bottom: 4px;
        }
        .notes-content {
            font-size: 9px;
            color: #334155;
            line-height: 1.45;
        }

        /* Financial Breakdown */
        .breakdown-card {
            background: #ffffff;
            border: 1px solid #e2ece0;
            border-radius: 5px;
            padding: 10px 14px;
        }
        .breakdown-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
        }
        .breakdown-table td {
            padding: 3px 0;
            vertical-align: middle;
        }
        .breakdown-label {
            color: #475569;
            text-align: left;
        }
        .breakdown-label.bold {
            font-weight: 800;
            color: #0f172a;
            font-size: 10.5px;
        }
        .breakdown-value {
            text-align: right;
            color: #1e293b;
            font-variant-numeric: tabular-nums;
            font-weight: 600;
        }
        .breakdown-value.bold {
            font-weight: 800;
            color: #0d2818;
            font-size: 11px;
        }
        .breakdown-divider td {
            border-top: 1px dashed #cbd5e1;
            padding-top: 6px;
            margin-top: 4px;
        }

        /* Balance Due Container */
        .balance-container {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            background-color: #EAF3E4;
            border: 1px solid #cce1c7;
            border-radius: 4px;
        }
        .balance-container td {
            padding: 8px 10px;
            font-weight: 800;
            color: #072814;
        }
        .balance-label {
            font-size: 10.5px;
            text-align: left;
        }
        .balance-amount {
            font-size: 13px;
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        /* Signature area */
        .signature-box {
            text-align: right;
            margin-top: 14px;
            padding-right: 6px;
        }
        .signature-img {
            height: 42px;
            width: auto;
            display: inline-block;
        }
        .signature-caption {
            font-size: 8px;
            color: #64748b;
            margin-top: 2px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Grounding Footer */
        .page-footer {
            width: 100%;
            border-top: 1px solid #e1ece0;
            padding-top: 8px;
            margin-top: 15px;
            font-size: 8.5px;
            color: #64748b;
        }
        .page-footer td {
            vertical-align: middle;
        }
        .footer-left {
            text-align: left;
        }
        .footer-right {
            text-align: right;
            font-weight: 700;
            color: #2E7A42;
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
                    <img src="{{ public_path('images/brand-ib.svg') }}" style="height: 56px; width: auto;" alt="Logo">
                @else
                    <div style="font-size: 38px; font-weight: 900; line-height: 1; letter-spacing: -2px; color: #2E7A42;">
                        i<span style="color: #B4D333;">B</span>
                    </div>
                @endif
            </td>
            <td class="title-meta-box">
                <div class="main-title">{{ $docTitle }}</div>
                <table class="meta-table">
                    <tr>
                        <td class="meta-label">{{ $t('purchase_order_number') }}:</td>
                        <td class="meta-value">{{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">{{ $t('order_date') }}:</td>
                        <td class="meta-value">{{ $formattedIssueDate }}</td>
                    </tr>
                    @if($formattedDueDate)
                    <tr>
                        <td class="meta-label">{{ $t('due_date') }}:</td>
                        <td class="meta-value">{{ $formattedDueDate }}</td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <!-- Parties Section (De / Cobrar a en tarjetas estructuradas) -->
    <table class="parties-table">
        <tr>
            <!-- De (Sender) -->
            <td class="party-col">
                <div class="party-card">
                    <span class="party-badge">{{ $t('from') }}</span>
                    <div class="party-name">{{ $profile->business_name ?: $profile->owner_name }}</div>
                    @if($profile->owner_name && $profile->owner_name !== $profile->business_name)
                        <div class="party-detail">{{ $profile->owner_name }}</div>
                    @endif
                    @if($profile->email)
                        <div class="party-detail">{{ $profile->email }}</div>
                    @endif
                    @if($profile->phone)
                        <div class="party-detail">{{ $profile->phone }}</div>
                    @endif
                    @if($profile->website)
                        <div class="party-detail">{{ str_replace(['https://', 'http://'], '', $profile->website) }}</div>
                    @endif
                    @if($profile->address)
                        <div class="party-detail">{!! nl2br(e($profile->address)) !!}</div>
                    @endif
                    @if($profile->tax_id)
                        <div class="party-tax">{{ $t('tax_id') }}: {{ $profile->tax_id }}</div>
                    @endif
                </div>
            </td>

            <!-- Cobrar a (Client) -->
            <td class="party-col">
                <div class="party-card">
                    <span class="party-badge">{{ $t('billed_to') }}</span>
                    <div class="party-name">{{ $client->company ?: $client->name }}</div>
                    @if($client->company && $client->name)
                        <div class="party-detail">{{ $client->name }}</div>
                    @endif
                    @if($client->email)
                        <div class="party-detail">{{ $client->email }}</div>
                    @endif
                    @if($client->phone)
                        <div class="party-detail">{{ $client->phone }}</div>
                    @endif
                    @if($client->billing_address)
                        <div class="party-detail">{!! nl2br(e($client->billing_address)) !!}</div>
                    @endif
                    @if($client->tax_id)
                        <div class="party-tax">{{ $t('tax_id') }}: {{ $client->tax_id }}</div>
                    @endif
                    @if($client->shipping_address)
                        <div style="margin-top: 6px; padding-top: 4px; border-top: 1px dashed #cbd5e1;">
                            <span class="party-badge">{{ $t('ship_to') }}</span>
                            <div class="party-detail">{!! nl2br(e($client->shipping_address)) !!}</div>
                        </div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- Line Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th class="col-desc">{{ $t('description') }}</th>
                <th class="col-rate">{{ $t('rate_each') }}</th>
                <th class="col-qty">{{ $t('quantity') }}</th>
                <th class="col-tax">{{ $t('tax') }}</th>
                <th class="col-disc">{{ $t('discount_abbr') }}</th>
                <th class="col-amount">{{ $t('amount_each') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $index => $item)
            @php
                $qty = (float) $item->quantity;
                $formattedQty = ($qty == (int)$qty) ? (string)(int)$qty : rtrim(rtrim(number_format($qty, 2), '0'), '.');
                $rowClass = ($index % 2 === 0) ? 'row-white' : 'row-shaded';
                $unitPrice = $formatMoney($item->unit_price_cents);
                $lineTotal = $formatMoney($item->total_cents);
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
                <div class="payment-card">
                    <div class="block-title">{{ $t('payment_instructions') }}</div>
                    <table class="payment-grid">
                        <tr>
                            <td class="payment-info-cell">
                                @if($profile->bank_name || $profile->bank_iban)
                                    <div class="payment-method-block">
                                        <div class="info-row-label">{{ $t('bank_transfer') }}</div>
                                        <div class="info-row-val">
                                            @if($profile->bank_name)
                                                <strong>{{ $profile->bank_name }}</strong><br>
                                            @endif
                                            @if($profile->bank_iban)
                                                <span style="color: #64748b;">{{ $t('account_iban') }}:</span> <strong>{{ $profile->bank_iban }}</strong><br>
                                            @endif
                                            @if($profile->bank_account_name || $profile->owner_name)
                                                <span style="color: #64748b;">{{ $t('account_name') }}:</span> {{ $profile->bank_account_name ?: $profile->owner_name }}<br>
                                            @endif
                                            @if($profile->bank_swift)
                                                <span style="color: #64748b;">{{ $t('routing') }}:</span> {{ $profile->bank_swift }}
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                @if($profile->paypal_email)
                                    <div class="payment-method-block" style="margin-top: 6px;">
                                        <div class="info-row-label">{{ $t('paypal') }}</div>
                                        <div class="info-row-val"><strong>{{ $profile->paypal_email }}</strong></div>
                                    </div>
                                @endif

                                @php
                                    $extraInstructions = trim($invoice->payment_instructions ?? '');
                                    $isDuplicate = in_array($extraInstructions, array_map('trim', array_filter([
                                        $profile->bank_iban,
                                        $profile->bank_name,
                                        $profile->paypal_email,
                                        $profile->tax_id,
                                        $profile->bank_account_name,
                                    ])), true);
                                @endphp
                                @if($extraInstructions && !$isDuplicate)
                                    <div class="payment-method-block" style="margin-top: 6px;">
                                        <div class="info-row-val" style="color: #475569;">
                                            {!! nl2br(e($extraInstructions)) !!}
                                        </div>
                                    </div>
                                @endif
                            </td>
                            @if(!empty($qrCodeSvg))
                            <td class="payment-qr-cell">
                                <img src="{{ $qrCodeSvg }}" class="qr-img" alt="QR Code">
                                <div class="qr-label">{{ $t('scan_to_pay') }}</div>
                            </td>
                            @endif
                        </tr>
                    </table>
                </div>

                @if($invoice->notes || $profile->default_notes)
                <div class="notes-card">
                    <div class="notes-title">{{ $t('notes') }}</div>
                    <div class="notes-content">
                        {!! nl2br(e($invoice->notes ?: $profile->default_notes)) !!}
                    </div>
                </div>
                @endif
            </td>

            <!-- Right Column: Financial Totals Breakdown -->
            <td class="bottom-right-col">
                <div class="breakdown-card">
                    <table class="breakdown-table">
                        <tr>
                            <td class="breakdown-label bold">{{ $t('subtotal') }}:</td>
                            <td class="breakdown-value bold">{{ $formatMoney($invoice->subtotal_cents) }}</td>
                        </tr>
                        @if($invoice->discount_total_cents > 0)
                        <tr>
                            <td class="breakdown-label">{{ $t('discount') }}:</td>
                            <td class="breakdown-value">-{{ $formatMoney($invoice->discount_total_cents) }}</td>
                        </tr>
                        @endif
                        @if($invoice->shipping_fee_cents > 0)
                        <tr>
                            <td class="breakdown-label">{{ $t('shipping_fee') }}:</td>
                            <td class="breakdown-value">{{ $formatMoney($invoice->shipping_fee_cents) }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="breakdown-label">{{ $t('sales_tax') }}:</td>
                            <td class="breakdown-value">{{ $formatMoney($invoice->tax_total_cents) }}</td>
                        </tr>
                        <tr class="breakdown-divider">
                            <td class="breakdown-label bold">{{ $t('total') }}:</td>
                            <td class="breakdown-value bold">{{ $formatMoney($invoice->total_cents) }}</td>
                        </tr>
                        <tr>
                            <td class="breakdown-label">{{ $t('amount_paid') }}:</td>
                            <td class="breakdown-value">{{ $formatMoney($invoice->amount_paid_cents) }}</td>
                        </tr>
                    </table>

                    <!-- Balance Due Box -->
                    <table class="balance-container">
                        <tr>
                            <td class="balance-label">{{ $t('balance_due') }}:</td>
                            <td class="balance-amount">{{ $formatMoney($invoice->balance_cents) }}</td>
                        </tr>
                    </table>
                </div>

                <!-- Handwritten Signature Flourish (Optional) -->
                @if($showSig && $activeSig)
                <div class="signature-box">
                    <img src="{{ $activeSig }}" class="signature-img" alt="Signature">
                    <div class="signature-caption">{{ $t('authorized_signature') ?? 'Firma autorizada' }}</div>
                </div>
                @endif
            </td>
        </tr>
    </table>

    <!-- Grounding Footer -->
    <table class="page-footer">
        <tr>
            <td class="footer-left">
                {{ $profile->business_name ?: $profile->owner_name }}
                @if($profile->website)
                    • {{ str_replace(['https://', 'http://'], '', $profile->website) }}
                @elseif($profile->email)
                    • {{ $profile->email }}
                @endif
            </td>
            <td class="footer-right">
                {{ $t('thank_you') }}
            </td>
        </tr>
    </table>

</body>
</html>
