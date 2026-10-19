@extends('layouts.app')

@section('title', $client->display_name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('clients.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                    &larr; {{ __('app.clients') }}
                </a>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-1">
                {{ $client->display_name }}
            </h1>
        </div>

        <div class="flex items-center space-x-2">
            <a href="{{ route('clients.edit', $client) }}" class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                {{ __('app.edit_client') }}
            </a>
            <a href="{{ route('invoices.create', ['client_id' => $client->id]) }}" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-sm transition active:scale-95">
                + {{ __('app.new_invoice') }}
            </a>
        </div>
    </div>

    <!-- Client Info & Stats Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Client Details Card -->
        <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('app.client_details') }}</h3>
            <div class="space-y-3 text-xs">
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">{{ __('app.primary_contact') }}</span>
                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ $client->name }}</span>
                </div>
                @if($client->company)
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">{{ __('app.company') }}</span>
                    <span class="font-medium text-slate-800 dark:text-slate-200">{{ $client->company }}</span>
                </div>
                @endif
                @if($client->tax_id)
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">{{ __('app.tax_id') }}</span>
                    <span class="font-mono text-slate-800 dark:text-slate-200">{{ $client->tax_id }}</span>
                </div>
                @endif
                @if($client->email)
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">{{ __('app.email') }}</span>
                    <a href="mailto:{{ $client->email }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $client->email }}</a>
                </div>
                @endif
                @if($client->phone)
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">{{ __('app.phone') }}</span>
                    <span class="text-slate-800 dark:text-slate-200">{{ $client->phone }}</span>
                </div>
                @endif
                @if($client->billing_address)
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">{{ __('app.billing_address') }}</span>
                    <div class="text-slate-700 dark:text-slate-300 leading-relaxed mt-0.5">{!! nl2br(e($client->billing_address)) !!}</div>
                </div>
                @endif
                @if($client->notes)
                <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">{{ __('app.notes') }}</span>
                    <div class="text-slate-500 italic mt-0.5">{!! nl2br(e($client->notes)) !!}</div>
                </div>
                @endif
            </div>
        </div>

        <!-- Financial Summary Cards -->
        <div class="lg:col-span-2 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __('app.total_billed') }}</div>
                    <div class="text-xl font-bold text-slate-900 dark:text-white mt-2">
                        {{ \App\Services\InvoiceCalculationService::formatMoney($totalBilled, $client->currency ?: 'USD') }}
                    </div>
                </div>
                <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">{{ __('app.total_collected') }}</div>
                    <div class="text-xl font-bold text-emerald-600 dark:text-emerald-400 mt-2">
                        {{ \App\Services\InvoiceCalculationService::formatMoney($totalPaid, $client->currency ?: 'USD') }}
                    </div>
                </div>
                <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">{{ __('app.current_balance_due') }}</div>
                    <div class="text-xl font-bold text-amber-600 dark:text-amber-400 mt-2">
                        {{ \App\Services\InvoiceCalculationService::formatMoney($balanceDue, $client->currency ?: 'USD') }}
                    </div>
                </div>
            </div>

            <!-- Invoices Table for this Client -->
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('app.client_invoices') }}</h3>
                    <span class="text-xs text-slate-400 font-semibold">{{ $invoices->total() }} {{ __('app.documents') }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-[10px] uppercase font-bold text-slate-400">
                                <th class="py-2.5 px-4">{{ __('app.number') }}</th>
                                <th class="py-2.5 px-3">{{ __('app.date') }}</th>
                                <th class="py-2.5 px-3">{{ __('app.status') }}</th>
                                <th class="py-2.5 px-3 text-right">{{ __('app.total') }}</th>
                                <th class="py-2.5 px-3 text-right">{{ __('app.balance') }}</th>
                                <th class="py-2.5 px-4 text-right">{{ __('app.action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($invoices as $inv)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                <td class="py-3 px-4 font-bold text-blue-600 dark:text-blue-400">
                                    <a href="{{ route('invoices.show', $inv) }}" class="hover:underline">
                                        {{ $inv->invoice_number }}
                                    </a>
                                </td>
                                <td class="py-3 px-3 text-slate-500">{{ $inv->issue_date->locale(app()->getLocale())->isoFormat('ll') }}</td>
                                <td class="py-3 px-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border
                                        {{ $inv->status === 'paid' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-700 border-slate-300' }}">
                                        {{ __('app.' . $inv->status) }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-right font-semibold text-slate-900 dark:text-white">{{ $inv->formatted_total }}</td>
                                <td class="py-3 px-3 text-right font-bold text-amber-600 dark:text-amber-400">{{ $inv->formatted_balance }}</td>
                                <td class="py-3 px-4 text-right">
                                    <a href="{{ route('invoices.show', $inv) }}" class="text-blue-600 hover:underline font-semibold">{{ __('app.view') }}</a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-slate-400 text-xs">{{ __('app.no_client_invoices') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($invoices->hasPages())
                <div class="p-3 border-t border-slate-200 dark:border-slate-800">
                    {{ $invoices->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
