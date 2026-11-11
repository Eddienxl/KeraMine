@extends('layouts.app')
@section('title', __('keramine.nav.history'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white">Riwayat Rencana Restock & Bundling</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Disimpan secara persisten di SQLite operasional lokal Laravel (Opsi 1) dengan fitur ekspor CSV & cetak laporan.
            </p>
        </div>
        <div class="flex items-center gap-2 no-print">
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 dark:border-[#2A2F3E] bg-white dark:bg-[#1A1D27] text-xs font-semibold">
                <x-icon name="printer" class="w-4 h-4" />
                {{ __('keramine.common.print') }}
            </button>
            <a href="{{ route('recommender') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold">
                <x-icon name="sparkles" class="w-4 h-4" />
                Buat Rencana Baru
            </a>
        </div>
    </div>

    <div class="space-y-4">
        @forelse($histories as $plan)
            <div class="km-card p-6">
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                    <div>
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span class="px-2 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950/70 text-indigo-700 dark:text-cyan-300 font-mono text-[10px] font-bold">
                                #{{ $plan->id }} · {{ $plan->basket_type }} / {{ $plan->item_level }}
                            </span>
                            <span class="text-[11px] font-mono text-slate-400">
                                POF {{ $plan->pof_name ?? '-' }} · {{ $plan->created_at?->format('d M Y H:i') }}
                            </span>
                        </div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ $plan->plan_title }}</h2>
                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Toko: <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $plan->nama_toko ?? 'GLOBAL' }}</span>
                            · Pemicu: <span class="font-mono">{{ implode(', ', $plan->trigger_items ?? []) }}</span>
                        </div>
                        @if($plan->notes)
                            <p class="text-xs text-slate-600 dark:text-slate-300 mt-2 italic">"{{ $plan->notes }}"</p>
                        @endif
                    </div>

                    <div class="flex items-center gap-2 no-print shrink-0">
                        <a href="{{ route('history.export', $plan) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-[#2A2F3E] text-xs font-semibold hover:border-indigo-400">
                            <x-icon name="download" class="w-3.5 h-3.5" />
                            CSV
                        </a>
                        <form action="{{ route('history.destroy', $plan) }}" method="POST" onsubmit="return confirm('Hapus rencana ini?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/50">
                                <x-icon name="trash" class="w-3.5 h-3.5" />
                            </button>
                        </form>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5">
                    @foreach(($plan->recommendations_json ?? []) as $rec)
                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-[#131620] border border-slate-200/80 dark:border-[#222634] text-xs">
                            <div class="font-bold text-slate-900 dark:text-white">{{ $rec['recommended_item'] ?? '-' }}</div>
                            <div class="text-[11px] font-mono-num text-slate-500 dark:text-slate-400 mt-1">
                                Lift {{ isset($rec['lift']) && $rec['lift'] !== null ? number_format((float)$rec['lift'], 2) . 'x' : 'Fallback' }}
                                · Conf {{ isset($rec['confidence']) && $rec['confidence'] !== null ? number_format((float)$rec['confidence'] * 100, 1) . '%' : '-' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="km-card p-10 text-center text-xs text-slate-500">{{ __('keramine.common.no_data') }}</div>
        @endforelse
    </div>

    <div>{{ $histories->links() }}</div>
</div>
@endsection

