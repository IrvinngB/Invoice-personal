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
    <div class="container">
        <h2>Hello {{ $client->name }},</h2>
        <p>Please find attached <strong>{{ ucfirst($invoice->type) }} {{ $invoice->invoice_number }}</strong> from <strong>{{ $profile->business_name }}</strong>.</p>
        
        <div class="meta">
            <div><strong>Amount Due:</strong> {{ \App\Services\InvoiceCalculationService::formatMoney($invoice->balance_cents, $invoice->currency, $invoice->locale) }}</div>
            <div><strong>Due Date:</strong> {{ $invoice->due_date->format('M d, Y') }}</div>
        </div>

        @if($invoice->payment_link)
            <p><a href="{{ $invoice->payment_link }}" class="btn">Pay Online Now</a></p>
        @endif

        @if($invoice->notes)
            <p><strong>Notes:</strong><br>{{ $invoice->notes }}</p>
        @endif

        <p>Thank you for your business!</p>
        <p>Best regards,<br><strong>{{ $profile->owner_name }}</strong><br>{{ $profile->business_name }}</p>
    </div>
</body>
</html>
