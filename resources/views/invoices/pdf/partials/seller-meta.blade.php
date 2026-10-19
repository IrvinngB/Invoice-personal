@php
    $profile = $ctx->profile;
    $inline = $inline ?? false;
    $lines = array_values(array_filter([
        ($profile->owner_name && $profile->owner_name !== $profile->business_name) ? $profile->owner_name : null,
        $profile->tax_id ? $ctx->t('tax_id') . ': ' . $profile->tax_id : null,
        $profile->address ? preg_replace('/\s*\R\s*/', ', ', trim($profile->address)) : null,
        $profile->email,
        $profile->phone,
        $profile->website ? preg_replace('#^https?://#', '', $profile->website) : null,
    ]));
@endphp
@if($inline)
    <div class="biz-meta">{{ implode('  •  ', $lines) }}</div>
@else
    <div class="biz-meta" style="margin-top: 2pt;">
        @foreach($lines as $line)<div>{{ $line }}</div>@endforeach
    </div>
@endif
