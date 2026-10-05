@extends('layouts.app')

@section('title', $invoice->invoice_number . ' - ' . ($invoice->client->company ?: $invoice->client->name))

@section('content')
<div x-data="{ paymentModal: false, emailModal: false, voidModal: false }" class="space-y-6">

    <!-- Top Action Bar -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('invoices.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 transition">
                    &larr; Invoices
                </a>
                <span class="text-xs text-slate-400">/</span>
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                    {{ $invoice->type }}
                </span>
            </div>
            <div class="flex items-center space-x-3 mt-1">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                    {{ $invoice->invoice_number }}
                </h1>
                @php
                    $badgeClasses = match($invoice->status) {
                        'paid' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
                        'partially_paid' => 'bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300 border-sky-200 dark:border-sky-800',
                        'sent' => 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300 border-blue-200 dark:border-blue-800',
                        'draft' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-300 dark:border-slate-700',
                        'overdue' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 border-rose-200 dark:border-rose-800',
                        'void' => 'bg-slate-100 text-slate-400 line-through dark:bg-slate-800 dark:text-slate-500 border-slate-200 dark:border-slate-700',
                        default => 'bg-slate-100 text-slate-700',
                    };
                @endphp
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider border {{ $badgeClasses }}">
                    {{ $invoice->status }}
                </span>
            </div>
        </div>

        <!-- Buttons Toolbar -->
        <div class="flex flex-wrap items-center gap-2">
            <!-- Download PDF -->
            <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-sm transition flex items-center">
                <svg class="w-4 h-4 mr-1.5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                PDF / Print
            </a>

            <!-- Send by Email -->
            <button type="button" @click="emailModal = true" class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-sm transition flex items-center">
                <svg class="w-4 h-4 mr-1.5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                Email
            </button>

            <!-- Record Payment (if invoice and not paid/void) -->
            @if($invoice->type === 'invoice' && !$invoice->isPaid() && !$invoice->isVoid())
            <button type="button" @click="paymentModal = true" class="px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition flex items-center active:scale-95">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Record Payment
            </button>
            @endif

            <!-- Edit (only if draft) -->
            @if(!$invoice->isLocked())
            <a href="{{ route('invoices.edit', $invoice) }}" class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                Edit Draft
            </a>
            <form method="POST" action="{{ route('invoices.finalize', $invoice) }}" class="inline">
                @csrf
                <button type="submit" class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-blue-50 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300 hover:bg-blue-100 transition">
                    Finalize & Lock
                </button>
            </form>
            @endif

            <!-- Convert Quote to Invoice -->
            @if($invoice->type === 'quote')
            <form method="POST" action="{{ route('invoices.convert', $invoice) }}" class="inline">
                @csrf
                <button type="submit" class="px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition active:scale-95">
                    Convert to Invoice
                </button>
            </form>
            @endif

            <!-- Issue Credit Note -->
            @if($invoice->type === 'invoice' && $invoice->isFinalized() && !$invoice->isVoid())
            <form method="POST" action="{{ route('invoices.credit-note', $invoice) }}" class="inline">
                @csrf
                <button type="submit" class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 transition">
                    Issue Credit Note
                </button>
            </form>
            @endif

            <!-- Duplicate -->
            <form method="POST" action="{{ route('invoices.duplicate', $invoice) }}" class="inline">
                @csrf
                <button type="submit" class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 transition">
                    Duplicate
                </button>
            </form>

            <!-- Void Button -->
            @if(!$invoice->isVoid())
            <button type="button" @click="voidModal = true" class="px-3 py-2 rounded-xl text-xs font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition">
                Void
            </button>
            @endif
        </div>
    </div>

    @if($invoice->isLocked())
    <div class="p-3 bg-amber-50/70 border border-amber-200 dark:bg-amber-950/30 dark:border-amber-900 text-amber-800 dark:text-amber-300 rounded-xl text-xs flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <span><strong>Locked document:</strong> This document is finalized. Direct modifications are disallowed to protect audit integrity. Use Duplicate or Credit Note for revisions.</span>
        </div>
    </div>
    @endif

    <!-- Main Invoice Paper Preview -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 sm:p-10 shadow-sm space-y-8">

        <!-- Paper Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start gap-6 border-b border-slate-200 dark:border-slate-800 pb-8">
            <div>
                <div class="text-xl font-bold text-slate-900 dark:text-white">{{ $profile->business_name }}</div>
                <div class="text-xs text-slate-500 mt-1 leading-relaxed">
                    @if($profile->owner_name) <div>{{ $profile->owner_name }}</div> @endif
                    @if($profile->tax_id) <div>Tax ID: {{ $profile->tax_id }}</div> @endif
                    <div>{!! nl2br(e($profile->address)) !!}</div>
                    @if($profile->email) <div>{{ $profile->email }}</div> @endif
                </div>
            </div>

            <div class="text-left sm:text-right">
                <div class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                    {{ $invoice->type === 'quote' ? 'Estimate / Quote' : ($invoice->type === 'credit_note' ? 'Credit Note' : 'Invoice') }}
                </div>
                <div class="text-sm font-bold text-blue-600 dark:text-blue-400 mt-0.5">{{ $invoice->invoice_number }}</div>

                <div class="text-xs text-slate-500 mt-4 space-y-1">
                    <div><span class="text-slate-400">Issue Date:</span> <strong class="text-slate-800 dark:text-slate-200">{{ $invoice->issue_date->format('M d, Y') }}</strong></div>
                    <div><span class="text-slate-400">Due Date:</span> <strong class="text-slate-800 dark:text-slate-200">{{ $invoice->due_date->format('M d, Y') }}</strong></div>
                    @if($invoice->parentInvoice)
                        <div><span class="text-slate-400">Ref Doc:</span> <a href="{{ route('invoices.show', $invoice->parentInvoice) }}" class="text-blue-600 hover:underline">{{ $invoice->parentInvoice->invoice_number }}</a></div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Billed to & Terms -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 border-b border-slate-200 dark:border-slate-800 pb-8">
            <div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Billed To</div>
                <div class="text-sm font-bold text-slate-900 dark:text-white">{{ $invoice->client->company ?: $invoice->client->name }}</div>
                <div class="text-xs text-slate-500 mt-1 leading-relaxed">
                    @if($invoice->client->company && $invoice->client->name !== $invoice->client->company)
                        <div>Attn: {{ $invoice->client->name }}</div>
                    @endif
                    @if($invoice->client->tax_id) <div>Tax ID: {{ $invoice->client->tax_id }}</div> @endif
                    @if($invoice->client->billing_address) <div>{!! nl2br(e($invoice->client->billing_address)) !!}</div> @endif
                    @if($invoice->client->email) <div>{{ $invoice->client->email }}</div> @endif
                </div>
            </div>

            <div class="text-left sm:text-right">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Payment Terms & Currency</div>
                <div class="text-xs text-slate-700 dark:text-slate-300">
                    <div>Currency: <strong class="text-slate-900 dark:text-white">{{ $invoice->currency }}</strong> @if($invoice->exchange_rate != 1.0) (Rate: {{ $invoice->exchange_rate }}) @endif</div>
                    <div class="mt-1 text-slate-500">{{ $invoice->payment_terms ?: 'Payment due within 14 days' }}</div>
                </div>
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b-2 border-slate-200 dark:border-slate-700 text-[10px] uppercase font-bold tracking-wider text-slate-400">
                        <th class="py-2.5 px-3">Description</th>
                        <th class="py-2.5 px-3 text-center">Qty</th>
                        <th class="py-2.5 px-3 text-right">Unit Price</th>
                        @if($invoice->tax_total_cents > 0)
                            <th class="py-2.5 px-3 text-right">Tax Rate</th>
                        @endif
                        <th class="py-2.5 px-3 text-right">Line Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($invoice->items as $item)
                    <tr>
                        <td class="py-3 px-3">
                            <div class="font-bold text-slate-900 dark:text-white">{{ $item->description }}</div>
                            @if($item->discount_bps > 0)
                                <div class="text-[10px] text-emerald-600 dark:text-emerald-400">
                                    Discount: {{ $item->discount_bps / 100 }}% (-{{ \App\Services\InvoiceCalculationService::formatMoney($item->discount_amount_cents, $invoice->currency) }})
                                </div>
                            @endif
                        </td>
                        <td class="py-3 px-3 text-center text-slate-600 dark:text-slate-300">
                            {{ rtrim(rtrim(number_format((float)$item->quantity, 4), '0'), '.') }} {{ $item->unit }}
                        </td>
                        <td class="py-3 px-3 text-right font-medium text-slate-900 dark:text-white">
                            {{ $item->formatted_unit_price }}
                        </td>
                        @if($invoice->tax_total_cents > 0)
                        <td class="py-3 px-3 text-right text-slate-500">
                            {{ $item->tax_rate_bps > 0 ? ($item->tax_rate_bps / 100) . '%' : '-' }}
                        </td>
                        @endif
                        <td class="py-3 px-3 text-right font-bold text-slate-900 dark:text-white">
                            {{ $item->formatted_total }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totals & Bank details -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-4 border-t border-slate-200 dark:border-slate-800">
            <div>
                @if($profile->bank_iban || $invoice->payment_link)
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 text-xs space-y-1.5">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Payment Instructions</div>
                    @if($profile->bank_name) <div>Bank: <strong>{{ $profile->bank_name }}</strong></div> @endif
                    @if($profile->bank_iban) <div>IBAN: <strong>{{ $profile->bank_iban }}</strong></div> @endif
                    @if($profile->bank_swift) <div>SWIFT/BIC: <strong>{{ $profile->bank_swift }}</strong></div> @endif
                    @if($profile->paypal_email) <div>PayPal: <strong>{{ $profile->paypal_email }}</strong></div> @endif
                    @if($invoice->payment_link)
                        <div class="pt-1">
                            <a href="{{ $invoice->payment_link }}" target="_blank" class="text-blue-600 dark:text-blue-400 font-semibold hover:underline inline-flex items-center">
                                Pay Online via Stripe &rarr;
                            </a>
                        </div>
                    @endif
                </div>
                @endif
            </div>

            <div class="space-y-2 text-xs">
                <div class="flex justify-between text-slate-600 dark:text-slate-400">
                    <span>Subtotal:</span>
                    <span class="font-semibold text-slate-900 dark:text-white">{{ $invoice->formatted_subtotal }}</span>
                </div>
                @if($invoice->discount_total_cents > 0)
                <div class="flex justify-between text-emerald-600 dark:text-emerald-400">
                    <span>Discounts:</span>
                    <span class="font-semibold">-{{ $invoice->formatted_discount_total }}</span>
                </div>
                @endif
                @if($invoice->tax_total_cents > 0)
                <div class="flex justify-between text-slate-600 dark:text-slate-400">
                    <span>Tax ({{ $invoice->tax_inclusive ? 'Inclusive' : 'Exclusive' }}):</span>
                    <span class="font-semibold text-slate-900 dark:text-white">{{ $invoice->formatted_tax_total }}</span>
                </div>
                @endif
                @if($invoice->shipping_fee_cents > 0)
                <div class="flex justify-between text-slate-600 dark:text-slate-400">
                    <span>Shipping / Fees:</span>
                    <span class="font-semibold text-slate-900 dark:text-white">{{ $invoice->formatted_shipping_fee }}</span>
                </div>
                @endif

                <div class="pt-2 border-t-2 border-slate-200 dark:border-slate-700 flex justify-between items-baseline">
                    <span class="text-sm font-bold text-slate-900 dark:text-white">Total:</span>
                    <span class="text-lg font-black text-slate-900 dark:text-white">{{ $invoice->formatted_total }}</span>
                </div>

                @if($invoice->amount_paid_cents > 0)
                <div class="flex justify-between text-emerald-600 dark:text-emerald-400 font-semibold">
                    <span>Amount Paid:</span>
                    <span>-{{ $invoice->formatted_amount_paid }}</span>
                </div>
                @endif

                <div class="p-3 bg-blue-50 dark:bg-blue-950/40 rounded-xl border border-blue-100 dark:border-blue-900/60 flex justify-between items-center text-blue-900 dark:text-blue-200 font-bold">
                    <span>Balance Due:</span>
                    <span class="text-base">{{ $invoice->formatted_balance }}</span>
                </div>
            </div>
        </div>

        @if($invoice->notes)
        <div class="pt-4 border-t border-slate-200 dark:border-slate-800 text-xs text-slate-600 dark:text-slate-400">
            <div class="font-bold text-slate-700 dark:text-slate-300 mb-1">Notes:</div>
            <div>{!! nl2br(e($invoice->notes)) !!}</div>
        </div>
        @endif
    </div>

    <!-- Payments Ledger & Recording -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Payments Section -->
        <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">Payments Ledger</h3>
                    <p class="text-xs text-slate-400">{{ count($invoice->payments) }} recorded payments</p>
                </div>
                @if($invoice->type === 'invoice' && !$invoice->isPaid() && !$invoice->isVoid())
                <button type="button" @click="paymentModal = true" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 transition">
                    + Record Payment
                </button>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-[10px] uppercase font-bold text-slate-400">
                            <th class="py-2">Date</th>
                            <th class="py-2">Method</th>
                            <th class="py-2">Ref</th>
                            <th class="py-2 text-right">Amount</th>
                            <th class="py-2 text-right"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($invoice->payments as $payment)
                        <tr>
                            <td class="py-2.5 font-medium text-slate-800 dark:text-slate-200">{{ $payment->payment_date->format('M d, Y') }}</td>
                            <td class="py-2.5 text-slate-500 uppercase text-[10px]">{{ str_replace('_', ' ', $payment->method) }}</td>
                            <td class="py-2.5 text-slate-400">{{ $payment->reference ?: '-' }}</td>
                            <td class="py-2.5 text-right font-bold text-emerald-600 dark:text-emerald-400">{{ $payment->formatted_amount }}</td>
                            <td class="py-2.5 text-right">
                                <form method="POST" action="{{ route('payments.destroy', $payment) }}" onsubmit="return confirm('Delete this payment and restore invoice balance?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-slate-400 hover:text-rose-500 transition" title="Delete Payment">
                                        <svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-4 text-center text-slate-400 text-xs">No payments recorded yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Audit Trail Log -->
        <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
            <div>
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">Audit Trail</h3>
                <p class="text-xs text-slate-400">Immutable change log for compliance and integrity</p>
            </div>

            <div class="space-y-3 max-h-60 overflow-y-auto pr-1">
                @forelse($invoice->auditLogs as $log)
                <div class="flex items-start space-x-3 text-xs">
                    <div class="w-2 h-2 rounded-full bg-blue-500 mt-1.5 shrink-0"></div>
                    <div class="flex-1">
                        <div class="font-semibold text-slate-800 dark:text-slate-200">
                            {{ ucfirst(str_replace('_', ' ', $log->event)) }}
                        </div>
                        <div class="text-[10px] text-slate-400">
                            {{ $log->created_at->format('M d, Y H:i:s') }} · IP: {{ $log->user_ip ?: 'Local' }}
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-xs text-slate-400">No events logged yet.</div>
                @endforelse
            </div>
        </div>

    </div>

    <!-- Record Payment Modal -->
    <div x-show="paymentModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4" x-cloak>
        <div @click.away="paymentModal = false" class="max-w-md w-full bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Record Payment</h3>
                <button type="button" @click="paymentModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
            </div>

            <form method="POST" action="{{ route('invoices.payments.store', $invoice) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Amount ({{ $invoice->currency }})</label>
                    <input type="number" step="0.01" min="0.01" name="amount" value="{{ number_format($invoice->balance_cents / 100, 2, '.', '') }}" required
                        class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-bold">
                    <span class="text-[10px] text-slate-400">Remaining Balance: {{ $invoice->formatted_balance }}</span>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Payment Date</label>
                    <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required
                        class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Payment Method</label>
                    <select name="method" required class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                        <option value="bank_transfer">Bank Wire / Transfer</option>
                        <option value="stripe_link">Stripe / Online Card</option>
                        <option value="paypal">PayPal</option>
                        <option value="cash">Cash</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Reference / Transaction ID</label>
                    <input type="text" name="reference" placeholder="e.g. WIRE-894102 or pi_9918"
                        class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Notes</label>
                    <input type="text" name="notes" placeholder="Optional notes..."
                        class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                </div>

                <div class="pt-3 flex justify-end space-x-2">
                    <button type="button" @click="paymentModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-md">Confirm Payment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Send Email Modal -->
    <div x-show="emailModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4" x-cloak>
        <div @click.away="emailModal = false" class="max-w-md w-full bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Email {{ $invoice->invoice_number }}</h3>
                <button type="button" @click="emailModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
            </div>

            <form method="POST" action="{{ route('invoices.send-email', $invoice) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Recipient Email</label>
                    <input type="email" name="email" value="{{ $invoice->client->email }}" required
                        class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-medium">
                </div>
                <div class="text-[11px] text-slate-400 leading-relaxed">
                    The PDF invoice will be automatically generated and attached to this email. You can configure custom SMTP credentials in Studio Settings.
                </div>

                <div class="pt-3 flex justify-end space-x-2">
                    <button type="button" @click="emailModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-md">Send Email Now</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Void Modal -->
    <div x-show="voidModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4" x-cloak>
        <div @click.away="voidModal = false" class="max-w-md w-full bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 p-6 space-y-4">
            <h3 class="text-base font-bold text-rose-600 dark:text-rose-400">Void Document</h3>
            <p class="text-xs text-slate-500">Voiding marks this invoice as cancelled and preserves the number in your sequence without gaps.</p>
            <form method="POST" action="{{ route('invoices.void', $invoice) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Reason for Voiding</label>
                    <input type="text" name="reason" placeholder="e.g. Cancelled by client, duplicated error" required
                        class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                </div>
                <div class="pt-3 flex justify-end space-x-2">
                    <button type="button" @click="voidModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700">Confirm Void</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
