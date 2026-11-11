@extends('layouts.app')
@section('title', __('keramine.nav.recommender'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white">Mesin Rekomendasi Bundling & Restocking B2B</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Menghasilkan pasangan produk keramik dari aturan SOTA tervalidasi Fisher & Benjamini-Hochberg atau Volume Popularity Fallback secara transparan.
            </p>
        </div>
        <span class="self-start px-3 py-1 rounded-full text-[11px] font-mono font-bold uppercase {{ $result['source_mode'] === 'fastapi' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300' }}">
            MODE: {{ strtoupper($result['source_mode']) }}
        </span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Form Konfigurasi -->
        <form method="GET" action="{{ route('recommender') }}" class="lg:col-span-5 km-card p-6 space-y-4">
            <input type="hidden" name="run" value="1">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <x-icon name="sparkles" class="w-4 h-4 text-indigo-600 dark:text-cyan-400" />
                Parameter Rekomendasi B2B
            </h2>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Pilih Toko B2B (Opsional — Auto-Deteksi Profil Pembelian)
                </label>
                <select name="nama_toko" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-[#2A2F3E] bg-white dark:bg-[#0F1117] text-xs">
                    <option value="">— Mode Global (Tanpa Filter Toko) —</option>
                    @foreach($stores as $s)
                        <option value="{{ $s['nama_toko'] }}" {{ $selectedToko === $s['nama_toko'] ? 'selected' : '' }}>
                            [POF {{ $s['dominant_pof'] }}] {{ $s['nama_toko'] }} ({{ number_format($s['total_qty_dus']) }} DUS)
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Granularitas Keranjang</label>
                    <select name="basket_type" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-[#2A2F3E] bg-white dark:bg-[#0F1117] text-xs">
                        <option value="store_month" {{ $basketType === 'store_month' ? 'selected' : '' }}>Toko + Bulan (Strategis, 3.949)</option>
                        <option value="store_week" {{ $basketType === 'store_week' ? 'selected' : '' }}>Toko + Minggu (Taktis, 10.475)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Level Item Hierarki</label>
                    <select name="item_level" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-[#2A2F3E] bg-white dark:bg-[#0F1117] text-xs">
                        <option value="SERI" {{ $itemLevel === 'SERI' ? 'selected' : '' }}>SERI (Rekomendasi Utama)</option>
                        <option value="MERK_UKURAN" {{ $itemLevel === 'MERK_UKURAN' ? 'selected' : '' }}>MERK_UKURAN</option>
                        <option value="MERK" {{ $itemLevel === 'MERK' ? 'selected' : '' }}>MERK</option>
                        <option value="UKURAN" {{ $itemLevel === 'UKURAN' ? 'selected' : '' }}>UKURAN</option>
                        <option value="NAMABARA" {{ $itemLevel === 'NAMABARA' ? 'selected' : '' }}>NAMABARA (SKU Penuh)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Item Pemicu Keranjang (Pisahkan koma untuk multi-item, kosongkan untuk Auto-Profile Toko)
                </label>
                <input type="text" name="trigger_items" id="trigger_items_input" value="{{ $triggerInput }}" list="catalog_suggestions" placeholder="Misal: DIEGO atau CUBIC, VINYL" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-[#2A2F3E] bg-white dark:bg-[#0F1117] text-xs font-mono">
                <datalist id="catalog_suggestions">
                    @foreach($catalogItems as $cat)
                        <option value="{{ $cat['name'] }}">
                    @endforeach
                </datalist>

                <div class="flex flex-wrap gap-1.5 mt-2">
                    @foreach(['DIEGO', 'CUBIC', 'VINYL', 'ALBASIA', 'ELPASO', 'KRUING'] as $preset)
                        <button type="button" onclick="document.getElementById('trigger_items_input').value='{{ $preset }}'" class="px-2 py-1 rounded-md text-[10px] font-mono font-semibold bg-slate-100 dark:bg-[#131620] hover:bg-indigo-100 dark:hover:bg-indigo-950/60 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-[#2A2F3E]">
                            {{ $preset }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Jumlah Rekomendasi (Top-K)</label>
                <input type="number" name="top_k" value="{{ $topK }}" min="1" max="15" class="w-28 px-3 py-2 rounded-xl border border-slate-200 dark:border-[#2A2F3E] bg-white dark:bg-[#0F1117] text-xs font-mono">
            </div>

            <button type="submit" class="w-full py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm transition">
                Hitung Rekomendasi Bundling
            </button>
        </form>

        <!-- Hasil Rekomendasi + Simpan -->
        <div class="lg:col-span-7 space-y-5">
            @if(! empty($result['store_profile']))
                @php($sp = $result['store_profile'])
                <div class="km-card p-5 bg-slate-50/70 dark:bg-[#131620]">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <div class="text-[11px] font-mono text-indigo-600 dark:text-cyan-400">PROFIL TOKO · POF {{ $sp['dominant_pof'] }} · {{ $sp['total_baskets_month'] }} KERANJANG BULAN</div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ $sp['nama_toko'] }}</h3>
                        </div>
                        <div class="text-right font-mono-num">
                            <div class="text-sm font-extrabold">{{ number_format($sp['total_qty_dus']) }} DUS</div>
                            <div class="text-[11px] text-slate-500">{{ $sp['total_unique_skus'] }} SKU unik</div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="km-card p-6">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">Hasil Rekomendasi Cross-Selling</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Pemicu aktif:
                            <span class="font-mono font-semibold text-indigo-600 dark:text-cyan-300">
                                {{ ! empty($result['triggers']) ? implode(', ', $result['triggers']) : 'GLOBAL / AUTO' }}
                            </span>
                        </p>
                    </div>
                </div>

                <div class="space-y-3">
                    @forelse($result['recommendations'] as $idx => $rec)
                        @php($isRule = $rec['lift'] !== null)
                        <div class="p-4 rounded-xl border {{ $isRule ? 'border-slate-200 dark:border-[#2A2F3E] bg-white dark:bg-[#131620]' : 'border-amber-200 dark:border-amber-900/60 bg-amber-50/40 dark:bg-amber-950/20' }}">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-7 h-7 rounded-lg bg-indigo-50 dark:bg-indigo-950/70 text-indigo-700 dark:text-cyan-300 font-mono font-bold text-xs flex items-center justify-center">
                                        #{{ $idx + 1 }}
                                    </span>
                                    <div>
                                        <div class="font-extrabold text-sm text-slate-900 dark:text-white">{{ $rec['recommended_item'] }}</div>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400">
                                            {{ $rec['antecedents_str'] }} → {{ $rec['recommended_item'] }}
                                        </div>
                                    </div>
                                </div>
                                <span class="self-start sm:self-center px-2.5 py-1 rounded-full text-[10px] font-semibold {{ str_contains($rec['source_type'], 'Significant') ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300' : (str_contains($rec['source_type'], 'Fallback') ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300' : 'bg-slate-100 text-slate-700 dark:bg-[#222634] dark:text-slate-300') }}">
                                    {{ $rec['source_type'] }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 mt-3 pt-3 border-t border-slate-100 dark:border-[#222634] text-[11px] font-mono-num">
                                <div>
                                    <span class="text-slate-400 block">Confidence</span>
                                    <span class="font-bold">{{ $rec['confidence'] !== null ? number_format($rec['confidence'] * 100, 1) . '%' : 'N/A' }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block">Lift</span>
                                    <span class="font-bold text-indigo-600 dark:text-cyan-300">{{ $rec['lift'] !== null ? number_format($rec['lift'], 2) . 'x' : 'N/A' }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block">Zhang</span>
                                    <span class="font-bold">{{ $rec['zhang_metric'] !== null ? number_format($rec['zhang_metric'], 3) : 'N/A' }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block">Est. Vol/Bsk</span>
                                    <span class="font-bold">{{ $rec['avg_qty_dus'] !== null ? number_format($rec['avg_qty_dus'], 1) . ' DUS' : '-' }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block">Fisher p-val</span>
                                    <span class="font-bold">{{ $rec['p_value_fisher'] !== null ? number_format($rec['p_value_fisher'], 4) : 'Fallback' }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-xs text-slate-500">{{ __('keramine.common.no_data') }}</div>
                    @endforelse
                </div>

                @if(! empty($result['recommendations']))
                    <form method="POST" action="{{ route('recommender') }}" class="mt-6 pt-5 border-t border-slate-200 dark:border-[#2A2F3E] space-y-3">
                        @csrf
                        <input type="hidden" name="save_plan" value="1">
                        <input type="hidden" name="nama_toko" value="{{ $selectedToko }}">
                        <input type="hidden" name="basket_type" value="{{ $basketType }}">
                        <input type="hidden" name="item_level" value="{{ $itemLevel }}">
                        <input type="hidden" name="trigger_items" value="{{ implode(', ', $result['triggers']) }}">
                        <input type="hidden" name="top_k" value="{{ $topK }}">

                        <div class="text-xs font-bold text-slate-900 dark:text-white">Simpan Rencana Restock ke Riwayat SQLite</div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <input type="text" name="plan_title" placeholder="Judul rencana (opsional, otomatis jika kosong)" class="px-3.5 py-2 rounded-xl border border-slate-200 dark:border-[#2A2F3E] bg-white dark:bg-[#0F1117] text-xs">
                            <input type="text" name="notes" placeholder="Catatan untuk sales/gudang (opsional)" class="px-3.5 py-2 rounded-xl border border-slate-200 dark:border-[#2A2F3E] bg-white dark:bg-[#0F1117] text-xs">
                        </div>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition">
                            Simpan Rencana & Lihat Riwayat
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

