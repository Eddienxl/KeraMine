@extends('layouts.app')
@section('title', __('keramine.nav.login'))

@section('content')
<div class="max-w-xl mx-auto space-y-6">
    <div class="km-card p-7">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-cyan-300 flex items-center justify-center">
                <x-icon name="shield" class="w-5 h-5" />
            </div>
            <div>
                <h1 class="text-xl font-extrabold text-slate-900 dark:text-white">{{ __('keramine.auth.title') }}</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('keramine.auth.subtitle') }}</p>
            </div>
        </div>

        <div class="mt-6 p-4 rounded-xl bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800 space-y-2.5">
            <div class="text-xs font-bold uppercase tracking-wider text-indigo-700 dark:text-cyan-300">
                {{ __('keramine.auth.quick_title') }}
            </div>
            <div class="grid grid-cols-1 gap-2">
                <form action="{{ route('login.post') }}" method="POST">
                    @csrf
                    <input type="hidden" name="quick_role" value="admin">
                    <button type="submit" class="w-full px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold flex items-center justify-between transition">
                        <span>{{ __('keramine.auth.quick_admin') }}</span>
                        <x-icon name="arrow-right" class="w-4 h-4" />
                    </button>
                </form>
                <form action="{{ route('login.post') }}" method="POST">
                    @csrf
                    <input type="hidden" name="quick_role" value="dian">
                    <button type="submit" class="w-full px-4 py-2.5 rounded-xl bg-white dark:bg-[#131620] hover:border-indigo-500 border border-slate-200 dark:border-[#2A2F3E] text-slate-800 dark:text-slate-200 text-xs font-semibold flex items-center justify-between transition">
                        <span>{{ __('keramine.auth.quick_dian') }}</span>
                        <span class="font-mono text-[10px] text-emerald-600 dark:text-emerald-400">71 Toko</span>
                    </button>
                </form>
                <form action="{{ route('login.post') }}" method="POST">
                    @csrf
                    <input type="hidden" name="quick_role" value="robby">
                    <button type="submit" class="w-full px-4 py-2.5 rounded-xl bg-white dark:bg-[#131620] hover:border-indigo-500 border border-slate-200 dark:border-[#2A2F3E] text-slate-800 dark:text-slate-200 text-xs font-semibold flex items-center justify-between transition">
                        <span>{{ __('keramine.auth.quick_robby') }}</span>
                        <span class="font-mono text-[10px] text-amber-600 dark:text-amber-400">55 Toko</span>
                    </button>
                </form>
            </div>
        </div>

        <form action="{{ route('login.post') }}" method="POST" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">{{ __('keramine.auth.email') }}</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-[#2A2F3E] bg-white dark:bg-[#0F1117] text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('email')<p class="text-xs text-rose-500 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">{{ __('keramine.auth.password') }}</label>
                <input type="password" name="password" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-[#2A2F3E] bg-white dark:bg-[#0F1117] text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <label class="inline-flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                <input type="checkbox" name="remember" class="rounded border-slate-300 text-indigo-600">
                <span>{{ __('keramine.auth.remember') }}</span>
            </label>
            <button type="submit" class="w-full py-2.5 rounded-xl bg-slate-900 dark:bg-indigo-500 hover:opacity-95 text-white text-sm font-semibold transition">
                {{ __('keramine.auth.submit') }}
            </button>
        </form>
    </div>
</div>
@endsection

