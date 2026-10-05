@extends('layouts.app')

@section('title', 'Services & Products Catalog')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Services & Products Catalog</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Pre-configured items for sub-minute line item insertion.</p>
        </div>
        <a href="{{ route('catalog.create') }}" class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 shadow-sm transition active:scale-95">
            + New Catalog Item
        </a>
    </div>

    <!-- Catalog Table -->
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 text-[10px] uppercase font-bold tracking-wider text-slate-400">
                        <th class="py-3 px-4 sm:px-6">Item Name</th>
                        <th class="py-3 px-4">Description</th>
                        <th class="py-3 px-4">Unit</th>
                        <th class="py-3 px-4 text-right">Default Price</th>
                        <th class="py-3 px-4 text-right">Default Tax</th>
                        <th class="py-3 px-4 sm:px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($items as $item)
                    <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                        <td class="py-3.5 px-4 sm:px-6 font-bold text-slate-900 dark:text-white">
                            {{ $item->name }}
                        </td>
                        <td class="py-3.5 px-4 text-slate-500 max-w-xs truncate">
                            {{ $item->description ?: '-' }}
                        </td>
                        <td class="py-3.5 px-4 text-slate-600 dark:text-slate-300">
                            {{ $item->unit }}
                        </td>
                        <td class="py-3.5 px-4 text-right font-bold text-slate-900 dark:text-white">
                            {{ $item->formatted_price }}
                        </td>
                        <td class="py-3.5 px-4 text-right text-slate-500">
                            {{ $item->tax_rate_bps > 0 ? ($item->tax_rate_bps / 100) . '%' : '-' }}
                        </td>
                        <td class="py-3.5 px-4 sm:px-6 text-right space-x-2">
                            <a href="{{ route('catalog.edit', $item) }}" class="text-blue-600 dark:text-blue-400 font-semibold hover:underline">
                                Edit
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400">
                            No catalog items created yet. Add standard services or products here.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
        <div class="p-4 border-t border-slate-200 dark:border-slate-800">
            {{ $items->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
