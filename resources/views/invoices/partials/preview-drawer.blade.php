    <!-- Live PDF preview drawer -->
    <aside x-show="preview.open" x-cloak
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
        @keydown.escape.window="preview.open = false"
        class="fixed top-16 right-0 bottom-0 z-30 w-full md:w-[44rem] max-w-full flex flex-col bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 shadow-2xl">
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200 dark:border-slate-800">
            <div class="flex items-center gap-2">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('app.preview_live') }}</h3>
                <span x-show="preview.loading" class="text-[11px] text-slate-400">{{ __('app.preview_updating') }}</span>
            </div>
            <button type="button" @click="preview.open = false" class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="{{ __('app.preview_close') }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="relative flex-1 bg-slate-100 dark:bg-slate-950">
            <iframe x-show="preview.url" :src="preview.url ? preview.url + '#toolbar=0&navpanes=0&view=FitH' : ''" class="absolute inset-0 w-full h-full" title="{{ __('app.preview_live') }}"></iframe>
            <div x-show="preview.error" class="absolute inset-x-4 top-4 p-3 rounded-xl bg-amber-50 text-amber-800 text-xs border border-amber-200">{{ __('app.preview_failed') }}</div>
        </div>
    </aside>
