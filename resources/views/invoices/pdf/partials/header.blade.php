@php
    $profile = $ctx->profile;
    $invoice = $ctx->invoice;
    $logo = $ctx->logoPath();
    $status = $ctx->statusColors();
    $style = $ctx->theme['header'];
@endphp

@if($style === 'split')
    <table class="w-full" style="margin-bottom: 26pt;">
        <tr>
            <td style="width: 55%;">
                @if($logo)<img src="{{ $logo }}" class="logo" alt="">@endif
                <div class="biz-name">{{ $profile->business_name }}</div>
                @include('invoices.pdf.partials.seller-meta')
            </td>
            <td class="right" style="width: 45%;">
                <div class="doc-title">{{ $ctx->docTitle() }}</div>
                <div class="doc-number">{{ $invoice->invoice_number }}</div>
                @if($ctx->shows('status'))
                    <div style="margin-top: 7pt;">
                        <span class="status-pill" style="background: {{ $status['bg'] }}; color: {{ $status['fg'] }};">{{ $ctx->t('status_' . $invoice->status) }}</span>
                    </div>
                @endif
            </td>
        </tr>
    </table>

@elseif($style === 'band')
    <table class="w-full band" style="margin-bottom: 22pt;">
        <tr>
            <td style="width: 55%; vertical-align: middle;">
                @if($logo)<img src="{{ $logo }}" class="logo" alt="">@endif
                <div class="biz-name">{{ $profile->business_name }}</div>
                @include('invoices.pdf.partials.seller-meta')
            </td>
            <td class="right" style="width: 45%; vertical-align: middle;">
                <div class="doc-title">{{ $ctx->docTitle() }}</div>
                <div class="doc-number">{{ $invoice->invoice_number }}</div>
                @if($ctx->shows('status'))
                    <div class="biz-meta" style="margin-top: 5pt; text-transform: uppercase; letter-spacing: 0.8pt;">{{ $ctx->t('status_' . $invoice->status) }}</div>
                @endif
            </td>
        </tr>
    </table>

@elseif($style === 'centered')
    <div class="center" style="margin-bottom: 8pt;">
        @if($logo)<img src="{{ $logo }}" class="logo" alt="" style="margin-bottom: 4pt;"><br>@endif
        <div class="biz-name" style="letter-spacing: 3pt; text-transform: uppercase;">{{ $profile->business_name }}</div>
        <div style="margin-top: 3pt;">@include('invoices.pdf.partials.seller-meta', ['inline' => true])</div>
    </div>
    <div class="rule-double"></div>
    <div class="center" style="margin: 14pt 0 22pt 0;">
        <span class="doc-title" style="letter-spacing: 6pt;">{{ $ctx->docTitle() }}</span>
        <span class="doc-number" style="margin-left: 8pt;">N&ordm; {{ $invoice->invoice_number }}</span>
    </div>

@else {{-- stacked --}}
    <table class="w-full" style="margin-bottom: 30pt;">
        <tr>
            <td style="width: 60%;">
                @if($logo)<img src="{{ $logo }}" class="logo" alt="" style="max-height: 30pt;">@endif
                <div class="biz-name" style="text-transform: uppercase; letter-spacing: 1.5pt;">{{ $profile->business_name }}</div>
                @include('invoices.pdf.partials.seller-meta')
            </td>
            <td class="right" style="width: 40%;">
                <div class="label">{{ $ctx->docTitle() }}</div>
                <div class="ink bold" style="font-size: {{ $ctx->theme['size']['total'] }}pt;">{{ $invoice->invoice_number }}</div>
                @if($ctx->shows('status'))
                    <div class="label" style="margin-top: 4pt;">{{ $ctx->t('status_' . $invoice->status) }}</div>
                @endif
            </td>
        </tr>
    </table>
@endif
