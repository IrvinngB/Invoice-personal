@extends('layouts.app')

@section('title', 'Clients')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Clients Directory</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Manage client contact records, default currencies, and billing addresses.</p>
        </div>
        <a href="{{ route('clients.create') }}" class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 shadow-sm transition active:scale-95">
            + New Client
        </a>
    </div>

    <!-- Search Bar -->
    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
        <form method="GET" action="{{ route('clients.index') }}" class="flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search client by name, company, email or tax ID..."
                class="w-full px-4 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500">
            <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition">
                Search
            </button>
            @if(request('search'))
                <a href="{{ route('clients.index') }}" class="px-3 py-2 rounded-xl text-xs text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition flex items-center">
                    Clear
                </a>
            @endif
        </form>
    </div>

    <!-- Clients Table -->
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 text-[10px] uppercase font-bold tracking-wider text-slate-400">
                        <th class="py-3 px-4 sm:px-6">Client / Company</th>
                        <th class="py-3 px-4">Contact</th>
                        <th class="py-3 px-4">Tax ID</th>
                        <th class="py-3 px-4">Invoices</th>
                        <th class="py-3 px-4 text-right">Total Billed</th>
                        <th class="py-3 px-4 text-right">Balance Due</th>
                        <th class="py-3 px-4 sm:px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($clients as $c)
                    <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                        <td class="py-3.5 px-4 sm:px-6">
                            <a href="{{ route('clients.show', $c) }}" class="font-bold text-slate-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400">
                                {{ $c->company ?: $c->name }}
                            </a>
                            @if($c->company && $c->name !== $c->company)
                                <div class="text-[10px] text-slate-400">{{ $c->name }}</div>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-slate-600 dark:text-slate-300">
                            <div>{{ $c->email ?: 'No email' }}</div>
                            <div class="text-[10px] text-slate-400">{{ $c->phone ?: '' }}</div>
                        </td>
                        <td class="py-3.5 px-4 text-slate-500 font-mono text-[11px]">
                            {{ $c->tax_id ?: '-' }}
                        </td>
                        <td class="py-3.5 px-4 font-semibold text-slate-700 dark:text-slate-300">
                            {{ $c->invoices_count }}
                        </td>
                        <td class="py-3.5 px-4 text-right font-bold text-slate-900 dark:text-white">
                            {{ \App\Services\InvoiceCalculationService::formatMoney((int)$c->total_billed_cents, $c->currency ?: 'USD') }}
                        </td>
                        <td class="py-3.5 px-4 text-right font-bold {{ $c->total_balance_cents > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400' }}">
                            {{ \App\Services\InvoiceCalculationService::formatMoney((int)$c->total_balance_cents, $c->currency ?: 'USD') }}
                        </td>
                        <td class="py-3.5 px-4 sm:px-6 text-right space-x-2">
                            <a href="{{ route('invoices.create', ['client_id' => $c->id]) }}" class="text-blue-600 dark:text-blue-400 font-semibold hover:underline" title="Invoice client">
                                + Invoice
                            </a>
                            <a href="{{ route('clients.edit', $c) }}" class="text-slate-400 hover:text-slate-700 dark:hover:text-slate-200">
                                Edit
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400">
                            No clients found. Click "+ New Client" to create your first client.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($clients->hasPages())
        <div class="p-4 border-t border-slate-200 dark:border-slate-800">
            {{ $clients->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
