@extends('layouts.app')

@section('title', 'New Recurring Schedule')

@section('content')
<div x-data="{
    catalogItems: {{ Js::from($catalogItems) }},
    items: [
        { description: 'Monthly Retainer Engineering Support', quantity: 1, unit: 'month', unit_price: 1000, tax_rate_percent: 0 }
    ],
    addItem() {
        this.items.push({ description: '', quantity: 1, unit: 'hours', unit_price: 0, tax_rate_percent: 0 });
    },
    removeItem(idx) {
        if (this.items.length <= 1) return;
        this.items.splice(idx, 1);
    },
    addCatalog(cat) {
        this.items.push({
            description: cat.name + (cat.description ? ' - ' + cat.description : ''),
            quantity: 1,
            unit: cat.unit || 'hours',
            unit_price: cat.unit_price_cents / 100,
            tax_rate_percent: cat.tax_rate_bps ? cat.tax_rate_bps / 100 : 0
        });
    }
}" class="max-w-3xl mx-auto space-y-6">

    <div>
        <a href="{{ route('recurring.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
            &larr; Recurring Schedules
        </a>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-1">Create Recurring Template</h1>
    </div>

    <form method="POST" action="{{ route('recurring.store') }}" class="p-6 sm:p-8 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-6">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Client <span class="text-rose-500">*</span></label>
                <select name="client_id" required class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                    <option value="">-- Select Client --</option>
                    @foreach($clients as $c)
                        <option value="{{ $c->id }}">{{ $c->display_name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Billing Frequency <span class="text-rose-500">*</span></label>
                <select name="frequency" required class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                    <option value="weekly">Weekly</option>
                    <option value="biweekly">Bi-weekly (Every 2 weeks)</option>
                    <option value="monthly" selected>Monthly (1st of month)</option>
                    <option value="quarterly">Quarterly (Every 3 months)</option>
                    <option value="yearly">Yearly</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Start / Next Date <span class="text-rose-500">*</span></label>
                <input type="date" name="start_date" value="{{ date('Y-m-01') }}" required
                    class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">End Date (Optional)</label>
                <input type="date" name="end_date"
                    class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Currency</label>
                <input type="text" name="currency" value="{{ $profile->default_currency }}" maxlength="3" required
                    class="w-full px-3 py-2 text-xs uppercase font-bold rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Payment Terms (Days to Due Date)</label>
            <input type="number" name="payment_terms_days" value="14" required
                class="w-32 px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
        </div>

        <!-- Line items -->
        <div class="space-y-3 pt-3 border-t border-slate-100 dark:border-slate-800">
            <div class="flex items-center justify-between">
                <label class="text-xs font-bold uppercase tracking-wider text-slate-400">Template Line Items</label>
                <button type="button" @click="addItem()" class="text-xs text-blue-600 font-semibold hover:underline">+ Add Line</button>
            </div>

            <template x-for="(item, idx) in items" :key="idx">
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-2">
                    <div class="flex items-center gap-2">
                        <input type="text" :name="`items[${idx}][description]`" x-model="item.description" placeholder="Description of service..." required
                            class="flex-1 px-3 py-1.5 text-xs rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                        <button type="button" @click="removeItem(idx)" :disabled="items.length <= 1" class="text-slate-400 hover:text-rose-500 disabled:opacity-30">
                            &times;
                        </button>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <input type="number" step="0.01" min="0.01" :name="`items[${idx}][quantity]`" x-model="item.quantity" placeholder="Qty" required
                                class="w-full px-2 py-1 text-xs rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                        </div>
                        <div>
                            <input type="text" :name="`items[${idx}][unit]`" x-model="item.unit" placeholder="month, hours" required
                                class="w-full px-2 py-1 text-xs rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                        </div>
                        <div>
                            <input type="number" step="0.01" min="0" :name="`items[${idx}][unit_price]`" x-model="item.unit_price" placeholder="Price" required
                                class="w-full px-2 py-1 text-xs rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-right">
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Default Notes</label>
            <textarea name="notes" rows="2" placeholder="Pre-fill notes on recurring invoices..."
                class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white"></textarea>
        </div>

        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100 dark:border-slate-800">
            <a href="{{ route('recurring.index') }}" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                Cancel
            </a>
            <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-sm transition active:scale-95">
                Save Recurring Schedule
            </button>
        </div>
    </form>
</div>
@endsection
