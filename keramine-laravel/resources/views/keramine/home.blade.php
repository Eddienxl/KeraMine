@extends('layouts.app')
@section('title', __('keramine.nav.home'))

@section('content')
<div class="space-y-8">
    <!-- Hero + Top Rules Showcase -->
    <section class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
        <div class="lg:col-span-7 km-card p-7 flex flex-col justify-between bg-gradient-to-br from-white via-indigo-50/40 to-slate-50 dark:from-[#1A1D27] dark:via-[#1E2235] dark:to-[#141720]">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-4">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/70 dark:text-cyan-300 border border-indigo-200 dark:border-indigo-800">
                        <x-icon name="sparkles" class="w-3.5 h-3.5" />
                        {{ __('keramine.hero.badge') }}
                    </span>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-mono font-bold uppercase {{ $health['supabase_online'] ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300' }}">
                        {{ $health['supabase_online'] ? __('keramine.common.source_live_supa') : __('keramine.common.source_fallback') }}
                    </span>
                </div>

                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white leading-tight mb-3">
                    {{ __('keramine.hero.title') }}
                </h1>
                <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed mb-6">
                    {{ __('keramine.hero.subtitle') }}
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('recommender') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-sm transition">
                    <x-icon name="sparkles" class="w-4 h-4" />
                    {{ __('keramine.hero.cta_recommender') }}
                </a>
                <a href="{{ route('stores') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white dark:bg-[#0F1117] border border-slate-200 dark:border-[#2A2F3E] text-slate-700 dark:text-slate-200 hover:border-indigo-400 text-sm font-semibold transition">
                    <x-icon name="store" class="w-4 h-4" />
                    {{ __('keramine.hero.cta_stores') }}
                </a>
                <a href="{{ config('keramine.streamlit_url') }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-900 dark:bg-cyan-500/20 text-white dark:text-cyan-300 border border-transparent dark:border-cyan-700 text-sm font-semibold transition">
                    <x-icon name="chart" class="w-4 h-4" />
                    {{ __('keramine.hero.cta_lab') }}
                    <x-icon name="external" class="w-3.5 h-3.5" />
                </a>
            </div>
        </div>

        <div class="lg:col-span-5 km-card p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between gap-2 mb-1">
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('keramine.hero.highlight_title') }}</h2>
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-slate-100 dark:bg-[#0F1117] text-slate-600 dark:text-slate-300">
                        {{ strtoupper($rulesSource) }}
                    </span>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">{{ __('keramine.hero.highlight_sub') }}</p>

                <div class="space-y-2.5">
                    @foreach($topRules as $idx => $rule)
                        <div class="p-3 rounded-xl border border-slate-200/80 dark:border-[#2A2F3E] bg-slate-50/70 dark:bg-[#131620] flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-[11px] font-mono font-bold text-indigo-600 dark:text-cyan-400">#{{ $idx + 1 }}</span>
                                    <span class="font-bold text-xs text-slate-900 dark:text-white truncate">
                                        {{ $rule['antecedents_str'] }} → {{ $rule['consequents_str'] }}
                                    </span>
                                </div>
                                <div class="text-[11px] font-mono-num text-slate-500 dark:text-slate-400 mt-0.5">
                                    Conf {{ number_format($rule['confidence'] * 100, 1) }}% · Lift {{ number_format($rule['lift'], 2) }}x · Zhang +{{ number_format($rule['zhang_metric'], 2) }}
                                </div>
                            </div>
                            <a href="{{ route('recommender', ['trigger_items' => $rule['antecedents_str'], 'item_level' => 'SERI', 'run' => 1]) }}" class="shrink-0 px-2.5 py-1.5 rounded-lg text-[11px] font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-cyan-300 hover:bg-indigo-100 transition">
                                Uji
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-200 dark:border-[#2A2F3E] flex items-center justify-between text-xs">
                <span class="text-slate-500 dark:text-slate-400">FastAPI :8001</span>
                <span class="inline-flex items-center gap-1.5 font-mono font-semibold {{ $health['fastapi_online'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                    <span class="w-2 h-2 rounded-full {{ $health['fastapi_online'] ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                    {{ $health['fastapi_online'] ? 'ONLINE' : 'STANDBY (FALLBACK)' }}
                </span>
            </div>
        </div>
    </section>

    <!-- 4 KPI Cards -->
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="km-card p-5">
            <div class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('keramine.metrics.tx_label') }}</div>
            <div class="text-2xl font-extrabold font-mono-num text-slate-900 dark:text-white mt-1">{{ __('keramine.metrics.tx_value') }}</div>
            <div class="text-xs text-emerald-600 dark:text-emerald-400 font-medium mt-1">{{ __('keramine.metrics.tx_sub') }}</div>
        </div>
        <div class="km-card p-5">
            <div class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('keramine.metrics.stores_label') }}</div>
            <div class="text-2xl font-extrabold font-mono-num text-slate-900 dark:text-white mt-1">{{ $storesCount }} Toko</div>
            <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ __('keramine.metrics.stores_sub') }}</div>
        </div>
        <div class="km-card p-5">
            <div class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('keramine.metrics.rules_label') }}</div>
            <div class="text-2xl font-extrabold font-mono-num text-indigo-600 dark:text-cyan-300 mt-1">{{ __('keramine.metrics.rules_value') }}</div>
            <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ __('keramine.metrics.rules_sub') }}</div>
        </div>
        <div class="km-card p-5">
            <div class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('keramine.metrics.precision_label') }}</div>
            <div class="text-2xl font-extrabold font-mono-num text-slate-900 dark:text-white mt-1">{{ __('keramine.metrics.precision_value') }}</div>
            <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ __('keramine.metrics.precision_sub') }}</div>
        </div>
    </section>

    <!-- Chart + Sales POF Breakdown + Quick Plans -->
    <section class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-7 km-card p-6">
            <div class="flex items-center justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Distribusi Volume Penjualan per Sales POF (DUS)</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Agregasi profil 237 toko aktif dari Supabase REST / JSON bersih</p>
                </div>
                <a href="{{ route('stores') }}" class="text-xs font-semibold text-indigo-600 dark:text-cyan-300 hover:underline">Lihat Semua Toko →</a>
            </div>
            <div class="h-64">
                <canvas id="pofVolumeChart"></canvas>
            </div>
        </div>

        <div class="lg:col-span-5 km-card p-6">
            <div class="flex items-center justify-between gap-2 mb-4">
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Riwayat Rencana Restock Terbaru</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Tersimpan di SQLite operasional lokal (Opsi 1)</p>
                </div>
                <a href="{{ route('history') }}" class="text-xs font-semibold text-indigo-600 dark:text-cyan-300 hover:underline">Kelola →</a>
            </div>

            <div class="space-y-3">
                @forelse($recentPlans as $plan)
                    <div class="p-3.5 rounded-xl border border-slate-200 dark:border-[#2A2F3E] bg-slate-50/70 dark:bg-[#131620]">
                        <div class="font-semibold text-xs text-slate-900 dark:text-white line-clamp-1">{{ $plan->plan_title }}</div>
                        <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                            <span>{{ $plan->nama_toko ?? 'GLOBAL' }} · POF {{ $plan->pof_name ?? '-' }}</span>
                            <span class="font-mono">{{ count($plan->recommendations_json ?? []) }} items</span>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('keramine.common.no_data') }}</p>
                @endforelse
            </div>

            <div class="mt-5 pt-4 border-t border-slate-200 dark:border-[#2A2F3E]">
                <a href="{{ route('pipeline') }}" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-[#0F1117] hover:bg-indigo-50 dark:hover:bg-indigo-950/50 text-xs font-semibold text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-[#2A2F3E] transition">
                    <x-icon name="activity" class="w-4 h-4 text-indigo-600 dark:text-cyan-400" />
                    Buka Simulator Pipeline Real-Time n8n + FastAPI
                </a>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const pofData = @json($pofStats);
    const ctx = document.getElementById('pofVolumeChart');
    if (!ctx || typeof Chart === 'undefined') return;

    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#94A3B8' : '#475569';
    const gridColor = isDark ? 'rgba(148,163,184,0.12)' : 'rgba(15,23,42,0.06)';

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: pofData.map(x => `${x.pof} (${x.store_count} toko)`),
            datasets: [{
                label: 'Total Volume (DUS)',
                data: pofData.map(x => x.total_dus),
                backgroundColor: ['#4F46E5', '#0EA5E9', '#10B981', '#F59E0B', '#8B5CF6'],
                borderRadius: 8,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { color: textColor }, grid: { display: false } },
                y: { ticks: { color: textColor }, grid: { color: gridColor } }
            }
        }
    });
});
</script>
@endpush

