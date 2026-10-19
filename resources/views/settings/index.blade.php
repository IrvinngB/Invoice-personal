@extends('layouts.app')

@section('title', __('app.settings_title'))

@section('content')
<div class="max-w-4xl mx-auto space-y-8">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ __('app.settings_heading') }}</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('app.settings_subtitle') }}</p>
    </div>

    <!-- Invoice Themes entry point -->
    <a href="{{ route('settings.themes.index') }}" class="flex items-center justify-between gap-4 p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm hover:border-blue-400 transition group">
        <div>
            <div class="text-sm font-bold text-slate-900 dark:text-white">{{ __('app.themes_heading') }}</div>
            <div class="text-xs text-slate-500 dark:text-slate-400">{{ __('app.themes_subtitle') }}</div>
        </div>
        <span class="px-4 py-2 text-xs font-semibold rounded-xl bg-blue-600 group-hover:bg-blue-700 text-white whitespace-nowrap transition">{{ __('app.themes_manage') }} &rarr;</span>
    </a>

    <!-- Main Settings Form -->
    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Business Identity Card -->
        <div class="p-6 sm:p-8 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">1. {{ __('app.business_profile') }}</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">{{ __('app.business_name') }} <span class="text-rose-500">*</span></label>
                    <input type="text" name="business_name" placeholder="Acme Studio LLC" value="{{ old('business_name', $profile->business_name) }}" required
                        class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-bold">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">{{ __('app.owner_name') }}</label>
                    <input type="text" name="owner_name" placeholder="John Doe" value="{{ old('owner_name', $profile->owner_name) }}"
                        class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">{{ __('app.tax_id') }}</label>
                    <input type="text" name="tax_id" placeholder="0-000-000-0" value="{{ old('tax_id', $profile->tax_id) }}"
                        class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono">
                </div>
                <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">{{ __('app.business_email') }}</label>
                    <input type="email" name="email" placeholder="hello@example.com" value="{{ old('email', $profile->email) }}"
                        class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">{{ __('app.phone') }}</label>
                    <input type="text" name="phone" placeholder="+1 555 000 0000" value="{{ old('phone', $profile->phone) }}"
                        class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">{{ __('app.website_url') }}</label>
                    <input type="url" name="website" value="{{ old('website', $profile->website) }}" placeholder="https://www.example.com"
                        class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                </div>
                <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">{{ __('app.timezone') }}</label>
                    <input type="text" name="timezone" placeholder="America/New_York" value="{{ old('timezone', $profile->timezone) }}" required
                        class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">{{ __('app.business_address') }}</label>
                <textarea name="address" placeholder="123 Main Street&#10;City, Country" rows="3"
                    class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">{{ old('address', $profile->address) }}</textarea>
            </div>

            <!-- Logo & Signature Upload -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-2 border-t border-slate-100 dark:border-slate-800">
                <!-- Logo Upload -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">{{ __('app.business_logo') }}</label>
                    <div class="flex items-center space-x-4">
                        @if($profile->logo_path && file_exists(public_path($profile->logo_path)))
                            <img src="{{ asset($profile->logo_path) }}" class="h-12 w-auto max-w-[120px] rounded-lg border border-slate-200 p-1 bg-white">
                        @endif
                        <input type="file" name="logo" accept="image/*"
                            class="text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                </div>

                <!-- Signature Upload & Option -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('app.business_signature') }}</label>
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="show_signature" value="1" {{ old('show_signature', $profile->show_signature) ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500">
                            <span class="ml-2 text-xs font-medium text-slate-700 dark:text-slate-300">{{ __('app.show_signature') }}</span>
                        </label>
                    </div>
                    <div class="flex items-center space-x-4">
                        @if($profile->signature_path && file_exists(public_path($profile->signature_path)))
                            <img src="{{ asset($profile->signature_path) }}" class="h-10 w-auto max-w-[120px] rounded-lg border border-slate-200 p-1 bg-white">
                        @else
                            <span class="text-[10px] font-semibold text-amber-600 dark:text-amber-400 border border-dashed border-amber-400 rounded-lg px-2 py-1.5">{{ __('app.signature_missing') }}</span>
                        @endif
                        <input type="file" name="signature" accept="image/*"
                            class="text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">{{ __('app.show_signature_description') }}</p>
                </div>
            </div>
        </div>

        <!-- Bank Details Card -->
        <div class="p-6 sm:p-8 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">2. {{ __('app.payment_instructions') }}</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">{{ __('app.bank_name') }}</label>
                    <input type="text" name="bank_name" placeholder="Example Bank" value="{{ old('bank_name', $profile->bank_name) }}"
                        class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">{{ __('app.account_holder_name') }}</label>
                    <input type="text" name="bank_account_name" placeholder="John Doe" value="{{ old('bank_account_name', $profile->bank_account_name) }}"
                        class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">{{ __('app.account_number') }}</label>
                    <input type="text" name="bank_iban" placeholder="0000-0000-0000-0000" value="{{ old('bank_iban', $profile->bank_iban) }}"
                        class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">{{ __('app.swift_code') }}</label>
                    <input type="text" name="bank_swift" placeholder="EXAMPLEXXX" value="{{ old('bank_swift', $profile->bank_swift) }}"
                        class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">{{ __('app.paypal_email') }}</label>
                    <input type="email" name="paypal_email" placeholder="payments@example.com" value="{{ old('paypal_email', $profile->paypal_email) }}"
                        class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">{{ __('app.payment_url') }}</label>
                    <input type="url" name="payment_link" value="{{ old('payment_link', $profile->payment_link) }}" placeholder="https://buy.stripe.com/..."
                        class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">{{ __('app.invoice_footer') }}</label>
                <textarea name="default_footer" rows="2"
                    class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">{{ old('default_footer', $profile->default_footer) }}</textarea>
            </div>
        </div>

        <!-- Numbering & Defaults Card -->
        <div class="p-6 sm:p-8 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">3. {{ __('app.document_sequences') }}</h3>

            <!-- Numbering Sequences -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Invoices -->
                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-2">
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 block">{{ __('app.invoice_sequence') }}</span>
                    <div>
                        <label class="block text-[10px] text-slate-400">{{ __('app.prefix_optional') }}</label>
                        <input type="text" name="invoice_prefix" placeholder="INV-2026-" value="{{ old('invoice_prefix', $profile->invoice_prefix) }}"
                            class="w-full px-2 py-1 text-xs rounded bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] text-slate-400">{{ __('app.digits') }}</label>
                            <input type="number" name="invoice_number_digits" value="{{ old('invoice_number_digits', $profile->invoice_number_digits) }}" required
                                class="w-full px-2 py-1 text-xs rounded bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-[10px] text-slate-400">{{ __('app.next_number') }}</label>
                            <input type="number" name="next_invoice_number" value="{{ old('next_invoice_number', $profile->next_invoice_number) }}" required
                                class="w-full px-2 py-1 text-xs rounded bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-bold text-blue-600">
                        </div>
                    </div>
                </div>

                <!-- Quotes -->
                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-2">
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 block">{{ __('app.quote_sequence') }}</span>
                    <div>
                        <label class="block text-[10px] text-slate-400">{{ __('app.prefix_optional') }}</label>
                        <input type="text" name="quote_prefix" placeholder="QUO-2026-" value="{{ old('quote_prefix', $profile->quote_prefix) }}"
                            class="w-full px-2 py-1 text-xs rounded bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] text-slate-400">{{ __('app.digits') }}</label>
                            <input type="number" name="quote_number_digits" value="{{ old('quote_number_digits', $profile->quote_number_digits) }}" required
                                class="w-full px-2 py-1 text-xs rounded bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-[10px] text-slate-400">{{ __('app.next_number') }}</label>
                            <input type="number" name="next_quote_number" value="{{ old('next_quote_number', $profile->next_quote_number) }}" required
                                class="w-full px-2 py-1 text-xs rounded bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-bold text-blue-600">
                        </div>
                    </div>
                </div>

                <!-- Credit Notes -->
                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-2">
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 block">{{ __('app.credit_notes') }}</span>
                    <div>
                        <label class="block text-[10px] text-slate-400">{{ __('app.prefix_optional') }}</label>
                        <input type="text" name="credit_note_prefix" placeholder="CN-2026-" value="{{ old('credit_note_prefix', $profile->credit_note_prefix) }}"
                            class="w-full px-2 py-1 text-xs rounded bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] text-slate-400">{{ __('app.digits') }}</label>
                            <input type="number" name="credit_note_number_digits" value="{{ old('credit_note_number_digits', $profile->credit_note_number_digits) }}" required
                                class="w-full px-2 py-1 text-xs rounded bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-[10px] text-slate-400">{{ __('app.next_number') }}</label>
                            <input type="number" name="next_credit_note_number" value="{{ old('next_credit_note_number', $profile->next_credit_note_number) }}" required
                                class="w-full px-2 py-1 text-xs rounded bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-bold text-blue-600">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Document Render Defaults -->
            <div class="grid grid-cols-1 sm:grid-cols-5 gap-4 pt-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">{{ __('app.default_currency') }}</label>
                    <input type="text" name="default_currency" maxlength="3" value="{{ old('default_currency', $profile->default_currency) }}" required
                        class="w-full px-3 py-2 text-xs font-bold uppercase rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">{{ __('app.payment_days') }}</label>
                    <input type="number" name="default_payment_terms_days" value="{{ old('default_payment_terms_days', $profile->default_payment_terms_days) }}" required
                        class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">{{ __('app.pdf_template') }}</label>
                    <select name="default_template" class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                        @foreach($pdfTemplates as $tplKey => $tplLabel)
                            <option value="{{ $tplKey }}" {{ old('default_template', $profile->default_template) === $tplKey ? 'selected' : '' }}>{{ $tplLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <a href="{{ route('settings.themes.index') }}" class="text-xs font-semibold text-blue-600 hover:underline">{{ __('app.themes_manage') }} &rarr;</a>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">{{ __('app.paper_size') }}</label>
                    <select name="default_page_size" class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                        <option value="a4" {{ old('default_page_size', $profile->default_page_size) === 'a4' ? 'selected' : '' }}>A4</option>
                        <option value="letter" {{ old('default_page_size', $profile->default_page_size) === 'letter' ? 'selected' : '' }}>US Letter</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">{{ __('app.default_language') }}</label>
                    <select name="default_locale" class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                        <option value="es" {{ old('default_locale', $profile->default_locale) === 'es' ? 'selected' : '' }}>Español (ES)</option>
                        <option value="en" {{ old('default_locale', $profile->default_locale) === 'en' ? 'selected' : '' }}>English (EN)</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end">
            <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-600/20 transition active:scale-95">
                {{ __('app.save_profile_changes') }}
            </button>
        </div>
    </form>

    <!-- Data Safety & Backup Section -->
    <div class="p-6 sm:p-8 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-6">
        <div>
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">4. {{ __('app.backups_exports') }}</h3>
            <p class="text-xs text-slate-500 mt-1">{{ __('app.backups_exports_subtitle') }}</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- 1-Click SQLite Snapshot -->
            <div class="p-5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 space-y-2">
                <div class="font-bold text-xs text-slate-900 dark:text-white flex items-center">
                    <svg class="w-4 h-4 mr-1.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    {{ __('app.download_snapshot') }}
                </div>
                <p class="text-[11px] text-slate-500">{{ __('app.download_snapshot_description') }}</p>
                <div class="pt-2">
                    <a href="{{ route('backup.download-sqlite') }}" class="inline-flex items-center px-3.5 py-1.5 rounded-lg text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 text-slate-800 dark:text-slate-200">
                        {{ __('app.download_sqlite_backup') }}
                    </a>
                </div>
            </div>

            <!-- JSON Data Export -->
            <div class="p-5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 space-y-2">
                <div class="font-bold text-xs text-slate-900 dark:text-white flex items-center">
                    <svg class="w-4 h-4 mr-1.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                    </svg>
                    {{ __('app.export_json') }}
                </div>
                <p class="text-[11px] text-slate-500">{{ __('app.export_json_description') }}</p>
                <div class="pt-2">
                    <a href="{{ route('backup.export-json') }}" class="inline-flex items-center px-3.5 py-1.5 rounded-lg text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 text-slate-800 dark:text-slate-200">
                        {{ __('app.export_full_json') }}
                    </a>
                </div>
            </div>

            <!-- Accountant CSV Report -->
            <div class="p-5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 space-y-2">
                <div class="font-bold text-xs text-slate-900 dark:text-white flex items-center">
                    <svg class="w-4 h-4 mr-1.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    {{ __('app.accountant_csv') }}
                </div>
                <p class="text-[11px] text-slate-500">{{ __('app.accountant_csv_description') }}</p>
                <form method="GET" action="{{ route('backup.export-csv') }}" class="flex items-center gap-2 pt-2">
                    <input type="date" name="from_date" class="px-2 py-1 text-xs rounded bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700">
                    <span class="text-xs text-slate-400">{{ __('app.to') }}</span>
                    <input type="date" name="to_date" class="px-2 py-1 text-xs rounded bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700">
                    <button type="submit" class="px-3 py-1 text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg shadow-sm">
                        {{ __('app.export_csv') }}
                    </button>
                </form>
            </div>

            <!-- Restore SQLite Snapshot -->
            <div class="p-5 rounded-xl border border-rose-200 dark:border-rose-900/40 bg-rose-50/20 dark:bg-rose-950/10 space-y-2">
                <div class="font-bold text-xs text-rose-700 dark:text-rose-400 flex items-center">
                    <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    {{ __('app.restore_database') }}
                </div>
                <p class="text-[11px] text-slate-500">{{ __('app.restore_database_description') }}</p>
                <form method="POST" action="{{ route('backup.restore-sqlite') }}" enctype="multipart/form-data" onsubmit="return confirm(@js(__('app.restore_confirmation')));" class="flex items-center gap-2 pt-1">
                    @csrf
                    <input type="file" name="backup_file" required class="text-[11px] text-slate-500">
                    <button type="submit" class="px-3 py-1 text-xs font-semibold bg-rose-600 hover:bg-rose-700 text-white rounded-lg shrink-0">
                        {{ __('app.restore') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
