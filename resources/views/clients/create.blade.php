@extends('layouts.app')

@section('title', __('app.new_client'))

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <a href="{{ route('clients.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
            &larr; {{ __('app.clients_directory') }}
        </a>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-1">{{ __('app.add_new_client') }}</h1>
    </div>

    <form method="POST" action="{{ route('clients.store') }}" class="p-6 sm:p-8 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-6">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">{{ __('app.primary_contact_name') }} <span class="text-rose-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required
                    class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">{{ __('app.company_organization') }}</label>
                <input type="text" name="company" value="{{ old('company') }}"
                    class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">{{ __('app.tax_id') }}</label>
                <input type="text" name="tax_id" value="{{ old('tax_id') }}"
                    class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">{{ __('app.email_address') }}</label>
                <input type="email" name="email" value="{{ old('email') }}"
                    class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">{{ __('app.phone_number') }}</label>
                <input type="text" name="phone" value="{{ old('phone') }}"
                    class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">{{ __('app.billing_address') }}</label>
            <textarea name="billing_address" rows="3"
                class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">{{ old('billing_address') }}</textarea>
        </div>

        <!-- Invoicing Defaults for this Client -->
        <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-4">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('app.client_invoicing_defaults') }}</h4>
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">{{ __('app.default_currency') }}</label>
                    <input type="text" name="currency" maxlength="3" value="{{ old('currency', $profile->default_currency) }}" placeholder="USD"
                        class="w-full px-3 py-1.5 text-xs font-bold uppercase rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">{{ __('app.default_tax_percent') }}</label>
                    <input type="number" step="0.1" name="default_tax_rate_percent" value="{{ old('default_tax_rate_percent', 0) }}" placeholder="0"
                        class="w-full px-3 py-1.5 text-xs rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">{{ __('app.payment_days') }}</label>
                    <input type="number" name="default_payment_terms_days" value="{{ old('default_payment_terms_days', $profile->default_payment_terms_days) }}" placeholder="14"
                        class="w-full px-3 py-1.5 text-xs rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">{{ __('app.invoice_language') }}</label>
                    <select name="locale" class="w-full px-3 py-1.5 text-xs rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                        <option value="en">English (EN)</option>
                        <option value="es">Español (ES)</option>
                    </select>
                </div>
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">{{ __('app.internal_notes') }}</label>
            <textarea name="notes" rows="2" placeholder="{{ __('app.private_client_notes_placeholder') }}"
                class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">{{ old('notes') }}</textarea>
        </div>

        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100 dark:border-slate-800">
            <a href="{{ route('clients.index') }}" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                {{ __('app.cancel') }}
            </a>
            <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-sm transition active:scale-95">
                {{ __('app.save_client') }}
            </button>
        </div>
    </form>
</div>
@endsection
