<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; color: #334155; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #2563eb; color: #ffffff !important; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 15px; }
        .meta { margin: 20px 0; padding: 15px; background: #f8fafc; border-radius: 6px; }
    </style>
</head>
<body>
    @php
        $t = fn ($key, $replace = []) => __("invoice.{$key}", $replace, $locale);
        $documentType = $invoice->type === 'credit_note' ? $t('credit_note') : $t($invoice->type);
        $formattedDueDate = $invoice->due_date?->locale($locale)->isoFormat($locale === 'es' ? 'D [de] MMMM [de] Y' : 'MMMM D, Y');
    @endphp
    <div class="container">
        <h2>{{ $t('mail_greeting', ['name' => $client->name]) }}</h2>
        <p>{{ $t('mail_attached', ['type' => $documentType, 'number' => $invoice->invoice_number, 'business' => $profile->business_name]) }}</p>
        
        <div class="meta">
            <div><strong>{{ $t('mail_amount_due') }}:</strong> {{ \App\Services\InvoiceCalculationService::formatMoney($invoice->balance_cents, $invoice->currency, $locale) }}</div>
            <div><strong>{{ $t('due_date') }}:</strong> {{ $formattedDueDate }}</div>
        </div>

        @if($invoice->payment_link)
            <p><a href="{{ $invoice->payment_link }}" class="btn">{{ $t('mail_pay_online') }}</a></p>
        @endif

        @if($invoice->notes)
            <p><strong>{{ $t('notes') }}:</strong><br>{{ $invoice->notes }}</p>
        @endif

        <p>{{ $t('thank_you') }}</p>
        <p>{{ $t('mail_best_regards') }}<br><strong>{{ $profile->owner_name }}</strong><br>{{ $profile->business_name }}</p>
    </div>
</body>
</html>
