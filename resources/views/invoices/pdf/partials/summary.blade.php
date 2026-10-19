@php
    $invoice = $ctx->invoice;
    $positive = $ctx->theme['positive'];
@endphp
<table class="summary">
    <tr><td class="k">{{ $ctx->t('subtotal') }}</td><td class="v">{{ $ctx->money($invoice->subtotal_cents) }}</td></tr>
    @if($invoice->discount_total_cents > 0)
        <tr><td class="k">{{ $ctx->t('discount') }}</td><td class="v" style="color: {{ $positive }};">-{{ $ctx->money($invoice->discount_total_cents) }}</td></tr>
    @endif
    @if($invoice->shipping_fee_cents > 0)
        <tr><td class="k">{{ $ctx->t('shipping_fee') }}</td><td class="v">{{ $ctx->money($invoice->shipping_fee_cents) }}</td></tr>
    @endif
    @if($invoice->tax_total_cents > 0)
        <tr>
            <td class="k">{{ $ctx->t('tax') }} <span class="small">({{ $invoice->tax_inclusive ? $ctx->t('tax_included') : $ctx->t('tax_excluded') }})</span></td>
            <td class="v">{{ $ctx->money($invoice->tax_total_cents) }}</td>
        </tr>
    @endif
    <tr class="sep"><td class="k">{{ $ctx->t('total') }}</td><td class="v">{{ $ctx->money($invoice->total_cents) }}</td></tr>
    @if($invoice->amount_paid_cents > 0)
        <tr><td class="k">{{ $ctx->t('amount_paid') }}</td><td class="v" style="color: {{ $positive }};">-{{ $ctx->money($invoice->amount_paid_cents) }}</td></tr>
    @endif
</table>
<table class="balance">
    <tr>
        <td class="k">{{ $ctx->t('balance_due') }}</td>
        <td class="v">{{ $ctx->money($invoice->balance_cents) }}</td>
    </tr>
</table>

@if($sig = $ctx->signaturePath())
    <div style="margin-top: 22pt; text-align: right;">
        <img src="{{ $sig }}" class="signature-img" alt="">
        <div class="signature-line">{{ $ctx->t('authorized_signature') }}</div>
    </div>
@endif
