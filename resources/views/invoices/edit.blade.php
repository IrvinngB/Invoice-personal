@extends('layouts.app')

@section('title', 'Edit ' . $invoice->invoice_number)

@section('content')
<div x-data="invoiceBuilder({
    clients: {{ Js::from($clients) }},
    catalogItems: {{ Js::from($catalogItems) }},
    defaultCurrency: '{{ $invoice->currency }}',
    defaultTermsDays: 14,
    defaultTaxInclusive: {{ $invoice->tax_inclusive ? 'true' : 'false' }},
    defaultNotes: {{ Js::from($invoice->notes) }},
    defaultFooter: {{ Js::from($invoice->payment_terms) }},
    defaultPaymentInstructions: {{ Js::from($invoice->payment_instructions) }},
    initialClientId: '{{ $invoice->client_id }}',
    sourceInvoice: {
        client_id: {{ $invoice->client_id }},
        currency: '{{ $invoice->currency }}',
        exchange_rate: {{ $invoice->exchange_rate }},
        notes: {{ Js::from($invoice->notes) }},
        payment_terms: {{ Js::from($invoice->payment_terms) }},
        payment_link: '{{ $invoice->payment_link }}',
        pdf_template: '{{ $invoice->pdf_template }}',
        page_size: '{{ $invoice->page_size }}',
        locale: '{{ $invoice->locale }}',
        tax_inclusive: {{ $invoice->tax_inclusive ? 'true' : 'false' }},
        shipping_fee_cents: {{ $invoice->shipping_fee_cents }},
        items: {{ Js::from($invoice->items) }}
    }
})" class="space-y-6">

    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('invoices.show', $invoice) }}" class="text-xs font-semibold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 transition">
                    &larr; Back to {{ $invoice->invoice_number }}
                </a>
                <span class="text-xs text-slate-400">/</span>
                <span class="text-xs font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400">
                    Editing Draft
                </span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-1">
                {{ $invoice->invoice_number }}
            </h1>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('invoices.show', $invoice) }}" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                Cancel
            </a>
            <button type="button" @click="submitForm()" class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-600/20 transition active:scale-95 flex items-center">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
                Update Document
            </button>
        </div>
    </div>

    <form id="invoiceForm" method="POST" action="{{ route('invoices.update', $invoice) }}" @keydown.window.prevent.ctrl.s="submitForm()" @keydown.window.prevent.meta.s="submitForm()">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left 2 Cols: Form Content -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Client & Dates Card -->
                <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">Recipient & Schedule</h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Client</label>
                            <select name="client_id" x-model="form.client_id" required
                                class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                                <template x-for="c in clients" :key="c.id">
                                    <option :value="c.id" x-text="c.company ? `${c.company} (${c.name})` : c.name"></option>
                                </template>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Currency</label>
                                <input type="text" name="currency" x-model="form.currency" maxlength="3" required
                                    class="w-full px-3 py-2 text-xs uppercase font-bold rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Exch. Rate</label>
                                <input type="number" step="0.0001" name="exchange_rate" x-model="form.exchange_rate"
                                    class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Issue Date</label>
                            <input type="date" name="issue_date" value="{{ $invoice->issue_date->format('Y-m-d') }}" required
                                class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Due Date</label>
                            <input type="date" name="due_date" value="{{ $invoice->due_date->format('Y-m-d') }}" required
                                class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                        </div>
                    </div>
                </div>

                <!-- Line Items Table -->
                <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">Line Items</h3>
                            <p class="text-xs text-slate-400">Drag items to reorder · Supports fractional units & hours</p>
                        </div>
                        <div x-data="{ open: false }" class="relative">
                            <button type="button" @click="open = !open" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-200 transition flex items-center">
                                <svg class="w-3.5 h-3.5 mr-1 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                                Add from Catalog
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

                    <div id="itemsContainer" x-init="initSortable()" class="space-y-3">
                        <template x-for="(item, index) in items" :key="item.key">
                            <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 space-y-3 transition item-row">
                                <div class="flex items-start gap-2">
                                    <div class="cursor-grab active:cursor-grabbing text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 pt-2 shrink-0 drag-handle">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                                        </svg>
                                    </div>

                                    <div class="flex-1">
                                        <input type="text" :name="`items[${index}][description]`" x-model="item.description" required
                                            class="w-full px-3 py-1.5 text-xs font-medium rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                                    </div>

                                    <button type="button" @click="removeItem(index)" :disabled="items.length <= 1"
                                        class="p-1.5 text-slate-400 hover:text-rose-500 disabled:opacity-30 transition shrink-0">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>

                                <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 pl-6">
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Qty</label>
                                        <input type="number" step="0.0001" min="0.0001" :name="`items[${index}][quantity]`" x-model="item.quantity" @input="recalc()" required
                                            class="w-full px-2.5 py-1 text-xs rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Unit</label>
                                        <input type="text" :name="`items[${index}][unit]`" x-model="item.unit" required
                                            class="w-full px-2.5 py-1 text-xs rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Price</label>
                                        <input type="number" step="0.01" min="0" :name="`items[${index}][unit_price]`" x-model="item.unit_price" @input="recalc()" required
                                            class="w-full px-2.5 py-1 text-xs rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-right">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Disc. %</label>
                                        <input type="number" step="0.1" min="0" max="100" :name="`items[${index}][discount_percent]`" x-model="item.discount_percent" @input="recalc()"
                                            class="w-full px-2.5 py-1 text-xs rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-right">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Tax %</label>
                                        <input type="number" step="0.1" min="0" max="100" :name="`items[${index}][tax_rate_percent]`" x-model="item.tax_rate_percent" @input="recalc()"
                                            class="w-full px-2.5 py-1 text-xs rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-right">
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <button type="button" @click="addItem()" class="w-full py-2.5 border-2 border-dashed border-slate-200 dark:border-slate-700 hover:border-blue-400 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-blue-600 transition flex items-center justify-center">
                        + Add Another Line Item
                    </button>
                </div>

                <!-- Notes & Instructions -->
                <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">Notes & Payment Details</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Customer Notes</label>
                            <textarea name="notes" rows="3" x-model="form.notes"
                                class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white"></textarea>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Payment Terms</label>
                                <textarea name="payment_terms" rows="2" x-model="form.payment_terms"
                                    class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white"></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Payment Link</label>
                                <input type="url" name="payment_link" x-model="form.payment_link"
                                    class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4 sticky top-20">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">Financial Summary</h3>

                    <div class="space-y-2.5 text-xs">
                        <div class="flex justify-between text-slate-600 dark:text-slate-400">
                            <span>Subtotal:</span>
                            <span class="font-semibold text-slate-900 dark:text-white" x-text="formatMoney(totals.subtotal)"></span>
                        </div>
                        <div x-show="totals.discount > 0" class="flex justify-between text-emerald-600 dark:text-emerald-400">
                            <span>Discounts:</span>
                            <span class="font-semibold" x-text="'-' + formatMoney(totals.discount)"></span>
                        </div>
                        <div x-show="totals.tax > 0" class="flex justify-between text-slate-600 dark:text-slate-400">
                            <span x-text="`Tax (${form.tax_inclusive ? 'Inclusive' : 'Exclusive'}):`"></span>
                            <span class="font-semibold text-slate-900 dark:text-white" x-text="formatMoney(totals.tax)"></span>
                        </div>
                        <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                            <span class="text-slate-600 dark:text-slate-400">Extra / Shipping:</span>
                            <div class="w-24">
                                <input type="number" step="0.01" min="0" name="shipping_fee" x-model="form.shipping_fee" @input="recalc()"
                                    class="w-full px-2 py-1 text-xs text-right rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                            </div>
                        </div>
                        <div class="pt-3 border-t-2 border-slate-200 dark:border-slate-700 flex justify-between items-baseline">
                            <span class="text-sm font-bold text-slate-900 dark:text-white">Total Amount:</span>
                            <span class="text-xl font-black text-blue-600 dark:text-blue-400" x-text="formatMoney(totals.total)"></span>
                        </div>
                    </div>

                    <!-- Layout Options -->
                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800 space-y-3">
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">PDF Template</label>
                            <select name="pdf_template" x-model="form.pdf_template" class="w-full px-3 py-1.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                                <option value="modern">Modern</option>
                                <option value="classic">Classic</option>
                                <option value="minimal">Minimalist</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">Page Size</label>
                                <select name="page_size" x-model="form.page_size" class="w-full px-2 py-1.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                                    <option value="a4">A4</option>
                                    <option value="letter">Letter</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">Language</label>
                                <select name="locale" x-model="form.locale" class="w-full px-2 py-1.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                                    <option value="en">EN</option>
                                    <option value="es">ES</option>
                                </select>
                            </div>
                        </div>
                        <div class="pt-2">
                            <label class="inline-flex items-center text-xs text-slate-600 dark:text-slate-300 cursor-pointer">
                                <input type="checkbox" name="tax_inclusive" x-model="form.tax_inclusive" @change="recalc()" value="1"
                                    class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 mr-2">
                                <span>Tax-inclusive pricing</span>
                            </label>
                        </div>
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
            shipping_fee: 0,
            tax_inclusive: config.defaultTaxInclusive || false,
            notes: config.defaultNotes || '',
            payment_terms: config.defaultFooter || '',
            payment_link: '',
            pdf_template: 'modern',
            page_size: 'a4',
            locale: 'en'
        },
        items: [],
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
            }

            if (this.items.length === 0) {
                this.items.push({ key: 1, description: '', quantity: 1, unit: 'hours', unit_price: 0, discount_percent: 0, tax_rate_percent: 0 });
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
