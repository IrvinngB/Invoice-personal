@extends('layouts.app')

@section('title', __('app.themes_title'))

@section('content')
<div class="max-w-4xl mx-auto space-y-8">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ __('app.themes_heading') }}</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('app.themes_subtitle') }}</p>
        </div>
        <a href="{{ route('settings.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-900 dark:hover:text-white whitespace-nowrap">&larr; {{ __('app.settings') }}</a>
    </div>

    <div class="p-6 sm:p-8 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">{{ __('app.themes_presets') }}</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach($presets as $preset)
                <div class="flex items-center justify-between p-4 rounded-xl border border-slate-200 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <span class="w-4 h-4 rounded-full border border-slate-200" style="background: {{ config("invoice_themes.presets.{$preset}.accent") }}"></span>
                        <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ __("app.template_{$preset}") }}</span>
                    </div>
                    <a href="{{ route('settings.themes.create', ['base' => $preset]) }}" class="text-xs font-semibold text-blue-600 hover:underline">{{ __('app.theme_customize') }}</a>
                </div>
            @endforeach
        </div>
    </div>

    <div class="p-6 sm:p-8 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">{{ __('app.themes_custom') }}</h3>
        @forelse($themes as $theme)
            <div class="flex items-center justify-between p-4 rounded-xl border border-slate-200 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <span class="w-4 h-4 rounded-full border border-slate-200" style="background: {{ $theme->overrides['accent'] ?? config("invoice_themes.presets.{$theme->base}.accent") }}"></span>
                    <div>
                        <div class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $theme->name }}</div>
                        <div class="text-[11px] text-slate-400">{{ __('app.theme_based_on') }} {{ __("app.template_{$theme->base}") }}</div>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <a href="{{ route('settings.themes.edit', $theme) }}" class="text-xs font-semibold text-blue-600 hover:underline">{{ __('app.edit') }}</a>
                    <form method="POST" action="{{ route('settings.themes.destroy', $theme) }}" onsubmit="return confirm('{{ __('app.theme_delete_confirm') }}')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs font-semibold text-rose-600 hover:underline">{{ __('app.delete') }}</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('app.themes_empty') }}</p>
        @endforelse
    </div>
</div>
@endsection
