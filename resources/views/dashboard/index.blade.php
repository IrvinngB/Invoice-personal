@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Studio Overview</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Welcome back, {{ $profile->owner_name }}. Here is your financial snapshot.</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('backup.export-csv') }}" class="inline-flex items-center px-3 py-2 rounded-lg text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                <svg class="w-4 h-4 mr-1.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export CSV
            </a>
            <a href="{{ route('invoices.create') }}" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 shadow-sm transition active:scale-95">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Create Invoice
            </a>
        </div>
    </div>

    <!-- Financial Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Invoiced -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Total Invoiced</span>
                <span class="p-2 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z" />
                    </svg>
                </span>
            </div>
            <div class="mt-3 text-2xl font-bold text-slate-900 dark:text-white">
                {{ \App\Services\InvoiceCalculationService::formatMoney($totalInvoicedCents, $currency) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Lifetime all valid invoices</div>
        </div>

        <!-- Total Collected -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Total Collected</span>
                <span class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </span>
            </div>
            <div class="mt-3 text-2xl font-bold text-slate-900 dark:text-white">
                {{ \App\Services\InvoiceCalculationService::formatMoney($totalPaidCents, $currency) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Recorded payments received</div>
        </div>

        <!-- Outstanding Balance -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400">Outstanding</span>
                <span class="p-2 rounded-xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <div class="mt-3 text-2xl font-bold text-slate-900 dark:text-white">
                {{ \App\Services\InvoiceCalculationService::formatMoney($outstandingCents, $currency) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Unpaid balance pending</div>
        </div>

        <!-- Overdue -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-rose-600 dark:text-rose-400">Overdue</span>
                <span class="p-2 rounded-xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </span>
            </div>
            <div class="mt-3 text-2xl font-bold text-rose-600 dark:text-rose-400">
                {{ \App\Services\InvoiceCalculationService::formatMoney($overdueCents, $currency) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Requires client follow-up</div>
        </div>
    </div>

    <!-- Chart & Top Clients Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Revenue Trends Chart -->
        <div class="lg:col-span-2 p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Revenue Trends (Past 12 Months)</h3>
                <span class="text-xs text-slate-400 font-medium">Invoiced vs Collected</span>
            </div>
            <div class="h-64">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <!-- Top Clients -->
        <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">Top Clients</h3>
                <div class="space-y-4">
                    @forelse($topClients as $client)
                        <div class="flex items-center justify-between">
                            <div>
                                <a href="{{ route('clients.show', $client) }}" class="text-sm font-semibold text-slate-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition block">
                                    {{ $client->display_name }}
                                </a>
                                <span class="text-xs text-slate-400">{{ $client->email ?: 'No email' }}</span>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-bold text-slate-900 dark:text-white">
                                    {{ \App\Services\InvoiceCalculationService::formatMoney($client->total_billed_cents ?: 0, $client->currency ?: $currency) }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="text-xs text-slate-400">No client billing records yet.</div>
                    @endforelse
                </div>
            </div>
            <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800">
                <a href="{{ route('clients.index') }}" class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center">
                    Manage all clients &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- Invoices List & Filter Section -->
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
        <!-- Table Filter Bar -->
        <div class="p-4 sm:p-5 border-b border-slate-200/80 dark:border-slate-800">
            <form method="GET" action="{{ route('dashboard') }}" class="flex flex-col md:flex-row gap-3">
                <div class="flex-1 relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by invoice # or client..."
                        class="w-full pl-9 pr-4 py-2 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <div class="flex flex-wrap gap-2">
                    <select name="status" class="py-2 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Statuses</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                        <option value="partially_paid" {{ request('status') === 'partially_paid' ? 'selected' : '' }}>Partially Paid</option>
                        <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                        <option value="void" {{ request('status') === 'void' ? 'selected' : '' }}>Void</option>
                    </select>

                    <select name="type" class="py-2 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Types</option>
                        <option value="invoice" {{ request('type') === 'invoice' ? 'selected' : '' }}>Invoices</option>
                        <option value="quote" {{ request('type') === 'quote' ? 'selected' : '' }}>Quotes / Estimates</option>
                        <option value="credit_note" {{ request('type') === 'credit_note' ? 'selected' : '' }}>Credit Notes</option>
                    </select>

                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition">
                        Filter
                    </button>
                    @if(request()->hasAny(['search', 'status', 'type', 'client_id']))
                        <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-xl text-xs font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Invoices Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="py-3 px-4 sm:px-6">Number & Type</th>
                        <th class="py-3 px-4">Client</th>
                        <th class="py-3 px-4">Issue / Due Date</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Total</th>
                        <th class="py-3 px-4 text-right">Balance</th>
                        <th class="py-3 px-4 sm:px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                    @forelse($recentInvoices as $inv)
                    <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                        <td class="py-3.5 px-4 sm:px-6">
                            <a href="{{ route('invoices.show', $inv) }}" class="font-bold text-blue-600 dark:text-blue-400 hover:underline">
                                {{ $inv->invoice_number }}
                            </a>
                            <div class="text-[10px] uppercase font-semibold text-slate-400 mt-0.5">
                                {{ $inv->type }}
                            </div>
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="font-medium text-slate-900 dark:text-white">{{ $inv->client->company ?: $inv->client->name }}</span>
                            @if($inv->client->company && $inv->client->name !== $inv->client->company)
                                <div class="text-[10px] text-slate-400">{{ $inv->client->name }}</div>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-slate-600 dark:text-slate-300">
                            <div>{{ $inv->issue_date->format('M d, Y') }}</div>
                            <div class="text-[10px] {{ $inv->isOverdue() ? 'text-rose-500 font-bold' : 'text-slate-400' }}">
                                Due: {{ $inv->due_date->format('M d, Y') }}
                            </div>
                        </td>
                        <td class="py-3.5 px-4">
                            @php
                                $badgeClasses = match($inv->status) {
                                    'paid' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
                                    'partially_paid' => 'bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300 border-sky-200 dark:border-sky-800',
                                    'sent' => 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300 border-blue-200 dark:border-blue-800',
                                    'draft' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-300 dark:border-slate-700',
                                    'overdue' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 border-rose-200 dark:border-rose-800 animate-pulse',
                                    'void' => 'bg-slate-100 text-slate-400 line-through dark:bg-slate-800 dark:text-slate-500 border-slate-200 dark:border-slate-700',
                                    default => 'bg-slate-100 text-slate-700',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border {{ $badgeClasses }}">
                                {{ $inv->status }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-right font-semibold text-slate-900 dark:text-white">
                            {{ $inv->formatted_total }}
                        </td>
                        <td class="py-3.5 px-4 text-right font-bold {{ $inv->balance_cents > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400' }}">
                            {{ $inv->formatted_balance }}
                        </td>
                        <td class="py-3.5 px-4 sm:px-6 text-right space-x-2">
                            <a href="{{ route('invoices.pdf', $inv) }}" target="_blank" class="text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 p-1" title="View PDF">
                                <svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </a>
                            <a href="{{ route('invoices.show', $inv) }}" class="text-blue-600 dark:text-blue-400 font-semibold hover:underline">
                                View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400">
                            No invoices match your search filters.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($recentInvoices->hasPages())
        <div class="p-4 border-t border-slate-200 dark:border-slate-800">
            {{ $recentInvoices->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('revenueChart');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($chartLabels) !!},
                datasets: [
                    {
                        label: 'Invoiced',
                        data: {!! json_encode($invoicedData) !!},
                        backgroundColor: 'rgba(59, 130, 246, 0.7)',
                        borderRadius: 6,
                    },
                    {
                        label: 'Collected',
                        data: {!! json_encode($collectedData) !!},
                        backgroundColor: 'rgba(16, 185, 129, 0.8)',
                        borderRadius: 6,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            color: document.documentElement.classList.contains('dark') ? '#cbd5e1' : '#475569',
                            font: { size: 11, family: 'sans-serif' }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: document.documentElement.classList.contains('dark') ? '#94a3b8' : '#64748b', font: { size: 10 } }
                    },
                    y: {
                        border: { dash: [4, 4] },
                        grid: { color: document.documentElement.classList.contains('dark') ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)' },
                        ticks: { color: document.documentElement.classList.contains('dark') ? '#94a3b8' : '#64748b', font: { size: 10 } }
                    }
                }
            }
        });
    });
</script>
@endpush
