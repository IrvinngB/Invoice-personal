<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function store(Request $request, Invoice $invoice)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'method' => 'required|string|in:bank_transfer,paypal,stripe_link,cash,card,other',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $amountCents = (int) round(((float)$request->amount) * 100);

        $invoice->recordPayment(
            $amountCents,
            $request->payment_date,
            $request->method,
            $request->reference,
            $request->notes
        );

        return back()->with('success', 'Payment recorded successfully.');
    }

    public function destroy(Payment $payment)
    {
        $invoice = $payment->invoice;

        DB::transaction(function () use ($payment, $invoice) {
            $deletedAmount = $payment->amount_cents;
            $payment->delete();

            $invoice->recalculateTotals();

            AuditLog::log($invoice->id, 'payment_deleted', [
                'amount_cents' => $deletedAmount,
                'new_balance' => $invoice->balance_cents,
                'new_status' => $invoice->status,
            ]);
        });

        return back()->with('success', 'Payment deleted and invoice balance recalculated.');
    }
}
