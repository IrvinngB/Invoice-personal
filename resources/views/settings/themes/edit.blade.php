@extends('layouts.app')

@section('title', $theme->exists ? __('app.theme_edit') : __('app.theme_new'))

@php
    $field = 'w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white';
    $labelCls = 'block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1';
    $options = [
        'font' => array_combine(\App\Services\ThemeTokens::FONTS, \App\Services\ThemeTokens::FONTS),
        'header' => collect(\App\Services\ThemeTokens::HEADERS)->mapWithKeys(fn ($v) => [$v => __("app.theme_header_{$v}")])->all(),
        'table' => collect(\App\Services\ThemeTokens::TABLES)->mapWithKeys(fn ($v) => [$v => __("app.theme_table_{$v}")])->all(),
        'total' => collect(\App\Services\ThemeTokens::TOTALS)->mapWithKeys(fn ($v) => [$v => __("app.theme_total_{$v}")])->all(),
        'radius' => collect(\App\Services\ThemeTokens::RADII)->mapWithKeys(fn ($v) => [$v => __("app.theme_radius_{$v}")])->all(),
        'scale' => collect(array_keys(\App\Services\ThemeTokens::SCALES))->mapWithKeys(fn ($v) => [$v => __("app.theme_scale_{$v}")])->all(),
    ];
    $selects = ['font' => 'theme_font', 'header' => 'theme_header', 'table' => 'theme_table', 'total' => 'theme_total', 'radius' => 'theme_radius', 'scale' => 'theme_scale'];
@endphp

@section('content')
<div class="max-w-6xl mx-auto space-y-6"
     x-data="{
        form: @js($values + ['name' => old('name', $theme->name)]),
        src: '',
        timer: null,
        build() {
            const p = new URLSearchParams({
                base: '{{ $theme->base }}',
                locale: '{{ app()->getLocale() }}',
                accent: this.form.accent, font: this.form.font, header: this.form.header,
                table: this.form.table, total: this.form.total, radius: this.form.radius,
                scale: this.form.scale, uppercase_labels: this.form.uppercase_labels ? 1 : 0,
            });
            this.src = '{{ route('settings.themes.preview') }}?' + p.toString();
        },
        refresh() { clearTimeout(this.timer); this.timer = setTimeout(() => this.build(), 350); },
        init() { this.build(); this.$watch('form', () => this.refresh()); }
     }">

    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $theme->exists ? __('app.theme_edit') : __('app.theme_new') }}</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('app.theme_based_on') }} {{ __("app.template_{$theme->base}") }}</p>
        </div>
        <a href="{{ route('settings.themes.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-900 dark:hover:text-white whitespace-nowrap">&larr; {{ __('app.theme_back') }}</a>
    </div>

    @if($errors->any())
        <div class="p-3 rounded-xl bg-rose-50 text-rose-700 text-xs">{{ $errors->first() }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        <form method="POST" action="{{ $theme->exists ? route('settings.themes.update', $theme) : route('settings.themes.store') }}"
              class="lg:col-span-2 p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4 self-start">
            @csrf
            @if($theme->exists) @method('PUT') @endif
            <input type="hidden" name="base" value="{{ $theme->base }}">

            <div>
                <label class="{{ $labelCls }}">{{ __('app.theme_name') }} <span class="text-rose-500">*</span></label>
                <input type="text" name="name" x-model="form.name" required maxlength="80" class="{{ $field }}">
            </div>

            <div>
                <label class="{{ $labelCls }}">{{ __('app.theme_accent') }}</label>
                <div class="flex items-center gap-3">
                    <input type="color" name="accent" x-model="form.accent" class="h-9 w-14 rounded-lg border border-slate-200 dark:border-slate-700 bg-transparent cursor-pointer">
                    <span class="text-xs font-mono text-slate-500" x-text="form.accent"></span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                @foreach($selects as $key => $labelKey)
                    <div>
                        <label class="{{ $labelCls }}">{{ __("app.{$labelKey}") }}</label>
                        <select name="{{ $key }}" x-model="form.{{ $key }}" class="{{ $field }}">
                            @foreach($options[$key] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
            </div>

            <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-300">
                <input type="checkbox" name="uppercase_labels" value="1" x-model="form.uppercase_labels" class="rounded">
                {{ __('app.theme_uppercase') }}
            </label>

            <button type="submit" class="w-full px-4 py-2.5 text-sm font-semibold rounded-xl bg-blue-600 hover:bg-blue-700 text-white transition">{{ __('app.save') }}</button>
        </form>

        <div class="lg:col-span-3">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">{{ __('app.theme_live_preview') }}</div>
            <iframe :src="src" class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-white shadow-inner" style="height: 78vh;" title="{{ __('app.theme_live_preview') }}"></iframe>
        </div>
    </div>
</div>
@endsection
