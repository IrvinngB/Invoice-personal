<!DOCTYPE html>
<html lang="en" x-data="{ 
    darkMode: localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches),
    toggleTheme() {
        this.darkMode = !this.darkMode;
        localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
        if (this.darkMode) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    },
    mobileMenu: false
}" :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Invoices') - Personal Invoice Studio</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
        .font-display { font-family: 'Bricolage Grotesque', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 dark:bg-[#071F1F] dark:text-[#E8F5F0] min-h-screen font-sans antialiased transition-colors duration-200">

    <!-- Top Navigation -->
    <header class="sticky top-0 z-40 bg-white/80 dark:bg-[#0A2E2E]/80 backdrop-blur-md border-b border-slate-200 dark:border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Brand / Logo -->
                <div class="flex items-center space-x-6">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 group">
                        <div class="h-10 w-10 flex items-center justify-center group-hover:scale-105 transition-transform shrink-0">
                            <svg viewBox="15 14 70 70" class="h-9 w-auto" role="img" aria-label="Irvin Benitez">
                                <circle cx="27" cy="28" r="8" fill="#BBD94E"/>
                                <rect x="20" y="44" width="14" height="36" rx="7" fill="#3FA35B"/>
                                <rect x="44" y="18" width="14" height="62" rx="7" fill="#3FA35B"/>
                                <path fill-rule="evenodd" d="M44 62a18 18 0 1 0 36 0a18 18 0 1 0-36 0ZM55 62a7 7 0 1 0 14 0a7 7 0 1 0-14 0Z" fill="#3FA35B"/>
                            </svg>
                        </div>
                        <div>
                            <span class="text-base font-bold tracking-tight text-slate-900 dark:text-[#E8F5F0] block leading-tight font-display">
                                {{ \App\Models\BusinessProfile::current()->business_name ?: 'Irvin Benitez' }}
                            </span>
                            <span class="text-xs text-[#3FA35B] dark:text-[#B4D333] block leading-tight font-semibold">Invoice Studio</span>
                        </div>
                    </a>

                    <!-- Desktop Nav Links -->
                    <nav class="hidden md:flex items-center space-x-1 pl-4 border-l border-slate-200 dark:border-slate-800">
                        <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400' : 'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white' }}">
                            {{ __('app.dashboard') }}
                        </a>
                        <a href="{{ route('invoices.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('invoices.*') && !request()->routeIs('invoices.create') ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400' : 'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white' }}">
                            {{ __('app.invoices_quotes') }}
                        </a>
                        <a href="{{ route('clients.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('clients.*') ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400' : 'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white' }}">
                            {{ __('app.clients') }}
                        </a>
                        <a href="{{ route('catalog.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('catalog.*') ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400' : 'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white' }}">
                            {{ __('app.services_catalog') }}
                        </a>
                        <a href="{{ route('recurring.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('recurring.*') ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400' : 'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white' }}">
                            {{ __('app.recurring') }}
                        </a>
                        <a href="{{ route('settings.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('settings.*') ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400' : 'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white' }}">
                            {{ __('app.settings') }}
                        </a>
                    </nav>
                </div>

                <!-- Right Action Bar -->
                <div class="flex items-center space-x-3">
                    <!-- Language Switcher -->
                    <div class="flex items-center rounded-lg bg-slate-100 dark:bg-slate-800 p-0.5 border border-slate-200 dark:border-slate-700 text-xs font-semibold">
                        <a href="{{ route('locale.switch', 'en') }}" class="px-2 py-1 rounded-md transition {{ app()->getLocale() === 'en' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-xs' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white' }}">
                            EN
                        </a>
                        <a href="{{ route('locale.switch', 'es') }}" class="px-2 py-1 rounded-md transition {{ app()->getLocale() === 'es' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-xs' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white' }}">
                            ES
                        </a>
                    </div>

                    <!-- Quick Create Button -->
                    <a href="{{ route('invoices.create') }}" class="inline-flex items-center px-3.5 py-1.5 rounded-lg text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-500 shadow-sm transition active:scale-95">
                        <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>{{ __('app.new_invoice') }}</span>
                    </a>

                    <!-- Dark mode toggle -->
                    <button @click="toggleTheme()" type="button" class="p-2 rounded-lg text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition" title="Toggle color theme">
                        <svg x-show="!darkMode" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        <svg x-show="darkMode" class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" x-cloak>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 9H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </button>

                    @if(!config('invoice.local_mode', true) && session('authenticated'))
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="p-2 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition" title="Lock Studio">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </form>
                    @endif

                    <!-- Mobile Menu Trigger -->
                    <button @click="mobileMenu = !mobileMenu" class="md:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Drawer -->
        <div x-show="mobileMenu" @click.away="mobileMenu = false" class="md:hidden px-4 pt-2 pb-4 space-y-1 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900" x-cloak>
            <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">Dashboard</a>
            <a href="{{ route('invoices.index') }}" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">Invoices</a>
            <a href="{{ route('clients.index') }}" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">Clients</a>
            <a href="{{ route('catalog.index') }}" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">Catalog</a>
            <a href="{{ route('recurring.index') }}" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">Recurring</a>
            <a href="{{ route('settings.index') }}" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">Settings</a>
        </div>
    </header>

    <!-- Notification Toasts -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        @if(session('success'))
            <div class="mb-4 flex items-center p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300 rounded-xl shadow-sm">
                <svg class="w-5 h-5 mr-3 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-sm font-medium">{{ session('success') }}</div>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 flex items-center p-4 bg-rose-50 border border-rose-200 text-rose-800 dark:bg-rose-950/40 dark:border-rose-800 dark:text-rose-300 rounded-xl shadow-sm">
                <svg class="w-5 h-5 mr-3 shrink-0 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-sm font-medium">{{ session('error') }}</div>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 p-4 bg-rose-50 border border-rose-200 text-rose-800 dark:bg-rose-950/40 dark:border-rose-800 dark:text-rose-300 rounded-xl shadow-sm">
                <div class="text-sm font-semibold mb-1">Please fix the following errors:</div>
                <ul class="list-disc list-inside text-xs space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <!-- Main Container -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="mt-20 border-t border-slate-200 dark:border-slate-800 py-6 text-center text-xs text-slate-400 dark:text-slate-500">
        Personal Invoice Studio · Built with clean architecture & integer precision.
    </footer>

    @stack('scripts')
</body>
</html>
