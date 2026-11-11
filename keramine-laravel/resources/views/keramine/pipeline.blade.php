@extends('layouts.app')
@section('title', __('keramine.nav.pipeline'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white">Orkestrasi Pipeline Real-Time (FastAPI · Supabase · n8n)</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Uji ingest transaksi B2B baru secara langsung lewat n8n webhook atau FastAPI, amati re-mining SOTA di Supabase, dan reset baseline demo dalam 1 klik.
            </p>
        </div>

        <form action="{{ route('pipeline.reset') }}" method="POST" onsubmit="if(!confirm('Hapus seluruh transaksi simulasi dan pulihkan 784 aturan baseline dataset asli?')) return false; const b=this.querySelector('button[type=submit]'); if(b){b.disabled=true; b.classList.add('opacity-60');}">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-sm transition">
                <x-icon name="refresh" class="w-4 h-4" />
                Reset Demo ke Baseline (15.986 Tx)
            </button>
        </form>
    </div>

    <!-- Live Service Status -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="km-card p-5 flex items-center justify-between">
            <div>
                <div class="text-xs text-slate-500 dark:text-slate-400">FastAPI ARM Engine</div>
                <div class="text-sm font-mono font-bold mt-1">{{ config('keramine.fastapi_url') }}</div>
            </div>
            <span class="px-2.5 py-1 rounded-full text-[11px] font-mono font-bold {{ $health['fastapi_online'] ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950/70 dark:text-rose-300' }}">
                {{ $health['fastapi_online'] ? 'ONLINE' : 'OFFLINE' }}
            </span>
        </div>
        <div class="km-card p-5 flex items-center justify-between">
            <div>
                <div class="text-xs text-slate-500 dark:text-slate-400">Supabase PostgreSQL REST</div>
                <div class="text-sm font-mono font-bold mt-1">odtxjphklgvnpzicsysc</div>
            </div>
            <span class="px-2.5 py-1 rounded-full text-[11px] font-mono font-bold {{ $health['supabase_online'] ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300' }}">
                {{ $health['supabase_online'] ? 'CONNECTED' : 'UNREACHABLE' }}
            </span>
        </div>
        <div class="km-card p-5 flex items-center justify-between">
            <div>
                <div class="text-xs text-slate-500 dark:text-slate-400">n8n Workflow Automation</div>
                <div class="text-sm font-mono font-bold mt-1">{{ config('keramine.n8n_url') }}</div>
            </div>
            <a href="{{ config('keramine.n8n_url') }}" target="_blank" rel="noopener" class="px-3 py-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-cyan-300 text-xs font-semibold inline-flex items-center gap-1">
                Buka n8n <x-icon name="external" class="w-3.5 h-3.5" />
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Form Simulasi Transaksi Real-Time -->
        <form action="{{ route('pipeline.simulate') }}" method="POST" class="lg:col-span-6 km-card p-6 space-y-4">
            @csrf
            <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <x-icon name="play" class="w-4 h-4 text-emerald-600" />
                Simulator Transaksi B2B Baru + Auto Re-Mine
            </h2>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold mb-1">Jalur Eksekusi</label>
                    <select name="channel" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-[#2A2F3E] bg-white dark:bg-[#0F1117] text-xs">
                        <option value="fastapi">Langsung via FastAPI (:8001)</option>
                        <option value="n8n">Lewat n8n Webhook (:5678/webhook/keramine-ingest)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">Sales POF</label>
                    <input type="text" name="target_pof" value="DIAN" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-[#2A2F3E] bg-white dark:bg-[#0F1117] text-xs font-mono">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">Toko Mitra Target</label>
                <input type="text" name="target_toko" value="ABAD BANGUNAN, TOKO" list="pipeline_stores" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-[#2A2F3E] bg-white dark:bg-[#0F1117] text-xs">
                <datalist id="pipeline_stores">
                    @foreach($stores as $st)
                        <option value="{{ $st['nama_toko'] }}">
                    @endforeach
                </datalist>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">Pasangan Seri Bundling (min. 2 dipisah koma)</label>
                <input type="text" name="bundle_seris" value="DIEGO, GRANIT" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-[#2A2F3E] bg-white dark:bg-[#0F1117] text-xs font-mono">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold mb-1">Qty per Item (DUS)</label>
                    <input type="number" name="qty_per_item" value="75" min="1" max="5000" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-[#2A2F3E] bg-white dark:bg-[#0F1117] text-xs font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">Repeat Keranjang (Cabang/Periode)</label>
                    <input type="number" name="repeat_baskets" value="2" min="1" max="10" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-[#2A2F3E] bg-white dark:bg-[#0F1117] text-xs font-mono">
                </div>
            </div>

            <button type="submit" class="w-full py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition">
                Kirim Transaksi Simulasi & Trigger Re-Mine SOTA
            </button>
        </form>

        <!-- Form Re-Mine Manual + SOTA Benchmark -->
        <div class="lg:col-span-6 space-y-5">
            <form action="{{ route('pipeline.mine') }}" method="POST" class="km-card p-6 space-y-4">
                @csrf
                <input type="hidden" name="persist_to_supabase" value="1">
                <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <x-icon name="network" class="w-4 h-4 text-indigo-600 dark:text-cyan-400" />
                    Re-Mining SOTA On-Demand (FastAPI /api/mine)
                </h2>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold mb-1">Granularitas</label>
                        <select name="basket_type" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-[#2A2F3E] bg-white dark:bg-[#0F1117] text-xs">
                            <option value="store_month">store_month</option>
                            <option value="store_week">store_week</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">Level Item</label>
                        <select name="item_level" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-[#2A2F3E] bg-white dark:bg-[#0F1117] text-xs">
                            <option value="SERI">SERI</option>
                            <option value="MERK_UKURAN">MERK_UKURAN</option>
                            <option value="NAMABARA">NAMABARA</option>
                            <option value="MERK">MERK</option>
                            <option value="UKURAN">UKURAN</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">Algoritma</label>
                        <select name="algorithm" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-[#2A2F3E] bg-white dark:bg-[#0F1117] text-xs">
                            <option value="apriori">apriori</option>
                            <option value="fpgrowth">fpgrowth</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="w-full py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold transition">
                    Jalankan Mining & Sinkronkan ke Supabase
                </button>
            </form>

            <div class="km-card p-5">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-3">Benchmark Algoritma Colab SOTA (Apriori vs FP-Growth vs FP-Max)</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead class="font-mono text-[10px] text-slate-500 uppercase border-b border-slate-200 dark:border-[#2A2F3E]">
                            <tr>
                                <th class="py-2">Level</th>
                                <th class="py-2 text-right">Apriori (ms)</th>
                                <th class="py-2 text-right">FP-Growth (ms)</th>
                                <th class="py-2 text-right">FP-Max Pruning</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-[#242938] font-mono-num">
                            @foreach($benchmark as $row)
                                <tr>
                                    <td class="py-2 font-bold">{{ $row['item_level'] }}</td>
                                    <td class="py-2 text-right">{{ number_format($row['apriori_ms'], 1) }}</td>
                                    <td class="py-2 text-right text-indigo-600 dark:text-cyan-300">{{ number_format($row['fpgrowth_ms'], 1) }}</td>
                                    <td class="py-2 text-right text-emerald-600 dark:text-emerald-400">{{ number_format($row['fpmax_pruning_pct'], 1) }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Riwayat Eksekusi Mining -->
    <div class="km-card overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-[#2A2F3E] flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white">Log Eksekusi Mining Runs (keramine_mining_runs)</h2>
            <span class="text-[10px] font-mono uppercase px-2 py-0.5 rounded bg-slate-100 dark:bg-[#131620]">{{ strtoupper($runsSource) }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-[#131620] text-slate-500 font-mono text-[10px] uppercase">
                    <tr>
                        <th class="px-4 py-3">Triggered By</th>
                        <th class="px-4 py-3">Granularity / Level</th>
                        <th class="px-4 py-3 text-right">Baskets</th>
                        <th class="px-4 py-3 text-right">Rules</th>
                        <th class="px-4 py-3 text-right">Exec (ms)</th>
                        <th class="px-4 py-3">Notes / Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-[#242938]">
                    @forelse($runs as $run)
                        <tr>
                            <td class="px-4 py-2.5 font-mono font-semibold">{{ $run['triggered_by'] ?? 'colab_02_sota' }}</td>
                            <td class="px-4 py-2.5">{{ $run['basket_type'] ?? '-' }} / {{ $run['item_level'] ?? '-' }}</td>
                            <td class="px-4 py-2.5 text-right font-mono-num">{{ number_format($run['total_baskets'] ?? 0) }}</td>
                            <td class="px-4 py-2.5 text-right font-mono-num font-bold text-indigo-600 dark:text-cyan-300">{{ $run['rules_found'] ?? 0 }}</td>
                            <td class="px-4 py-2.5 text-right font-mono-num">{{ number_format((float) ($run['exec_time_ms'] ?? 0), 1) }}</td>
                            <td class="px-4 py-2.5 text-[11px] text-slate-500">{{ $run['notes'] ?? ($run['created_at'] ?? 'Colab verified baseline') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">{{ __('keramine.common.no_data') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
