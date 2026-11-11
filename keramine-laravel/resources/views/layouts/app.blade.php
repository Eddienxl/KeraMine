<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('keramine.app_name')) — B2B Ceramic Intelligence</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui'],
                        mono: ['JetBrains Mono', 'ui-monospace', 'SFMono-Regular'],
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

    <script>
        (function () {
            const saved = localStorage.getItem('keramine_theme');
            if (saved === 'dark') {
                document.documentElement.classList.add('dark');
            }
        })();
        function toggleKeraMineTheme() {
            const html = document.documentElement;
            const isDark = html.classList.toggle('dark');
            localStorage.setItem('keramine_theme', isDark ? 'dark' : 'light');
            window.dispatchEvent(new CustomEvent('keramine-theme-changed', { detail: { dark: isDark } }));
        }
        function toggleMobileMenu() {
            const drawer = document.getElementById('mobile-nav-drawer');
            if (drawer) {
                drawer.classList.toggle('hidden');
            }
        }
    </script>

    <style>
        body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
        .font-mono-num { font-family: 'JetBrains Mono', monospace; font-variant-numeric: tabular-nums; }
        .km-card {
            background-color: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 1rem;
            box-shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.04), 0 1px 2px -1px rgba(15, 23, 42, 0.03);
        }
        .dark .km-card {
            background-color: #1A1D27;
            border-color: #2A2F3E;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.35);
        }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-[#F8FAFC] text-[#0F172A] dark:bg-[#0F1117] dark:text-[#E6E8EE] transition-colors duration-200 antialiased">
@php
    $currLocale = app()->getLocale();
    $labUrl = config('keramine.streamlit_url');
    $n8nUrl = config('keramine.n8n_url');
@endphp

    <header class="sticky top-0 z-40 border-b border-slate-200/80 dark:border-[#2A2F3E] bg-white/90 dark:bg-[#131620]/90 backdrop-blur-md no-print">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-3">
            <!-- Brand -->
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 group shrink-0">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-600 via-indigo-500 to-cyan-500 flex items-center justify-center text-white shadow-sm group-hover:scale-105 transition">
                    <x-icon name="layers" class="w-5 h-5" />
                </div>
                <div class="leading-tight whitespace-nowrap">
                    <span class="font-extrabold text-base tracking-tight text-slate-900 dark:text-white">{{ __('keramine.app_name') }}</span>
                    <span class="hidden xl:block text-[10px] font-mono uppercase tracking-wider text-indigo-600 dark:text-cyan-400">Multi-Level SOTA ARM</span>
                </div>
            </a>

            <!-- Desktop Navigation -->
            <nav class="hidden lg:flex items-center gap-1 text-xs font-medium overflow-x-auto">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 px-2.5 py-2 rounded-lg whitespace-nowrap shrink-0 transition {{ request()->routeIs('home') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1F2433]' }}">
                    <x-icon name="home" class="w-3.5 h-3.5 shrink-0" />
                    <span>{{ __('keramine.nav.home') }}</span>
                </a>
                <a href="{{ route('recommender') }}" class="inline-flex items-center gap-1.5 px-2.5 py-2 rounded-lg whitespace-nowrap shrink-0 transition {{ request()->routeIs('recommender*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1F2433]' }}">
                    <x-icon name="sparkles" class="w-3.5 h-3.5 shrink-0" />
                    <span>{{ __('keramine.nav.recommender') }}</span>
                </a>
                <a href="{{ route('stores') }}" class="inline-flex items-center gap-1.5 px-2.5 py-2 rounded-lg whitespace-nowrap shrink-0 transition {{ request()->routeIs('stores*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1F2433]' }}">
                    <x-icon name="store" class="w-3.5 h-3.5 shrink-0" />
                    <span>{{ __('keramine.nav.stores') }}</span>
                </a>
                <a href="{{ route('rules') }}" class="inline-flex items-center gap-1.5 px-2.5 py-2 rounded-lg whitespace-nowrap shrink-0 transition {{ request()->routeIs('rules*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1F2433]' }}">
                    <x-icon name="network" class="w-3.5 h-3.5 shrink-0" />
                    <span>{{ __('keramine.nav.rules') }}</span>
                </a>
                <a href="{{ route('history') }}" class="inline-flex items-center gap-1.5 px-2.5 py-2 rounded-lg whitespace-nowrap shrink-0 transition {{ request()->routeIs('history*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1F2433]' }}">
                    <x-icon name="clock" class="w-3.5 h-3.5 shrink-0" />
                    <span>{{ __('keramine.nav.history') }}</span>
                </a>
                <a href="{{ route('pipeline') }}" class="inline-flex items-center gap-1.5 px-2.5 py-2 rounded-lg whitespace-nowrap shrink-0 transition {{ request()->routeIs('pipeline*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1F2433]' }}">
                    <x-icon name="activity" class="w-3.5 h-3.5 shrink-0" />
                    <span>{{ __('keramine.nav.pipeline') }}</span>
                </a>
                <a href="{{ $labUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 px-2.5 py-2 rounded-lg whitespace-nowrap shrink-0 text-indigo-600 dark:text-cyan-300 hover:bg-indigo-50 dark:hover:bg-cyan-950/40 font-semibold transition">
                    <x-icon name="chart" class="w-3.5 h-3.5 shrink-0" />
                    <span>{{ __('keramine.nav.lab') }}</span>
                    <x-icon name="external" class="w-3 h-3 shrink-0 opacity-75" />
                </a>
            </nav>

            <!-- Right Controls -->
            <div class="flex items-center gap-2 shrink-0 whitespace-nowrap">
                <div class="inline-flex items-center rounded-lg border border-slate-200 dark:border-[#2A2F3E] p-0.5 bg-slate-100 dark:bg-[#0F1117] text-xs font-mono font-bold">
                    <a href="{{ request()->fullUrlWithQuery(['lang' => 'id']) }}" class="px-2 py-1 rounded-md transition {{ $currLocale === 'id' ? 'bg-white dark:bg-[#1A1D27] text-indigo-600 dark:text-cyan-300 shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' }}">ID</a>
                    <a href="{{ request()->fullUrlWithQuery(['lang' => 'en']) }}" class="px-2 py-1 rounded-md transition {{ $currLocale === 'en' ? 'bg-white dark:bg-[#1A1D27] text-indigo-600 dark:text-cyan-300 shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' }}">EN</a>
                </div>

                <button type="button" onclick="toggleKeraMineTheme()" aria-label="Toggle color theme" title="Light Mode (Opsi C) / Dark Mode (Opsi B)" class="w-9 h-9 inline-flex items-center justify-center rounded-lg border border-slate-200 dark:border-[#2A2F3E] bg-slate-100 dark:bg-[#1A1D27] text-slate-700 dark:text-cyan-300 hover:bg-slate-200 dark:hover:bg-[#222634] transition shrink-0">
                    <span class="dark:hidden"><x-icon name="moon" class="w-4 h-4" /></span>
                    <span class="hidden dark:inline"><x-icon name="sun" class="w-4 h-4" /></span>
                </button>

                @auth
                    <form action="{{ route('logout') }}" method="POST" class="hidden sm:inline shrink-0">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800 whitespace-nowrap hover:opacity-90 transition">
                            <span>{{ __('keramine.nav.logout') }}</span>
                            <span class="font-mono text-[10px] opacity-80">({{ auth()->user()->pof_name ?? auth()->user()->role }})</span>
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="hidden sm:inline-flex items-center px-3.5 py-1.5 rounded-lg text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-white dark:bg-indigo-500 dark:hover:bg-indigo-400 whitespace-nowrap transition">
                        {{ __('keramine.nav.login') }}
                    </a>
                @endauth

                <button type="button" onclick="toggleMobileMenu()" aria-label="Open menu" class="lg:hidden w-9 h-9 inline-flex items-center justify-center rounded-lg border border-slate-200 dark:border-[#2A2F3E] text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-[#222634] transition">
                    <x-icon name="menu" class="w-5 h-5" />
                </button>
            </div>
        </div>

        <!-- Mobile Collapsible Drawer -->
        <div id="mobile-nav-drawer" class="hidden lg:hidden border-t border-slate-200 dark:border-[#2A2F3E] bg-white dark:bg-[#1A1D27] px-4 py-3 space-y-1 text-sm font-medium">
            <a href="{{ route('home') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg {{ request()->routeIs('home') ? 'bg-indigo-600 text-white' : 'text-slate-700 dark:text-slate-200' }}"><x-icon name="home" class="w-4 h-4" /> {{ __('keramine.nav.home') }}</a>
            <a href="{{ route('recommender') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg {{ request()->routeIs('recommender*') ? 'bg-indigo-600 text-white' : 'text-slate-700 dark:text-slate-200' }}"><x-icon name="sparkles" class="w-4 h-4" /> {{ __('keramine.nav.recommender') }}</a>
            <a href="{{ route('stores') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg {{ request()->routeIs('stores*') ? 'bg-indigo-600 text-white' : 'text-slate-700 dark:text-slate-200' }}"><x-icon name="store" class="w-4 h-4" /> {{ __('keramine.nav.stores') }}</a>
            <a href="{{ route('rules') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg {{ request()->routeIs('rules*') ? 'bg-indigo-600 text-white' : 'text-slate-700 dark:text-slate-200' }}"><x-icon name="network" class="w-4 h-4" /> {{ __('keramine.nav.rules') }}</a>
            <a href="{{ route('history') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg {{ request()->routeIs('history*') ? 'bg-indigo-600 text-white' : 'text-slate-700 dark:text-slate-200' }}"><x-icon name="clock" class="w-4 h-4" /> {{ __('keramine.nav.history') }}</a>
            <a href="{{ route('pipeline') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg {{ request()->routeIs('pipeline*') ? 'bg-indigo-600 text-white' : 'text-slate-700 dark:text-slate-200' }}"><x-icon name="activity" class="w-4 h-4" /> {{ __('keramine.nav.pipeline') }}</a>
            <a href="{{ $labUrl }}" target="_blank" rel="noopener" class="flex items-center gap-2 px-3 py-2 rounded-lg text-indigo-600 dark:text-cyan-300"><x-icon name="chart" class="w-4 h-4" /> {{ __('keramine.nav.lab') }}</a>
            @auth
                <form action="{{ route('logout') }}" method="POST" class="pt-2">
                    @csrf
                    <button type="submit" class="w-full px-3 py-2 rounded-lg bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 font-semibold text-left">{{ __('keramine.nav.logout') }} ({{ auth()->user()->pof_name }})</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="flex items-center justify-center mt-2 px-3 py-2 rounded-lg bg-slate-900 dark:bg-indigo-500 text-white font-semibold">{{ __('keramine.nav.login') }}</a>
            @endauth
        </div>
    </header>

    @if(session('status'))
        <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 mt-4 no-print">
            <div class="px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-300 dark:border-emerald-700 text-emerald-800 dark:text-emerald-200 text-xs font-medium flex items-center gap-2">
                <x-icon name="check" class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                <span>{{ session('status') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 mt-4 no-print">
            <div class="px-4 py-3 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-300 dark:border-rose-700 text-rose-800 dark:text-rose-200 text-xs font-medium flex items-center gap-2">
                <x-icon name="alert" class="w-4 h-4 text-rose-600 dark:text-rose-400 shrink-0" />
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @yield('content')
    </main>

    <footer class="border-t border-slate-200 dark:border-[#2A2F3E] bg-white/60 dark:bg-[#131620]/60 py-6 mt-12 no-print">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500 dark:text-slate-400">
            <div>
                <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('keramine.app_name') }}</span> — Multi-Level SOTA Association Rule Mining · UAS Data Mining · Teknik Informatika UIN Suska Riau
            </div>
            <div class="flex flex-wrap items-center gap-4">
                <a href="{{ $labUrl }}" target="_blank" rel="noopener" class="hover:text-indigo-600 dark:hover:text-cyan-300 inline-flex items-center gap-1">
                    Streamlit Lab (:8501) <x-icon name="external" class="w-3 h-3" />
                </a>
                <a href="{{ $n8nUrl }}" target="_blank" rel="noopener" class="hover:text-indigo-600 dark:hover:text-cyan-300 inline-flex items-center gap-1">
                    n8n Automation (:5678) <x-icon name="external" class="w-3 h-3" />
                </a>
                <a href="{{ config('keramine.fastapi_url') }}/docs" target="_blank" rel="noopener" class="hover:text-indigo-600 dark:hover:text-cyan-300 inline-flex items-center gap-1">
                    FastAPI Docs (:8001) <x-icon name="external" class="w-3 h-3" />
                </a>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>

