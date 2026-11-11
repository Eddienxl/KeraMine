<?php

namespace App\Http\Controllers;

use App\Models\RecommendationHistory;
use App\Services\KeraMineDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortalController extends Controller
{
    public function __construct(
        private readonly KeraMineDataService $dataService,
    ) {}

    public function home(): View
    {
        $health = $this->dataService->getHealthStatus();
        $cleaning = $this->dataService->getCleaningSummary();
        $topRulesResp = $this->dataService->getRules([
            'basket_type' => 'store_month',
            'item_level' => 'SERI',
            'fdr_only' => true,
            'limit' => 6,
            'sort' => 'utility',
        ]);
        $storesResp = $this->dataService->getStores();
        $runsResp = $this->dataService->getMiningRuns(5);
        $recentPlans = RecommendationHistory::query()->latest()->limit(4)->get();

        $pofStats = collect($storesResp['stores'])
            ->groupBy('dominant_pof')
            ->map(fn ($group, $pof) => [
                'pof' => $pof ?: 'LAINNYA',
                'store_count' => $group->count(),
                'total_dus' => round($group->sum('total_qty_dus')),
                'total_tx' => (int) $group->sum('total_transactions'),
            ])
            ->sortByDesc('total_dus')
            ->values()
            ->all();

        return view('keramine.home', [
            'health' => $health,
            'cleaning' => $cleaning,
            'topRules' => $topRulesResp['rules'],
            'rulesSource' => $topRulesResp['source'],
            'storesCount' => $storesResp['total_all'],
            'pofStats' => $pofStats,
            'recentRuns' => $runsResp['runs'],
            'recentPlans' => $recentPlans,
        ]);
    }

    public function stores(Request $request): View
    {
        $tab = $request->input('tab', 'stores');
        $defaultPof = '';
        if (Auth::check() && Auth::user()->role === 'sales' && ! $request->has('pof')) {
            $defaultPof = (string) Auth::user()->pof_name;
        }

        $pofFilter = strtoupper(trim((string) $request->input('pof', $defaultPof)));
        if ($pofFilter === 'ALL') {
            $pofFilter = '';
        }

        $storesResp = $this->dataService->getStores([
            'q' => $request->input('q'),
            'pof' => $pofFilter,
            'sort' => $request->input('sort', 'qty_desc'),
        ]);

        $level = (string) $request->input('level', 'SERI');
        $catalogResp = $this->dataService->getCatalogSummary($level, $request->input('q'), 90);

        $selectedStore = null;
        if ($request->filled('store')) {
            $selectedStore = $this->dataService->getStoreDetail((string) $request->input('store'));
        }

        return view('keramine.stores', [
            'tab' => $tab,
            'stores' => array_slice($storesResp['stores'], 0, 120),
            'totalFilteredStores' => count($storesResp['stores']),
            'totalAllStores' => $storesResp['total_all'],
            'allPofs' => $storesResp['all_pofs'],
            'pofFilter' => $pofFilter,
            'storesSource' => $storesResp['source'],
            'catalogItems' => $catalogResp['items'],
            'catalogUnique' => $catalogResp['total_unique'],
            'catalogLevel' => $level,
            'selectedStore' => $selectedStore,
        ]);
    }

    public function recommender(Request $request): View|RedirectResponse
    {
        $storesResp = $this->dataService->getStores();
        $stores = $storesResp['stores'];

        // Prioritize user's POF stores first if logged in as Sales POF
        $userPof = Auth::check() ? (string) Auth::user()->pof_name : 'ALL';
        $basketType = (string) $request->input('basket_type', 'store_month');
        $itemLevel = (string) $request->input('item_level', 'SERI');
        $namaToko = $request->filled('nama_toko') ? (string) $request->input('nama_toko') : null;
        $topK = max(1, min(15, (int) $request->input('top_k', 8)));

        $rawTrigger = trim((string) $request->input('trigger_items', $request->isMethod('get') && ! $request->has('run') ? 'DIEGO' : ''));
        $triggers = array_values(array_filter(array_map('trim', explode(',', $rawTrigger))));

        $catalogSeris = $this->dataService->getCatalogSummary($itemLevel, null, 60)['items'];

        $result = $this->dataService->getRecommendations($triggers, $namaToko, $basketType, $itemLevel, $topK);

        if ($request->isMethod('post') && $request->boolean('save_plan')) {
            $planTitle = trim((string) $request->input('plan_title'));
            if ($planTitle === '') {
                $prefix = $namaToko ?: 'Bundling Global';
                $trigLabel = ! empty($result['triggers']) ? implode(' + ', $result['triggers']) : 'Auto-Profile';
                $planTitle = "{$prefix} — Paket {$trigLabel} ({$itemLevel})";
            }

            $history = RecommendationHistory::create([
                'user_id' => Auth::id(),
                'plan_title' => $planTitle,
                'nama_toko' => $namaToko,
                'pof_name' => $result['store_profile']['dominant_pof'] ?? ($userPof !== 'ALL' ? $userPof : 'ADMIN'),
                'basket_type' => $basketType,
                'item_level' => $itemLevel,
                'trigger_items' => $result['triggers'],
                'recommendations_json' => $result['recommendations'],
                'source_mode' => $result['source_mode'],
                'notes' => trim((string) $request->input('notes', '')),
            ]);

            return redirect()->route('history')->with('status', "Rencana bundling #{$history->id} ({$history->plan_title}) berhasil disimpan ke SQLite.");
        }

        return view('keramine.recommender', [
            'stores' => $stores,
            'userPof' => $userPof,
            'selectedToko' => $namaToko,
            'basketType' => $basketType,
            'itemLevel' => $itemLevel,
            'topK' => $topK,
            'triggerInput' => implode(', ', $triggers),
            'catalogItems' => $catalogSeris,
            'result' => $result,
        ]);
    }

    public function rules(Request $request): View
    {
        $basketType = (string) $request->input('basket_type', 'store_month');
        $itemLevel = (string) $request->input('item_level', 'SERI');
        $fdrOnly = $request->boolean('fdr_only', false);
        $minLift = (float) $request->input('min_lift', 0);
        $minZhang = (float) $request->input('min_zhang', -1);
        $sort = (string) $request->input('sort', 'utility');
        $q = trim((string) $request->input('q', ''));

        $rulesResp = $this->dataService->getRules([
            'basket_type' => $basketType,
            'item_level' => $itemLevel,
            'fdr_only' => $fdrOnly,
            'min_lift' => $minLift,
            'min_zhang' => $minZhang,
            'sort' => $sort,
            'q' => $q,
            'limit' => 200,
        ]);

        return view('keramine.rules', [
            'rules' => $rulesResp['rules'],
            'totalMatched' => $rulesResp['total_matched'],
            'significantCount' => $rulesResp['significant_count'],
            'rulesSource' => $rulesResp['source'],
            'basketType' => $basketType,
            'itemLevel' => $itemLevel,
            'fdrOnly' => $fdrOnly,
            'minLift' => $minLift,
            'minZhang' => $minZhang,
            'sort' => $sort,
            'q' => $q,
        ]);
    }

    public function history(): View
    {
        $histories = RecommendationHistory::query()->with('user')->latest()->paginate(12);

        return view('keramine.history', compact('histories'));
    }

    public function destroyHistory(RecommendationHistory $history): RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('login')->with('status', 'Silakan masuk terlebih dahulu untuk menghapus riwayat.');
        }

        if (! Auth::user()->isAdmin() && $history->user_id !== Auth::id()) {
            return redirect()->route('history')->with('error', 'Anda hanya dapat menghapus rencana milik sendiri atau sebagai Admin.');
        }

        $title = $history->plan_title;
        $history->delete();

        return redirect()->route('history')->with('status', "Rencana \"{$title}\" berhasil dihapus.");
    }

    public function exportHistoryCsv(RecommendationHistory $history): StreamedResponse
    {
        $filename = 'keramine-plan-' . $history->id . '.csv';

        return response()->streamDownload(function () use ($history) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            // UTF-8 BOM for clean Microsoft Excel rendering
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Plan Title', $history->plan_title]);
            fputcsv($out, ['Nama Toko', $history->nama_toko ?? 'GLOBAL']);
            fputcsv($out, ['Sales POF', $history->pof_name ?? '-']);
            fputcsv($out, ['Granularity / Level', "{$history->basket_type} / {$history->item_level}"]);
            fputcsv($out, ['Trigger Items', implode(', ', $history->trigger_items ?? [])]);
            fputcsv($out, ['Created At', $history->created_at?->toDateTimeString() ?? '']);
            fputcsv($out, []);
            fputcsv($out, ['Rank', 'Recommended Item', 'Source Type', 'Antecedent', 'Confidence', 'Lift', 'Zhang Metric', 'Avg Qty (DUS)', 'Utility Score', 'Fisher p-value']);

            foreach (($history->recommendations_json ?? []) as $idx => $rec) {
                fputcsv($out, [
                    $idx + 1,
                    $rec['recommended_item'] ?? '',
                    $rec['source_type'] ?? '',
                    $rec['antecedents_str'] ?? '',
                    $rec['confidence'] ?? '',
                    $rec['lift'] ?? '',
                    $rec['zhang_metric'] ?? '',
                    $rec['avg_qty_dus'] ?? '',
                    $rec['utility_score'] ?? '',
                    $rec['p_value_fisher'] ?? '',
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function pipeline(): View
    {
        $health = $this->dataService->getHealthStatus();
        $runsResp = $this->dataService->getMiningRuns(15);
        $storesResp = $this->dataService->getStores();
        $benchmark = $this->dataService->getBenchmarkSummary();

        return view('keramine.pipeline', [
            'health' => $health,
            'runs' => $runsResp['runs'],
            'runsSource' => $runsResp['source'],
            'stores' => array_slice($storesResp['stores'], 0, 50),
            'benchmark' => $benchmark['algorithm_benchmark_store_month'] ?? [],
        ]);
    }

    public function triggerMine(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'basket_type' => ['required', 'in:store_month,store_week'],
            'item_level' => ['required', 'in:MERK,UKURAN,MERK_UKURAN,SERI,NAMABARA'],
            'algorithm' => ['required', 'in:apriori,fpgrowth'],
            'persist_to_supabase' => ['nullable', 'boolean'],
        ]);

        $res = $this->dataService->triggerMining([
            'basket_type' => $validated['basket_type'],
            'item_level' => $validated['item_level'],
            'algorithm' => $validated['algorithm'],
            'triggered_by' => Auth::check() ? 'laravel_' . Auth::user()->role : 'laravel_portal',
            'persist_to_supabase' => (bool) ($validated['persist_to_supabase'] ?? true),
            'data_source' => 'auto',
        ]);

        if (! $res['ok']) {
            return redirect()->route('pipeline')->with('error', 'Gagal memicu FastAPI /api/mine: ' . ($res['error'] ?? 'FastAPI offline di :8001'));
        }

        $meta = $res['data']['meta'] ?? [];
        $rulesFound = $meta['rules_found'] ?? 0;
        $sigRules = $meta['significant_rules'] ?? 0;
        $ms = $meta['exec_time_ms'] ?? 0;

        return redirect()->route('pipeline')->with(
            'status',
            "Re-mining {$validated['basket_type']} / {$validated['item_level']} ({$validated['algorithm']}) selesai: {$rulesFound} aturan ({$sigRules} signifikan) dalam {$ms} ms."
        );
    }

    public function simulatePipeline(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'channel' => ['required', 'in:fastapi,n8n'],
            'target_toko' => ['required', 'string', 'max:160'],
            'target_pof' => ['required', 'string', 'max:32'],
            'bundle_seris' => ['required', 'string', 'max:200'],
            'qty_per_item' => ['required', 'integer', 'min:1', 'max:5000'],
            'repeat_baskets' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $seris = array_values(array_filter(array_map('trim', explode(',', $validated['bundle_seris']))));
        if (count($seris) < 2) {
            return redirect()->route('pipeline')->with('error', 'Masukkan minimal 2 seri bundling dipisahkan koma (misal: DIEGO, GRANIT).');
        }

        $res = $this->dataService->simulateTransactions([
            'target_toko' => strtoupper($validated['target_toko']),
            'target_pof' => strtoupper($validated['target_pof']),
            'bundle_seris' => $seris,
            'qty_per_item' => (int) $validated['qty_per_item'],
            'repeat_baskets' => (int) $validated['repeat_baskets'],
            'auto_remine' => true,
        ], $validated['channel']);

        if (! $res['ok']) {
            return redirect()->route('pipeline')->with(
                'error',
                "Simulasi via {$validated['channel']} gagal (HTTP {$res['status']}): " . ($res['error'] ?? 'Service offline')
            );
        }

        $inserted = $res['data']['inserted_transactions'] ?? ($res['data']['inserted'] ?? count($seris));
        $batchTag = $res['data']['batch_tag'] ?? 'n8n_sim';

        return redirect()->route('pipeline')->with(
            'status',
            "Simulasi real-time ({$validated['channel']}) berhasil! Batch {$batchTag}: +{$inserted} baris transaksi & auto re-mine selesai."
        );
    }

    public function resetDemo(): RedirectResponse
    {
        $res = $this->dataService->resetDemoData();
        if (! $res['ok']) {
            return redirect()->route('pipeline')->with('error', 'Gagal mereset demo Supabase: ' . ($res['error'] ?? 'FastAPI offline di :8001'));
        }

        $restored = $res['data']['restored_rules'] ?? 784;

        return redirect()->route('pipeline')->with(
            'status',
            "Demo berhasil direset ke baseline dataset asli: transaksi simulasi dihapus & {$restored} aturan SOTA dipulihkan."
        );
    }
}

