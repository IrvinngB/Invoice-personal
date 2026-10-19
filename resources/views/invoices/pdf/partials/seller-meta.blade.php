@php
    $profile = $ctx->profile;
    $inline = $inline ?? false;
    $lines = array_values(array_filter([
        ($ctx->shows('seller_owner') && $profile->owner_name && $profile->owner_name !== $profile->business_name) ? $profile->owner_name : null,
        ($ctx->shows('seller_tax_id') && $profile->tax_id) ? $ctx->t('tax_id') . ': ' . $profile->tax_id : null,
        ($ctx->shows('seller_address') && $profile->address) ? preg_replace('/\s*\R\s*/', ', ', trim($profile->address)) : null,
        $ctx->shows('seller_email') ? $profile->email : null,
        $ctx->shows('seller_phone') ? $profile->phone : null,
        ($ctx->shows('seller_website') && $profile->website) ? preg_replace('#^https?://#', '', $profile->website) : null,
    ]));
@endphp
@if($inline)
    <div class="biz-meta">{{ implode('  •  ', $lines) }}</div>
@else
    <div class="biz-meta" style="margin-top: 2pt;">
        @foreach($lines as $line)<div>{{ $line }}</div>@endforeach
    </div>
@endif
