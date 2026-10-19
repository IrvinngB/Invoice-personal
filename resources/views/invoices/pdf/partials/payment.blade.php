@php
    $profile = $ctx->profile;
    $invoice = $ctx->invoice;
    $extra = $ctx->extraPaymentInstructions();
@endphp
@if($ctx->hasPaymentBlock())
    <div class="card">
        <table class="w-full">
            <tr>
                <td>
                    <div class="label">{{ $ctx->t('payment_instructions') }}</div>
                    @if($profile->bank_name)<div class="card-row">{{ $ctx->t('bank') }}: <strong>{{ $profile->bank_name }}</strong></div>@endif
                    @if($profile->bank_account_name)<div class="card-row">{{ $ctx->t('account_name') }}: <strong>{{ $profile->bank_account_name }}</strong></div>@endif
                    @if($profile->bank_iban)<div class="card-row">{{ $ctx->t('iban') }}: <strong>{{ $profile->bank_iban }}</strong></div>@endif
                    @if($profile->bank_swift)<div class="card-row">{{ $ctx->t('swift_bic') }}: <strong>{{ $profile->bank_swift }}</strong></div>@endif
                    @if($profile->paypal_email)<div class="card-row">{{ $ctx->t('paypal') }}: <strong>{{ $profile->paypal_email }}</strong></div>@endif
                    @if($invoice->payment_link)<div class="card-row">{{ $ctx->t('payment_link') }}: <strong>{{ $invoice->payment_link }}</strong></div>@endif
                    @if($extra)<div class="card-row" style="margin-top: 4pt;">{!! nl2br(e($extra)) !!}</div>@endif
                </td>
                @if($ctx->qrCodeUri)
                    <td style="width: 76pt; padding-left: 10pt;">
                        <img src="{{ $ctx->qrCodeUri }}" class="qr" alt="">
                        <div class="qr-caption">{{ $ctx->t('scan_to_pay') }}</div>
                    </td>
                @endif
            </tr>
        </table>
    </div>
@endif
