@extends('layouts.app')
@section('title', __('keramine.nav.rules'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white">Eksplorasi Katalog Aturan Asosiasi SOTA</h1>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold uppercase {{ $rulesSource === 'supabase_rest' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300' }}">
                    {{ $rulesSource === 'supabase_rest' ? __('keramine.common.source_live_supa') : __('keramine.common.source_fallback') }}
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Audit kejujuran ilmiah: level SERI menghasilkan aturan paling diskriminatif dibanding level MERK/UKURAN yang homogen.
            </p>
        </div>

        <div class="text-xs font-mono-num text-right">
            <div class="font-bold text-slate-900 dark:text-white">{{ $totalMatched }} aturan cocok · {{ $significantCount }} signifikan</div>
            <div class="text-[11px] text-slate-500">Uji statistik Fisher + Benjamini-Hochberg</div>
        </div>
    </div>

    <form method="GET" action="{{ route('rules') }}" class="km-card p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
        <div class="lg:col-span-3">
            <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 mb-1">Granularitas Keranjang</label>
            <select name="basket_type" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-[#2A2F3E] bg-white dark:bg-[#0F1117] text-xs">
                <option value="store_month" {{ $basketType === 'store_month' ? 'selected' : '' }}>Toko + Bulan (store_month)</option>
                <option value="store_week" {{ $basketType === 'store_week' ? 'selected' : '' }}>Toko + Minggu (store_week)</option>
                <option value="ALL" {{ $basketType === 'ALL' ? 'selected' : '' }}>Semua Granularitas</option>
            </select>
        </div>
        <div class="lg:col-span-2">
            <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 mb-1">Level Item</label>
            <select name="item_level" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-[#2A2F3E] bg-white dark:bg-[#0F1117] text-xs">
                <option value="SERI" {{ $itemLevel === 'SERI' ? 'selected' : '' }}>SERI</option>
                <option value="MERK_UKURAN" {{ $itemLevel === 'MERK_UKURAN' ? 'selected' : '' }}>MERK_UKURAN</option>
                <option value="NAMABARA" {{ $itemLevel === 'NAMABARA' ? 'selected' : '' }}>NAMABARA</option>
                <option value="MERK" {{ $itemLevel === 'MERK' ? 'selected' : '' }}>MERK</option>
                <option value="UKURAN" {{ $itemLevel === 'UKURAN' ? 'selected' : '' }}>UKURAN</option>
                <option value="ALL" {{ $itemLevel === 'ALL' ? 'selected' : '' }}>Semua Level</option>
            </select>
        </div>
        <div class="lg:col-span-2">
            <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 mb-1">Cari Item</label>
            <input type="text" name="q" value="{{ $q }}" placeholder="DIEGO, CUBIC..." class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-[#2A2F3E] bg-white dark:bg-[#0F1117] text-xs">
        </div>
        <div class="lg:col-span-1">
            <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 mb-1">Min Lift</label>
            <input type="number" step="0.1" name="min_lift" value="{{ $minLift }}" class="w-full px-2.5 py-2 rounded-xl border border-slate-200 dark:border-[#2A2F3E] bg-white dark:bg-[#0F1117] text-xs font-mono">
        </div>
        <div class="lg:col-span-2">
            <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 mb-1">Urutkan</label>
            <select name="sort" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-[#2A2F3E] bg-white dark:bg-[#0F1117] text-xs">
                <option value="utility" {{ $sort === 'utility' ? 'selected' : '' }}>Utility Score (QTY-weighted)</option>
                <option value="lift" {{ $sort === 'lift' ? 'selected' : '' }}>Lift Tertinggi</option>
                <option value="zhang" {{ $sort === 'zhang' ? 'selected' : '' }}>Zhang’s Metric</option>
                <option value="confidence" {{ $sort === 'confidence' ? 'selected' : '' }}>Confidence</option>
                <option value="support" {{ $sort === 'support' ? 'selected' : '' }}>Support</option>
            </select>
        </div>
        <div class="lg:col-span-2 flex items-center gap-2">
            <label class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-slate-600 dark:text-slate-300 shrink-0">
                <input type="checkbox" name="fdr_only" value="1" {{ $fdrOnly ? 'checked' : '' }} class="rounded text-indigo-600">
                Sig. Saja
            </label>
            <button type="submit" class="flex-1 px-3 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold">
                Filter
            </button>
        </div>
    </form>

    <div class="km-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-[#131620] text-slate-500 dark:text-slate-400 uppercase font-mono text-[10px]">
                    <tr>
                        <th class="px-4 py-3">Level</th>
                        <th class="px-4 py-3">Antecedent → Consequent</th>
                        <th class="px-4 py-3 text-right">n(A,B)</th>
                        <th class="px-4 py-3 text-right">Supp</th>
                        <th class="px-4 py-3 text-right">Conf</th>
                        <th class="px-4 py-3 text-right">Lift</th>
                        <th class="px-4 py-3 text-right">Zhang</th>
                        <th class="px-4 py-3 text-right">Utility</th>
                        <th class="px-4 py-3 text-right">p-Fisher / q-BH</th>
                        <th class="px-4 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-[#242938]">
                    @forelse($rules as $r)
                        @php($sig = $r['is_significant'] || $r['is_fdr_significant'])
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-[#1F2433]/50">
                            <td class="px-4 py-2.5 font-mono text-[10px] text-slate-500">{{ $r['item_level'] }}</td>
                            <td class="px-4 py-2.5 font-bold text-slate-900 dark:text-white">
                                <a href="{{ route('recommender', ['trigger_items' => $r['antecedents_str'], 'item_level' => $r['item_level'], 'basket_type' => $r['basket_type'], 'run' => 1]) }}" class="hover:text-indigo-600 dark:hover:text-cyan-300">
                                    {{ $r['antecedents_str'] }} → {{ $r['consequents_str'] }}
                                </a>
                            </td>
                            <td class="px-4 py-2.5 text-right font-mono-num">{{ $r['n_ab'] }}/{{ $r['total_baskets'] }}</td>
                            <td class="px-4 py-2.5 text-right font-mono-num">{{ number_format($r['support'], 4) }}</td>
                            <td class="px-4 py-2.5 text-right font-mono-num">{{ number_format($r['confidence'] * 100, 1) }}%</td>
                            <td class="px-4 py-2.5 text-right font-mono-num font-bold {{ $r['lift'] >= 1.5 ? 'text-indigo-600 dark:text-cyan-300' : '' }}">{{ number_format($r['lift'], 2) }}x</td>
                            <td class="px-4 py-2.5 text-right font-mono-num {{ $r['zhang_metric'] > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500' }}">{{ number_format($r['zhang_metric'], 3) }}</td>
                            <td class="px-4 py-2.5 text-right font-mono-num">{{ number_format($r['utility_score'], 4) }}</td>
                            <td class="px-4 py-2.5 text-right font-mono-num text-[11px]">{{ number_format($r['p_value_fisher'], 4) }} / {{ number_format($r['q_value_bh'], 4) }}</td>
                            <td class="px-4 py-2.5 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $sig ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-[#222634] dark:text-slate-400' }}">
                                    {{ $sig ? 'SIGNIFIKAN' : 'EKSPLORATIF' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="px-4 py-8 text-center text-slate-500">{{ __('keramine.common.no_data') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

