@php
    $profile = $ctx->profile;
    $invoice = $ctx->invoice;
    $notes = $ctx->shows('notes') ? ($invoice->notes ?: $profile->default_notes) : null;
    $watermark = $ctx->watermark();
@endphp
<!DOCTYPE html>
<html lang="{{ $ctx->locale }}">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
    @include('invoices.pdf.partials.styles')
</head>
<body>
    @if($watermark)
        <div class="watermark" style="color: {{ $watermark['color'] }};">{{ $watermark['text'] }}</div>
    @endif

    @include('invoices.pdf.partials.header')
    @include('invoices.pdf.partials.parties')
    @include('invoices.pdf.partials.items')

    <table class="w-full">
        <tr>
            <td style="width: 52%; padding-right: 22pt;">
                @include('invoices.pdf.partials.payment')
            </td>
            <td style="width: 48%;">
                @include('invoices.pdf.partials.summary')
            </td>
        </tr>
    </table>

    @if($notes)
        <div style="margin-top: 22pt;">
            <div class="label">{{ $ctx->t('notes') }}</div>
            <div class="party-detail">{!! nl2br(e($notes)) !!}</div>
        </div>
    @endif

    @if($ctx->shows('footer'))
    <table class="page-footer w-full">
        <tr>
            <td>{{ $profile->business_name ?: $profile->owner_name }}@if($profile->default_footer) &nbsp;•&nbsp; {{ preg_replace('/\s*\R\s*/', ' • ', trim($profile->default_footer)) }}@endif</td>
            <td class="right">{{ $ctx->t('thank_you') }}</td>
        </tr>
    </table>
    @endif
</body>
</html>
