@php
    $invoice = $ctx->invoice;
    $client = $invoice->client;
    $showExchange = $invoice->exchange_rate && (float) $invoice->exchange_rate != 1.0;
    $hasShipping = (bool) $client->shipping_address;
@endphp
<table class="w-full" style="margin-bottom: 24pt;">
    <tr>
        <td style="width: {{ $hasShipping ? 28 : 46 }}%; padding-right: 14pt;">
            <div class="label">{{ $ctx->t('billed_to') }}</div>
            <div class="party-name">{{ $client->company ?: $client->name }}</div>
            <div class="party-detail">
                @if($client->company && $client->name !== $client->company)<div>{{ $ctx->t('attention') }}: {{ $client->name }}</div>@endif
                @if($client->tax_id)<div>{{ $ctx->t('tax_id') }}: {{ $client->tax_id }}</div>@endif
                @if($client->billing_address)<div>{!! nl2br(e($client->billing_address)) !!}</div>@endif
                @if($client->email)<div>{{ $client->email }}</div>@endif
                @if($client->phone)<div>{{ $client->phone }}</div>@endif
            </div>
        </td>
        @if($hasShipping)
            <td style="width: 28%; padding-right: 14pt;">
                <div class="label">{{ $ctx->t('ship_to') }}</div>
                <div class="party-detail">{!! nl2br(e($client->shipping_address)) !!}</div>
            </td>
        @endif
        <td style="width: {{ $hasShipping ? 44 : 54 }}%;">
            <table class="meta-table w-full">
                <tr><td class="meta-key">{{ $ctx->t('issue_date') }}</td><td class="meta-val">{{ $ctx->date($invoice->issue_date) }}</td></tr>
                @if($invoice->due_date)
                    <tr><td class="meta-key">{{ $ctx->t('due_date') }}</td><td class="meta-val">{{ $ctx->date($invoice->due_date) }}</td></tr>
                @endif
                <tr>
                    <td class="meta-key">{{ $ctx->t('currency') }}</td>
                    <td class="meta-val">{{ $invoice->currency }}@if($showExchange) <span class="muted small" style="font-weight: normal;">({{ $ctx->t('rate') }} {{ $invoice->exchange_rate }})</span>@endif</td>
                </tr>
                @if($invoice->payment_terms)
                    <tr><td class="meta-key">{{ $ctx->t('payment_terms') }}</td><td class="meta-val">{{ $invoice->payment_terms }}</td></tr>
                @endif
                @if($invoice->parentInvoice)
                    <tr><td class="meta-key">{{ $ctx->t('reference') }}</td><td class="meta-val">{{ $invoice->parentInvoice->invoice_number }}</td></tr>
                @endif
            </table>
        </td>
    </tr>
</table>
