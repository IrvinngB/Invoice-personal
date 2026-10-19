@php
    $invoice = $ctx->invoice;
    $showTax = $invoice->tax_total_cents > 0;
    $count = count($invoice->items);
@endphp
<table class="items" style="margin-bottom: 18pt;">
    <thead>
        <tr>
            <th style="width: {{ $showTax ? 42 : 52 }}%;">{{ $ctx->t('description') }}</th>
            <th class="qty" style="width: 11%;">{{ $ctx->t('qty') }}</th>
            <th class="num" style="width: 16%;">{{ $ctx->t('price') }}</th>
            @if($showTax)<th class="num" style="width: 10%;">{{ $ctx->t('tax') }}</th>@endif
            <th class="num" style="width: 21%;">{{ $ctx->t('amount') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoice->items as $item)
            <tr class="{{ $loop->even ? 'alt' : '' }} {{ $loop->last ? 'last' : '' }}">
                <td>
                    <div class="item-title">{{ $item->description }}</div>
                    @if($item->discount_bps > 0)
                        <div class="item-sub">{{ $ctx->t('discount') }}: {{ number_format($item->discount_bps / 100, 1) }}% (-{{ $ctx->money($item->discount_amount_cents) }})</div>
                    @endif
                </td>
                <td class="qty">{{ $ctx->quantity($item->quantity) }} <span class="muted small">{{ $item->unit }}</span></td>
                <td class="num">{{ $ctx->money($item->unit_price_cents) }}</td>
                @if($showTax)
                    <td class="num">{{ $item->tax_rate_bps > 0 ? number_format($item->tax_rate_bps / 100, 1) . '%' : '—' }}</td>
                @endif
                <td class="num ink bold">{{ $ctx->money($item->total_cents) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
