@extends('layouts.app')

@section('title', 'Recurring Invoices')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Recurring Schedules</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Automate recurring client retainers and monthly billing cycles.</p>
        </div>
        <a href="{{ route('recurring.create') }}" class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 shadow-sm transition active:scale-95">
            + New Recurring Schedule
        </a>
    </div>

    <!-- Recurring Invoices Table -->
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 text-[10px] uppercase font-bold tracking-wider text-slate-400">
                        <th class="py-3 px-4 sm:px-6">Client</th>
                        <th class="py-3 px-4">Frequency</th>
                        <th class="py-3 px-4">Next Issue Date</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Items / Details</th>
                        <th class="py-3 px-4 sm:px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($recurringInvoices as $r)
                    <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                        <td class="py-3.5 px-4 sm:px-6 font-bold text-slate-900 dark:text-white">
                            <a href="{{ route('clients.show', $r->client) }}" class="hover:underline">
                                {{ $r->client->display_name }}
                            </a>
                        </td>
                        <td class="py-3.5 px-4 font-semibold uppercase text-[10px] text-blue-600 dark:text-blue-400">
                            {{ $r->frequency }}
                        </td>
                        <td class="py-3.5 px-4 font-medium text-slate-800 dark:text-slate-200">
                            {{ $r->next_issue_date->format('M d, Y') }}
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border {{ $r->status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200' }}">
                                {{ $r->status }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-slate-500">
                            {{ count($r->template_payload['items'] ?? []) }} line item(s) · {{ $r->template_payload['currency'] ?? 'USD' }}
                        </td>
                        <td class="py-3.5 px-4 sm:px-6 text-right space-x-2">
                            <!-- Run Now -->
                            <form method="POST" action="{{ route('recurring.generate-now', $r) }}" class="inline">
                                @csrf
                                <button type="submit" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 hover:bg-blue-100 transition">
                                    Generate Draft Now
                                </button>
                            </form>

                            <!-- Delete -->
                            <form method="POST" action="{{ route('recurring.destroy', $r) }}" onsubmit="return confirm('Remove this recurring schedule?');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-slate-400 hover:text-rose-500 transition p-1">
                                    <svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400">
                            No recurring invoice schedules. Set up retainers here to generate invoices on schedule.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($recurringInvoices->hasPages())
        <div class="p-4 border-t border-slate-200 dark:border-slate-800">
            {{ $recurringInvoices->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
