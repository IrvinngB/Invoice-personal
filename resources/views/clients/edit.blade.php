@extends('layouts.app')

@section('title', 'Edit ' . $client->name)

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('clients.show', $client) }}" class="text-xs font-semibold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                &larr; Back to Client Profile
            </a>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-1">Edit Client</h1>
        </div>
        <form method="POST" action="{{ route('clients.destroy', $client) }}" onsubmit="return confirm('Are you sure you want to delete this client?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-3 py-1.5 rounded-xl text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition">
                Delete Client
            </button>
        </form>
    </div>

    <form method="POST" action="{{ route('clients.update', $client) }}" class="p-6 sm:p-8 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Primary Contact Name <span class="text-rose-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $client->name) }}" required
                    class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Company / Organization</label>
                <input type="text" name="company" value="{{ old('company', $client->company) }}"
                    class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Tax ID / VAT</label>
                <input type="text" name="tax_id" value="{{ old('tax_id', $client->tax_id) }}"
                    class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Email Address</label>
                <input type="email" name="email" value="{{ old('email', $client->email) }}"
                    class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Phone Number</label>
                <input type="text" name="phone" value="{{ old('phone', $client->phone) }}"
                    class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Billing Address</label>
            <textarea name="billing_address" rows="3"
                class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">{{ old('billing_address', $client->billing_address) }}</textarea>
        </div>

        <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-4">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Client Invoicing Defaults</h4>
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">Default Currency</label>
                    <input type="text" name="currency" maxlength="3" value="{{ old('currency', $client->currency) }}" placeholder="USD"
                        class="w-full px-3 py-1.5 text-xs font-bold uppercase rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">Default Tax %</label>
                    <input type="number" step="0.1" name="default_tax_rate_percent" value="{{ old('default_tax_rate_percent', $client->default_tax_rate_bps ? $client->default_tax_rate_bps / 100 : '') }}" placeholder="0"
                        class="w-full px-3 py-1.5 text-xs rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">Payment Days</label>
                    <input type="number" name="default_payment_terms_days" value="{{ old('default_payment_terms_days', $client->default_payment_terms_days) }}" placeholder="14"
                        class="w-full px-3 py-1.5 text-xs rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">Invoice Language</label>
                    <select name="locale" class="w-full px-3 py-1.5 text-xs rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                        <option value="en" {{ old('locale', $client->locale) === 'en' ? 'selected' : '' }}>English (EN)</option>
                        <option value="es" {{ old('locale', $client->locale) === 'es' ? 'selected' : '' }}>Español (ES)</option>
                    </select>
                </div>
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Internal Notes</label>
            <textarea name="notes" rows="2"
                class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">{{ old('notes', $client->notes) }}</textarea>
        </div>

        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100 dark:border-slate-800">
            <a href="{{ route('clients.show', $client) }}" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                Cancel
            </a>
            <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-sm transition active:scale-95">
                Update Client
            </button>
        </div>
    </form>
</div>
@endsection
