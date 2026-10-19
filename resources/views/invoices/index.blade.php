@extends('layouts.app')

@section('title', __('app.invoices_quotes'))

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ __('app.invoice_browser_title') }}</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('app.invoice_browser_subtitle') }}</p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('invoices.create', ['type' => 'quote']) }}" class="px-3.5 py-2 rounded-lg text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                + {{ __('app.new_quote_estimate') }}
            </a>
            <a href="{{ route('invoices.create', ['type' => 'invoice']) }}" class="px-4 py-2 rounded-lg text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 shadow-sm transition active:scale-95">
                + {{ __('app.new_invoice') }}
            </a>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
        <form method="GET" action="{{ route('invoices.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <div class="lg:col-span-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('app.search_invoices') }}"
                    class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <select name="type" class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                    <option value="">{{ __('app.all_types') }}</option>
                    <option value="invoice" {{ request('type') === 'invoice' ? 'selected' : '' }}>{{ __('app.invoices') }}</option>
                    <option value="quote" {{ request('type') === 'quote' ? 'selected' : '' }}>{{ __('invoice.quote') }}</option>
                    <option value="credit_note" {{ request('type') === 'credit_note' ? 'selected' : '' }}>{{ __('invoice.credit_note') }}</option>
                </select>
            </div>
            <div>
                <select name="status" class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                    <option value="">{{ __('app.all_statuses') }}</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>{{ __('app.draft') }}</option>
                    <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>{{ __('app.sent') }}</option>
                    <option value="partially_paid" {{ request('status') === 'partially_paid' ? 'selected' : '' }}>{{ __('app.partially_paid') }}</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>{{ __('app.paid') }}</option>
                    <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>{{ __('app.overdue') }}</option>
                    <option value="void" {{ request('status') === 'void' ? 'selected' : '' }}>{{ __('app.void_status') }}</option>
                </select>
            </div>
            <div>
                <select name="client_id" class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                    <option value="">{{ __('app.all_clients') }}</option>
                    @foreach($clients as $c)
                        <option value="{{ $c->id }}" {{ request('client_id') == $c->id ? 'selected' : '' }}>{{ $c->display_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center space-x-2">
                <button type="submit" class="w-full py-2 px-3 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition">
                    {{ __('app.filter') }}
                </button>
                @if(request()->hasAny(['search', 'type', 'status', 'client_id']))
                    <a href="{{ route('invoices.index') }}" class="py-2 px-2.5 rounded-xl text-xs text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition">
                        {{ __('app.reset') }}
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="py-3 px-4 sm:px-6">{{ __('app.document_number') }}</th>
                        <th class="py-3 px-4">{{ __('app.client') }}</th>
                        <th class="py-3 px-4">{{ __('app.issue_date') }}</th>
                        <th class="py-3 px-4">{{ __('app.due_date') }}</th>
                        <th class="py-3 px-4">{{ __('app.status') }}</th>
                        <th class="py-3 px-4 text-right">{{ __('app.total') }}</th>
                        <th class="py-3 px-4 text-right">{{ __('app.balance_due') }}</th>
                        <th class="py-3 px-4 sm:px-6 text-right">{{ __('app.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                    @forelse($invoices as $inv)
                    <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                        <td class="py-3.5 px-4 sm:px-6">
                            <a href="{{ route('invoices.show', $inv) }}" class="font-bold text-blue-600 dark:text-blue-400 hover:underline">
                                {{ $inv->invoice_number }}
                            </a>
                            <div class="text-[10px] uppercase font-semibold text-slate-400 mt-0.5">
                                {{ __('invoice.' . $inv->type) }}
                            </div>
                        </td>
                        <td class="py-3.5 px-4 font-medium text-slate-900 dark:text-white">
                            {{ $inv->client->company ?: $inv->client->name }}
                        </td>
                        <td class="py-3.5 px-4 text-slate-500">
                            {{ $inv->issue_date->locale(app()->getLocale())->isoFormat('ll') }}
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="{{ $inv->isOverdue() ? 'text-rose-500 font-bold' : 'text-slate-500' }}">
                                {{ $inv->due_date->locale(app()->getLocale())->isoFormat('ll') }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4">
                            @php
                                $badgeClasses = match($inv->status) {
                                    'paid' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
                                    'partially_paid' => 'bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300 border-sky-200 dark:border-sky-800',
                                    'sent' => 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300 border-blue-200 dark:border-blue-800',
                                    'draft' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-300 dark:border-slate-700',
                                    'overdue' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 border-rose-200 dark:border-rose-800',
                                    'void' => 'bg-slate-100 text-slate-400 line-through dark:bg-slate-800 dark:text-slate-500 border-slate-200 dark:border-slate-700',
                                    default => 'bg-slate-100 text-slate-700',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border {{ $badgeClasses }}">
                                {{ __('app.' . ($inv->status === 'void' ? 'void_status' : $inv->status)) }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-right font-semibold text-slate-900 dark:text-white">
                            {{ $inv->formatted_total }}
                        </td>
                        <td class="py-3.5 px-4 text-right font-bold {{ $inv->balance_cents > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400' }}">
                            {{ $inv->formatted_balance }}
                        </td>
                        <td class="py-3.5 px-4 sm:px-6 text-right space-x-2">
                            <a href="{{ route('invoices.pdf', $inv) }}" target="_blank" class="text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 p-1" title="{{ __('app.pdf_print') }}">
                                <svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </a>
                            <a href="{{ route('invoices.show', $inv) }}" class="text-blue-600 dark:text-blue-400 font-semibold hover:underline">
                                {{ __('app.view') }}
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-12 text-center text-slate-400">
                            {{ __('app.no_invoices_yet') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($invoices->hasPages())
        <div class="p-4 border-t border-slate-200 dark:border-slate-800">
            {{ $invoices->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
