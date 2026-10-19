@extends('layouts.app')

@section('title', __('app.create_document', ['type' => __('invoice.' . $type)]))

@section('content')
<div x-data="invoiceBuilder({
    clients: {{ Js::from($clients) }},
    catalogItems: {{ Js::from($catalogItems) }},
    defaultCurrency: '{{ $profile->default_currency }}',
    defaultTermsDays: {{ $profile->default_payment_terms_days }},
    defaultTaxInclusive: {{ $profile->default_tax_inclusive ? 'true' : 'false' }},
    defaultNotes: {{ Js::from($profile->default_notes) }},
    defaultFooter: {{ Js::from($profile->default_footer) }},
    defaultPaymentInstructions: {{ Js::from($profile->bank_iban ? "Bank: {$profile->bank_name}\nIBAN: {$profile->bank_iban}\nSWIFT: {$profile->bank_swift}" : '') }},
    defaultTemplate: '{{ $profile->default_template ?: "modern" }}',
    defaultPageSize: '{{ $profile->default_page_size ?: "letter" }}',
    defaultLocale: '{{ $profile->default_locale ?: "es" }}',
    defaultShowSignature: {{ $profile->show_signature ? 'true' : 'false' }},
    initialClientId: '{{ $selectedClientId ?: '' }}',
    sourceInvoice: {{ Js::from($sourceInvoice) }}
})" class="space-y-6">

    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('invoices.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 transition">
                    &larr; {{ __('app.back_to_invoices') }}
                </a>
                <span class="text-xs text-slate-400">/</span>
                <span class="text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400">
                    {{ __('app.new_document', ['type' => __('invoice.' . $type)]) }}
                </span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-1">
                {{ __('app.draft') }} {{ $previewNumber }}
            </h1>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('invoices.index') }}" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                {{ __('app.cancel') }}
            </a>
            <button type="button" @click="submitForm()" class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-600/20 transition active:scale-95 flex items-center">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
                {{ __('app.save_document', ['type' => __('invoice.' . $type)]) }}
            </button>
        </div>
    </div>

    <form id="invoiceForm" method="POST" action="{{ route('invoices.store') }}" @keydown.window.prevent.ctrl.s="submitForm()" @keydown.window.prevent.meta.s="submitForm()">
        @csrf
        <input type="hidden" name="type" value="{{ $type }}">
        @if(request('parent_invoice_id'))
            <input type="hidden" name="parent_invoice_id" value="{{ request('parent_invoice_id') }}">
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left 2 Cols: Form Content -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Client & Dates Card -->
                <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">{{ __('app.recipient_numbering') }}</h3>
                    </div>

                    <!-- Custom / Continuing Invoice Number -->
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/80 space-y-1.5">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                            {{ __('app.document_number') }}
                            <span class="text-[11px] font-normal text-slate-500 dark:text-slate-400">{{ __('app.document_number_help') }}</span>
                        </label>
                        <div class="flex items-center space-x-2">
                            <input type="text" name="invoice_number" value="{{ old('invoice_number', $previewNumber) }}"
                                placeholder="{{ $previewNumber }}"
                                class="w-full px-3 py-2 text-xs font-mono font-bold rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-blue-600 dark:text-blue-400 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <p class="text-[10px] text-slate-500 dark:text-slate-400">
                            {{ __('app.document_number_description') }}
                        </p>
                    </div>

                    @if($errors->has('client_id'))
                        <div class="p-3.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 text-xs text-amber-800 dark:text-amber-200 space-y-2">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <span class="font-medium">{{ $errors->first('client_id') }}</span>
                            </div>
                            <label class="inline-flex items-center text-xs font-bold text-amber-900 dark:text-amber-100 cursor-pointer pt-1">
                                <input type="checkbox" name="allow_duplicate" value="1" {{ old('allow_duplicate') ? 'checked' : '' }}
                                    class="rounded border-amber-400 text-amber-600 focus:ring-amber-500 mr-2">
                                <span>{{ __('app.allow_duplicate_invoice') }}</span>
                            </label>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Client Selection -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                {{ __('app.client') }} <span class="text-rose-500">*</span>
                            </label>
                            <div class="flex items-center space-x-2">
                                <select name="client_id" x-model="form.client_id" @change="onClientChange()" required
                                    class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                    <option value="">{{ __('app.select_client') }}</option>
                                    <template x-for="c in clients" :key="c.id">
                                        <option :value="c.id" x-text="c.company ? `${c.company} (${c.name})` : c.name"></option>
                                    </template>
                                </select>
                                <a href="{{ route('clients.create') }}" target="_blank" class="p-2 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-500 hover:text-blue-600 hover:bg-slate-50 dark:hover:bg-slate-800 transition" title="{{ __('app.add_new_client') }}">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                    </svg>
                                </a>
                            </div>
                        </div>

                        <!-- Currency & Rate -->
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">{{ __('app.currency') }}</label>
                                <input type="text" name="currency" x-model="form.currency" maxlength="3" required
                                    class="w-full px-3 py-2 text-xs uppercase font-bold rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">{{ __('app.exchange_rate') }}</label>
                                <input type="number" step="0.0001" name="exchange_rate" x-model="form.exchange_rate"
                                    class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                            </div>
                        </div>
                    </div>

                    <!-- Dates & Payment terms -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">{{ __('app.issue_date') }}</label>
                            <input type="date" name="issue_date" x-model="form.issue_date" @change="recalculateDueDate()" required
                                class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">{{ __('app.payment_terms') }}</label>
                            <input type="number" min="0" x-model="form.terms_days" @change="recalculateDueDate()"
                                class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">{{ __('app.due_date') }}</label>
                            <input type="date" name="due_date" x-model="form.due_date" required
                                class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                        </div>
                    </div>
                </div>

                <!-- Line Items Table -->
                <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">{{ __('app.line_items') }}</h3>
                            <p class="text-xs text-slate-400">{{ __('app.drag_reorder_help') }}</p>
                        </div>
                        <!-- Catalog Autocomplete dropdown -->
                        <div x-data="{ open: false }" class="relative">
                            <button type="button" @click="open = !open" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-200 transition flex items-center">
                                <svg class="w-3.5 h-3.5 mr-1 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                                {{ __('app.add_from_catalog') }}
                            </button>
                            <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-72 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-xl z-20 max-h-60 overflow-y-auto p-1 text-xs" x-cloak>
                                <template x-for="item in catalogItems" :key="item.id">
                                    <button type="button" @click="addCatalogItem(item); open = false" class="w-full text-left p-2 hover:bg-slate-100 dark:hover:bg-slate-700/60 rounded-lg transition">
                                        <div class="font-bold text-slate-800 dark:text-slate-200" x-text="item.name"></div>
                                        <div class="text-[10px] text-slate-400" x-text="`$${(item.unit_price_cents/100).toFixed(2)} / ${item.unit}`"></div>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Items Draggable Container -->
                    <div id="itemsContainer" x-init="initSortable()" class="space-y-3">
                        <template x-for="(item, index) in items" :key="item.key">
                            <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 space-y-3 transition item-row">
                                <div class="flex items-start gap-2">
                                    <!-- Drag Handle -->
                                    <div class="cursor-grab active:cursor-grabbing text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 pt-2 shrink-0 drag-handle">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                                        </svg>
                                    </div>

                                    <!-- Description Input -->
                                    <div class="flex-1">
                                        <input type="text" :name="`items[${index}][description]`" x-model="item.description" required
                                            placeholder="{{ __('app.work_description_placeholder') }}"
                                            class="w-full px-3 py-1.5 text-xs font-medium rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500">
                                    </div>

                                    <!-- Remove Button -->
                                    <button type="button" @click="removeItem(index)" :disabled="items.length <= 1"
                                        class="p-1.5 text-slate-400 hover:text-rose-500 disabled:opacity-30 disabled:hover:text-slate-400 transition shrink-0" title="{{ __('app.delete_line') }}">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Numerical inputs row -->
                                <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 pl-6">
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">{{ __('invoice.qty') }}</label>
                                        <input type="number" step="0.0001" min="0.0001" :name="`items[${index}][quantity]`" x-model="item.quantity" @input="recalc()" required
                                            class="w-full px-2.5 py-1 text-xs rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                                    </div>

                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">{{ __('invoice.unit') }}</label>
                                        <input type="text" :name="`items[${index}][unit]`" x-model="item.unit" placeholder="{{ __('app.unit_hours') }}" required
                                            class="w-full px-2.5 py-1 text-xs rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                                    </div>

                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">{{ __('invoice.price') }}</label>
                                        <input type="number" step="0.01" min="0" :name="`items[${index}][unit_price]`" x-model="item.unit_price" @input="recalc()" required
                                            class="w-full px-2.5 py-1 text-xs rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-right">
                                    </div>

                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">{{ __('app.discount_percent') }}</label>
                                        <input type="number" step="0.1" min="0" max="100" :name="`items[${index}][discount_percent]`" x-model="item.discount_percent" @input="recalc()"
                                            placeholder="0"
                                            class="w-full px-2.5 py-1 text-xs rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-right">
                                    </div>

                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">{{ __('app.tax_percent') }}</label>
                                        <input type="number" step="0.1" min="0" max="100" :name="`items[${index}][tax_rate_percent]`" x-model="item.tax_rate_percent" @input="recalc()"
                                            placeholder="0"
                                            class="w-full px-2.5 py-1 text-xs rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-right">
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Add Blank Item Button -->
                    <button type="button" @click="addItem()" class="w-full py-2.5 border-2 border-dashed border-slate-200 dark:border-slate-700 hover:border-blue-400 dark:hover:border-blue-500 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition flex items-center justify-center">
                        <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                        </svg>
                        {{ __('app.add_line_item') }}
                    </button>
                </div>

                <!-- Notes & Instructions Card -->
                <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">{{ __('app.notes_payment_details') }}</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">{{ __('app.customer_notes_work_details') }}</label>
                            <textarea name="notes" rows="3" x-model="form.notes"
                                class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400"></textarea>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">{{ __('app.payment_terms') }}</label>
                                <textarea name="payment_terms" rows="2" x-model="form.payment_terms"
                                    class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400"></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">{{ __('app.online_payment_url') }}</label>
                                <input type="url" name="payment_link" x-model="form.payment_link" placeholder="https://buy.stripe.com/..."
                                    class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400">
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column: Settings & Live Preview Panel -->
            <div class="space-y-6">

                <!-- Totals Breakdown Card -->
                <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4 sticky top-20">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">{{ __('app.financial_summary') }}</h3>

                    <div class="space-y-2.5 text-xs">
                        <div class="flex justify-between text-slate-600 dark:text-slate-400">
                            <span>{{ __('invoice.subtotal') }}:</span>
                            <span class="font-semibold text-slate-900 dark:text-white" x-text="formatMoney(totals.subtotal)"></span>
                        </div>

                        <div x-show="totals.discount > 0" class="flex justify-between text-emerald-600 dark:text-emerald-400">
                            <span>{{ __('app.discounts') }}:</span>
                            <span class="font-semibold" x-text="'-' + formatMoney(totals.discount)"></span>
                        </div>

                        <div x-show="totals.tax > 0" class="flex justify-between text-slate-600 dark:text-slate-400">
                            <span x-text="`Tax (${form.tax_inclusive ? 'Inclusive' : 'Exclusive'}):`"></span>
                            <span class="font-semibold text-slate-900 dark:text-white" x-text="formatMoney(totals.tax)"></span>
                        </div>

                        <!-- Shipping / Extra fee input -->
                        <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                            <span class="text-slate-600 dark:text-slate-400">{{ __('app.extra_shipping_fee') }}:</span>
                            <div class="w-24">
                                <input type="number" step="0.01" min="0" name="shipping_fee" x-model="form.shipping_fee" @input="recalc()"
                                    class="w-full px-2 py-1 text-xs text-right rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                            </div>
                        </div>

                        <!-- Grand Total -->
                        <div class="pt-3 border-t-2 border-slate-200 dark:border-slate-700 flex justify-between items-baseline">
                            <span class="text-sm font-bold text-slate-900 dark:text-white">{{ __('app.total_amount') }}:</span>
                            <span class="text-xl font-black text-blue-600 dark:text-blue-400" x-text="formatMoney(totals.total)"></span>
                        </div>

                        <div class="p-3 bg-blue-50/60 dark:bg-blue-950/30 rounded-xl border border-blue-100 dark:border-blue-900/50 flex justify-between items-center text-blue-900 dark:text-blue-200 font-bold">
                            <span>{{ __('app.balance_due') }}:</span>
                            <span class="text-base" x-text="formatMoney(totals.total)"></span>
                        </div>
                    </div>

                    <!-- PDF / Render Options -->
                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800 space-y-3">
                        <h4 class="text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ __('app.document_layout') }}</h4>

                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">{{ __('app.pdf_template') }}</label>
                            <select name="pdf_template" x-model="form.pdf_template" class="w-full px-3 py-1.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                                <option value="modern">{{ __('app.modern_default') }}</option>
                                <option value="emerald">{{ __('app.template_emerald') }}</option>
                                <option value="classic">{{ __('app.classic_formal') }}</option>
                                <option value="minimal">{{ __('app.template_minimal') }}</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">{{ __('app.paper_size') }}</label>
                                <select name="page_size" x-model="form.page_size" class="w-full px-2 py-1.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                                    <option value="a4">A4</option>
                                    <option value="letter">{{ __('app.us_letter') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">{{ __('app.default_language') }}</label>
                                <select name="locale" x-model="form.locale" class="w-full px-2 py-1.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                                    <option value="en">{{ __('app.english_language') }}</option>
                                    <option value="es">{{ __('app.spanish_language') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="pt-2 flex flex-col space-y-2">
                            <label class="inline-flex items-center text-xs text-slate-600 dark:text-slate-300 cursor-pointer">
                                <input type="checkbox" name="tax_inclusive" x-model="form.tax_inclusive" @change="recalc()" value="1"
                                    class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 mr-2">
                                <span>{{ __('app.prices_tax_inclusive') }}</span>
                            </label>
                            <label class="inline-flex items-center text-xs text-slate-600 dark:text-slate-300 cursor-pointer">
                                <input type="checkbox" name="show_signature" x-model="form.show_signature" value="1"
                                    class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 mr-2">
                                <span>{{ __('app.show_signature') }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Shortcut note -->
                    <div class="text-[10px] text-slate-400 text-center pt-2">
                        {{ __('app.save_shortcut_tip', ['primary' => 'Cmd+S', 'secondary' => 'Ctrl+S']) }}
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function invoiceBuilder(config) {
    return {
        clients: config.clients || [],
        catalogItems: config.catalogItems || [],
        form: {
            client_id: config.initialClientId || '',
            currency: config.defaultCurrency || 'USD',
            exchange_rate: 1.0,
            issue_date: new Date().toISOString().split('T')[0],
            terms_days: config.defaultTermsDays || 14,
            due_date: new Date(Date.now() + 14 * 86400000).toISOString().split('T')[0],
            shipping_fee: 0,
            tax_inclusive: config.defaultTaxInclusive || false,
            notes: config.defaultNotes || '',
            payment_terms: config.defaultFooter || '',
            payment_link: '',
            pdf_template: config.defaultTemplate || 'modern',
            page_size: config.defaultPageSize || 'letter',
            locale: config.defaultLocale || 'es',
            show_signature: config.defaultShowSignature || false
        },
        items: [
            { key: 1, description: '', quantity: 1, unit: 'hours', unit_price: 0, discount_percent: 0, tax_rate_percent: 0 }
        ],
        totals: { subtotal: 0, discount: 0, tax: 0, total: 0 },

        init() {
            if (config.sourceInvoice) {
                const src = config.sourceInvoice;
                this.form.client_id = src.client_id;
                this.form.currency = src.currency;
                this.form.exchange_rate = src.exchange_rate;
                this.form.notes = src.notes;
                this.form.payment_terms = src.payment_terms;
                this.form.payment_link = src.payment_link;
                this.form.pdf_template = src.pdf_template;
                this.form.page_size = src.page_size;
                this.form.locale = src.locale;
                this.form.tax_inclusive = !!src.tax_inclusive;
                this.form.shipping_fee = (src.shipping_fee_cents / 100) || 0;

                if (src.items && src.items.length > 0) {
                    this.items = src.items.map((it, idx) => ({
                        key: idx + 1,
                        description: it.description,
                        quantity: parseFloat(it.quantity),
                        unit: it.unit,
                        unit_price: (it.unit_price_cents / 100),
                        discount_percent: (it.discount_bps / 100),
                        tax_rate_percent: (it.tax_rate_bps / 100),
                    }));
                }
            } else if (this.form.client_id) {
                this.onClientChange();
            }

            this.recalc();
        },

        initSortable() {
            this.$nextTick(() => {
                const container = document.getElementById('itemsContainer');
                if (container && window.Sortable) {
                    new Sortable(container, {
                        handle: '.drag-handle',
                        animation: 150,
                        onEnd: (evt) => {
                            const moved = this.items.splice(evt.oldIndex, 1)[0];
                            this.items.splice(evt.newIndex, 0, moved);
                            this.recalc();
                        }
                    });
                }
            });
        },

        onClientChange() {
            const client = this.clients.find(c => c.id == this.form.client_id);
            if (!client) return;

            if (client.currency) {
                this.form.currency = client.currency;
            }
            if (client.locale) {
                this.form.locale = client.locale;
            }
            if (client.default_payment_terms_days !== null && client.default_payment_terms_days !== undefined) {
                this.form.terms_days = client.default_payment_terms_days;
            }
            this.recalculateDueDate();

            if (client.default_tax_rate_bps) {
                const taxPercent = client.default_tax_rate_bps / 100;
                this.items.forEach(it => {
                    if (!it.tax_rate_percent) it.tax_rate_percent = taxPercent;
                });
            }
            this.recalc();
        },

        recalculateDueDate() {
            if (!this.form.issue_date) return;
            const issue = new Date(this.form.issue_date);
            const days = parseInt(this.form.terms_days) || 0;
            const due = new Date(issue.getTime() + days * 86400000);
            this.form.due_date = due.toISOString().split('T')[0];
        },

        addItem() {
            this.items.push({
                key: Date.now(),
                description: '',
                quantity: 1,
                unit: 'hours',
                unit_price: 0,
                discount_percent: 0,
                tax_rate_percent: 0
            });
            this.recalc();
        },

        addCatalogItem(catalog) {
            this.items.push({
                key: Date.now(),
                description: catalog.name + (catalog.description ? ' - ' + catalog.description : ''),
                quantity: 1,
                unit: catalog.unit || 'hours',
                unit_price: catalog.unit_price_cents / 100,
                discount_percent: 0,
                tax_rate_percent: catalog.tax_rate_bps ? catalog.tax_rate_bps / 100 : 0
            });
            this.recalc();
        },

        removeItem(index) {
            if (this.items.length <= 1) return;
            this.items.splice(index, 1);
            this.recalc();
        },

        recalc() {
            let subtotal = 0;
            let discountTotal = 0;
            let taxTotal = 0;

            this.items.forEach(it => {
                const qty = parseFloat(it.quantity) || 0;
                const price = parseFloat(it.unit_price) || 0;
                const rawLine = qty * price;

                const discPct = parseFloat(it.discount_percent) || 0;
                const discAmt = (rawLine * discPct) / 100;
                const afterDisc = rawLine - discAmt;

                const taxPct = parseFloat(it.tax_rate_percent) || 0;
                let lineTax = 0;
                let lineNet = afterDisc;

                if (this.form.tax_inclusive) {
                    if (taxPct > 0) {
                        lineNet = afterDisc / (1 + (taxPct / 100));
                        lineTax = afterDisc - lineNet;
                    }
                } else {
                    if (taxPct > 0) {
                        lineTax = (afterDisc * taxPct) / 100;
                    }
                }

                subtotal += lineNet;
                discountTotal += discAmt;
                taxTotal += lineTax;
            });

            const shipping = parseFloat(this.form.shipping_fee) || 0;
            const total = subtotal + taxTotal + shipping;

            this.totals = {
                subtotal: Math.round(subtotal * 100) / 100,
                discount: Math.round(discountTotal * 100) / 100,
                tax: Math.round(taxTotal * 100) / 100,
                total: Math.round(total * 100) / 100,
            };
        },

        formatMoney(amount) {
            const sym = this.form.currency === 'EUR' ? '€' : (this.form.currency === 'GBP' ? '£' : '$');
            return sym + (amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        submitForm() {
            document.getElementById('invoiceForm').submit();
        }
    };
}
</script>
@endpush
