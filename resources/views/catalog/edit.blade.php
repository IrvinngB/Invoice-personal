@extends('layouts.app')

@section('title', 'Edit ' . $item->name)

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('catalog.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                &larr; Catalog Directory
            </a>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-1">Edit Item</h1>
        </div>
        <form method="POST" action="{{ route('catalog.destroy', $item) }}" onsubmit="return confirm('Remove this item from catalog?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-3 py-1.5 rounded-xl text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition">
                Remove Item
            </button>
        </form>
    </div>

    <form method="POST" action="{{ route('catalog.update', $item) }}" class="p-6 sm:p-8 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Item / Service Name <span class="text-rose-500">*</span></label>
            <input type="text" name="name" value="{{ old('name', $item->name) }}" required
                class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Description</label>
            <textarea name="description" rows="2"
                class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">{{ old('description', $item->description) }}</textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Unit of Measure <span class="text-rose-500">*</span></label>
                <input type="text" name="unit" value="{{ old('unit', $item->unit) }}" required
                    class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Unit Price ($) <span class="text-rose-500">*</span></label>
                <input type="number" step="0.01" min="0" name="unit_price" value="{{ old('unit_price', number_format($item->unit_price_cents / 100, 2, '.', '')) }}" required
                    class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-right font-bold">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Default Tax %</label>
                <input type="number" step="0.1" min="0" max="100" name="tax_rate_percent" value="{{ old('tax_rate_percent', $item->tax_rate_bps ? $item->tax_rate_bps / 100 : '') }}"
                    class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-right">
            </div>
        </div>

        <div class="pt-2">
            <label class="inline-flex items-center text-xs text-slate-700 dark:text-slate-300 cursor-pointer">
                <input type="checkbox" name="is_active" value="1" {{ $item->is_active ? 'checked' : '' }}
                    class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 mr-2">
                <span>Active item</span>
            </label>
        </div>

        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100 dark:border-slate-800">
            <a href="{{ route('catalog.index') }}" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                Cancel
            </a>
            <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-sm transition active:scale-95">
                Update Item
            </button>
        </div>
    </form>
</div>
@endsection
