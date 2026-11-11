@extends('layouts.app')
@section('title', __('keramine.nav.stores'))

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white">Profil 237 Toko B2B & Katalog Keramik</h1>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold uppercase {{ $storesSource === 'supabase_rest' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300' }}">
                    {{ $storesSource === 'supabase_rest' ? __('keramine.common.source_live_supa') : __('keramine.common.source_fallback') }}
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Segmentasi Sales POF ({{ implode(', ', $allPofs) }}) serta agregasi volume seri, merek, dan ukuran keramik.
            </p>
        </div>

        <!-- Tabs -->
        <div class="inline-flex rounded-xl border border-slate-200 dark:border-[#2A2F3E] p-1 bg-white dark:bg-[#1A1D27] text-xs font-semibold shrink-0">
            <a href="{{ route('stores', ['tab' => 'stores']) }}" class="px-3.5 py-1.5 rounded-lg transition {{ $tab === 'stores' ? 'bg-indigo-600 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#131620]' }}">
                Profil Toko ({{ $totalAllStores }})
            </a>
            <a href="{{ route('stores', ['tab' => 'catalog', 'level' => $catalogLevel]) }}" class="px-3.5 py-1.5 rounded-lg transition {{ $tab === 'catalog' ? 'bg-indigo-600 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#131620]' }}">
                Katalog Item ({{ $catalogUnique }})
            </a>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <form method="GET" action="{{ route('stores') }}" class="km-card p-4 grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div class="md:col-span-5">
            <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 mb-1">Kata Kunci</label>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama toko, seri DIEGO, merek PICASSO..." class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-[#2A2F3E] bg-white dark:bg-[#0F1117] text-xs">
        </div>

        @if($tab === 'stores')
            <div class="md:col-span-3">
                <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 mb-1">Sales POF</label>
                <select name="pof" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-[#2A2F3E] bg-white dark:bg-[#0F1117] text-xs">
                    <option value="ALL">Semua POF ({{ $totalAllStores }})</option>
                    @foreach($allPofs as $p)
                        <option value="{{ $p }}" {{ $pofFilter === $p ? 'selected' : '' }}>{{ $p }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 mb-1">Urutkan</label>
                <select name="sort" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-[#2A2F3E] bg-white dark:bg-[#0F1117] text-xs">
                    <option value="qty_desc" {{ request('sort') === 'qty_desc' ? 'selected' : '' }}>Total DUS</option>
                    <option value="tx_desc" {{ request('sort') === 'tx_desc' ? 'selected' : '' }}>Frekuensi Tx</option>
                    <option value="baskets_desc" {{ request('sort') === 'baskets_desc' ? 'selected' : '' }}>Keranjang Bulan</option>
                    <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>Nama A–Z</option>
                </select>
            </div>
        @else
            <div class="md:col-span-5">
                <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 mb-1">Level Hierarki Item</label>
                <select name="level" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-[#2A2F3E] bg-white dark:bg-[#0F1117] text-xs">
                    <option value="SERI" {{ $catalogLevel === 'SERI' ? 'selected' : '' }}>SERI (220 seri desain keramik)</option>
                    <option value="MERK" {{ $catalogLevel === 'MERK' ? 'selected' : '' }}>MERK (PICASSO, KIA, UNO, PLATINUM, ...)</option>
                    <option value="UKURAN" {{ $catalogLevel === 'UKURAN' ? 'selected' : '' }}>UKURAN (40X40, 25X40, 50X50, ...)</option>
                    <option value="MERK_UKURAN" {{ $catalogLevel === 'MERK_UKURAN' ? 'selected' : '' }}>MERK_UKURAN</option>
                    <option value="NAMABARA" {{ $catalogLevel === 'NAMABARA' ? 'selected' : '' }}>NAMABARA (438 SKU penuh)</option>
                </select>
            </div>
        @endif

        <div class="md:col-span-2 flex gap-2">
            <button type="submit" class="flex-1 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold transition">
                {{ __('keramine.common.filter') }}
            </button>
            <a href="{{ route('stores', ['tab' => $tab]) }}" class="px-3 py-2 rounded-xl border border-slate-200 dark:border-[#2A2F3E] text-xs font-semibold text-slate-600 dark:text-slate-300">
                Reset
            </a>
        </div>
    </form>

    @if($selectedStore)
        <div class="km-card p-6 border-indigo-400 dark:border-cyan-500/60 bg-indigo-50/30 dark:bg-[#191E2D]">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
                <div>
                    <div class="text-xs font-mono font-bold text-indigo-600 dark:text-cyan-400">DETAIL TOKO · KODE {{ $selectedStore['kode_toko'] ?? '-' }} · POF {{ $selectedStore['dominant_pof'] ?? '-' }}</div>
                    <h2 class="text-xl font-extrabold text-slate-900 dark:text-white">{{ $selectedStore['nama_toko'] }}</h2>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('recommender', ['nama_toko' => $selectedStore['nama_toko'], 'run' => 1]) }}" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold inline-flex items-center gap-1.5">
                        <x-icon name="sparkles" class="w-4 h-4" />
                        Buat Rekomendasi Bundling Toko Ini
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                <div class="p-4 rounded-xl bg-white dark:bg-[#131620] border border-slate-200 dark:border-[#2A2F3E]">
                    <div class="font-bold text-slate-700 dark:text-slate-200 mb-2">Top Seri Favorit</div>
                    @foreach(($selectedStore['top_seris'] ?? []) as $item)
                        <div class="flex justify-between py-1 border-b border-slate-100 dark:border-[#222634] last:border-0">
                            <span class="font-medium">{{ $item['name'] ?? '-' }}</span>
                            <span class="font-mono-num text-indigo-600 dark:text-cyan-300">{{ number_format($item['qty_dus'] ?? 0) }} DUS</span>
                        </div>
                    @endforeach
                </div>
                <div class="p-4 rounded-xl bg-white dark:bg-[#131620] border border-slate-200 dark:border-[#2A2F3E]">
                    <div class="font-bold text-slate-700 dark:text-slate-200 mb-2">Top Merek Keramik</div>
                    @foreach(($selectedStore['top_merks'] ?? []) as $item)
                        <div class="flex justify-between py-1 border-b border-slate-100 dark:border-[#222634] last:border-0">
                            <span class="font-medium">{{ $item['name'] ?? '-' }}</span>
                            <span class="font-mono-num text-emerald-600 dark:text-emerald-400">{{ number_format($item['qty_dus'] ?? 0) }} DUS</span>
                        </div>
                    @endforeach
                </div>
                <div class="p-4 rounded-xl bg-white dark:bg-[#131620] border border-slate-200 dark:border-[#2A2F3E]">
                    <div class="font-bold text-slate-700 dark:text-slate-200 mb-2">Top Ukuran</div>
                    @foreach(($selectedStore['top_ukurans'] ?? []) as $item)
                        <div class="flex justify-between py-1 border-b border-slate-100 dark:border-[#222634] last:border-0">
                            <span class="font-medium">{{ $item['name'] ?? '-' }}</span>
                            <span class="font-mono-num text-amber-600 dark:text-amber-300">{{ number_format($item['qty_dus'] ?? 0) }} DUS</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @if($tab === 'stores')
        <div class="text-xs text-slate-500 dark:text-slate-400">
            Menampilkan <span class="font-mono font-bold text-slate-800 dark:text-white">{{ count($stores) }}</span> dari {{ $totalFilteredStores }} toko cocok (Total {{ $totalAllStores }} toko aktif).
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($stores as $store)
                @php
                    $topSeri = $store['top_seris'][0]['name'] ?? '-';
                    $topMerk = $store['top_merks'][0]['name'] ?? '-';
                @endphp
                <div class="km-card p-5 flex flex-col justify-between hover:border-indigo-400 dark:hover:border-cyan-500/60 transition">
                    <div>
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-cyan-300">
                                POF {{ $store['dominant_pof'] ?? '-' }}
                            </span>
                            <span class="text-[11px] font-mono text-slate-400">#{{ $store['kode_toko'] ?? '-' }}</span>
                        </div>
                        <h3 class="font-bold text-sm text-slate-900 dark:text-white line-clamp-1">{{ $store['nama_toko'] }}</h3>

                        <div class="grid grid-cols-3 gap-2 my-3 p-2.5 rounded-xl bg-slate-50 dark:bg-[#131620] text-center">
                            <div>
                                <div class="text-[10px] text-slate-400">Total DUS</div>
                                <div class="font-mono-num font-bold text-xs text-slate-900 dark:text-white">{{ number_format($store['total_qty_dus'] ?? 0) }}</div>
                            </div>
                            <div>
                                <div class="text-[10px] text-slate-400">Keranjang Bln</div>
                                <div class="font-mono-num font-bold text-xs text-slate-900 dark:text-white">{{ $store['total_baskets_month'] ?? 0 }}</div>
                            </div>
                            <div>
                                <div class="text-[10px] text-slate-400">SKU Unik</div>
                                <div class="font-mono-num font-bold text-xs text-slate-900 dark:text-white">{{ $store['total_unique_skus'] ?? 0 }}</div>
                            </div>
                        </div>

                        <div class="text-[11px] text-slate-600 dark:text-slate-300 space-y-1">
                            <div>Seri Utama: <span class="font-semibold text-indigo-600 dark:text-cyan-300">{{ $topSeri }}</span></div>
                            <div>Merek Utama: <span class="font-semibold">{{ $topMerk }}</span></div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-100 dark:border-[#242938] flex items-center justify-between gap-2">
                        <a href="{{ route('stores', ['tab' => 'stores', 'pof' => $pofFilter ?: 'ALL', 'store' => $store['nama_toko']]) }}" class="text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-indigo-600">
                            Profil Lengkap
                        </a>
                        <a href="{{ route('recommender', ['nama_toko' => $store['nama_toko'], 'run' => 1]) }}" class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold transition">
                            Rekomendasi →
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-full km-card p-8 text-center text-xs text-slate-500">{{ __('keramine.common.no_data') }}</div>
            @endforelse
        </div>
    @else
        <div class="km-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-[#131620] text-slate-500 dark:text-slate-400 uppercase font-mono text-[10px]">
                        <tr>
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Item ({{ $catalogLevel }})</th>
                            <th class="px-4 py-3">Sampel Merek & Ukuran</th>
                            <th class="px-4 py-3 text-right">Volume (DUS)</th>
                            <th class="px-4 py-3 text-right">Baris Tx</th>
                            <th class="px-4 py-3 text-right">Jangkauan Toko</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-[#242938]">
                        @forelse($catalogItems as $idx => $item)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-[#1F2433]/50">
                                <td class="px-4 py-3 font-mono text-slate-400">{{ $idx + 1 }}</td>
                                <td class="px-4 py-3 font-bold text-slate-900 dark:text-white">{{ $item['name'] }}</td>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $item['sample_merk'] }} · {{ $item['sample_ukuran'] }}</td>
                                <td class="px-4 py-3 text-right font-mono-num font-semibold">{{ number_format($item['total_qty_dus']) }}</td>
                                <td class="px-4 py-3 text-right font-mono-num">{{ number_format($item['tx_count']) }}</td>
                                <td class="px-4 py-3 text-right font-mono-num">{{ $item['store_count'] }} toko</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('recommender', ['trigger_items' => $item['name'], 'item_level' => $catalogLevel, 'run' => 1]) }}" class="px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-cyan-300 font-semibold">
                                        Cari Pasangan
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">{{ __('keramine.common.no_data') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection

