<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class KeraMineDataService
{
    public function getHealthStatus(): array
    {
        $fastapiUrl = config('keramine.fastapi_url');
        $cleanDir = config('keramine.clean_data_dir');
        $localReady = is_file($cleanDir . DIRECTORY_SEPARATOR . 'mined_rules_catalog.csv')
            && is_file($cleanDir . DIRECTORY_SEPARATOR . 'store_profiles.json');

        try {
            $resp = Http::timeout(3)->get("{$fastapiUrl}/api/health");
            if ($resp->successful()) {
                $json = $resp->json() ?? [];

                return [
                    'fastapi_online' => true,
                    'supabase_configured' => (bool) ($json['supabase_configured'] ?? false),
                    'supabase_online' => (bool) ($json['supabase_reachable'] ?? false),
                    'local_clean_ready' => (bool) ($json['local_clean_csv'] ?? $localReady),
                    'latest_run' => $json['latest_run'] ?? null,
                    'fastapi_url' => $fastapiUrl,
                ];
            }
        } catch (\Throwable) {
            // Fall through to degraded health status without throwing in UI views
        }

        // Probe Supabase REST directly if FastAPI is offline
        $supaOnline = false;
        $latestRun = null;
        $supaData = $this->querySupabase('keramine_mining_runs', [
            'select' => 'id,triggered_by,item_level,rules_found,created_at',
            'order' => 'created_at.desc',
            'limit' => 1,
        ]);
        if ($supaData !== null) {
            $supaOnline = true;
            $latestRun = $supaData[0] ?? null;
        }

        return [
            'fastapi_online' => false,
            'supabase_configured' => config('keramine.supabase_rest_url') !== '',
            'supabase_online' => $supaOnline,
            'local_clean_ready' => $localReady,
            'latest_run' => $latestRun,
            'fastapi_url' => $fastapiUrl,
        ];
    }

    public function getCleaningSummary(): array
    {
        return $this->readJsonArtifact('cleaning_audit_summary.json') ?? [];
    }

    public function getBenchmarkSummary(): array
    {
        return $this->readJsonArtifact('sota_benchmark_summary.json') ?? [];
    }

    public function getMiningRuns(int $limit = 20): array
    {
        $remote = $this->querySupabase('keramine_mining_runs', [
            'select' => '*',
            'order' => 'created_at.desc',
            'limit' => $limit,
        ]);
        if ($remote !== null) {
            return ['runs' => $remote, 'source' => 'supabase_rest'];
        }

        $bench = $this->getBenchmarkSummary();

        return [
            'runs' => $bench['mining_runs_summary'] ?? [],
            'source' => 'local_fallback',
        ];
    }

    public function getStores(array $filters = []): array
    {
        $cached = Cache::remember('keramine_stores_v2', 60, function () {
            $remote = $this->querySupabase('keramine_store_profiles', [
                'select' => '*',
                'order' => 'total_qty_dus.desc',
                'limit' => 500,
            ]);
            if ($remote !== null && count($remote) > 0) {
                return ['stores' => $remote, 'source' => 'supabase_rest'];
            }

            return [
                'stores' => $this->readJsonArtifact('store_profiles.json') ?? [],
                'source' => 'local_fallback',
            ];
        });
        $stores = $cached['stores'];
        $source = $cached['source'];

        $allPofs = collect($stores)
            ->pluck('dominant_pof')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $filtered = collect($stores);

        if (! empty($filters['q'])) {
            $q = mb_strtolower(trim((string) $filters['q']));
            $filtered = $filtered->filter(function (array $s) use ($q) {
                $hay = mb_strtolower(
                    ($s['nama_toko'] ?? '') . ' ' . ($s['kode_toko'] ?? '') . ' ' . ($s['dominant_pof'] ?? '')
                );

                return str_contains($hay, $q);
            });
        }

        if (! empty($filters['pof'])) {
            $pof = strtoupper(trim((string) $filters['pof']));
            $filtered = $filtered->filter(fn (array $s) => strtoupper((string) ($s['dominant_pof'] ?? '')) === $pof);
        }

        $sort = $filters['sort'] ?? 'qty_desc';
        $filtered = match ($sort) {
            'tx_desc' => $filtered->sortByDesc('total_transactions'),
            'baskets_desc' => $filtered->sortByDesc('total_baskets_month'),
            'name_asc' => $filtered->sortBy('nama_toko'),
            default => $filtered->sortByDesc('total_qty_dus'),
        };

        return [
            'stores' => $filtered->values()->all(),
            'total_all' => count($stores),
            'all_pofs' => $allPofs,
            'source' => $source,
        ];
    }

    public function getStoreDetail(string $namaToko): ?array
    {
        $target = mb_strtoupper(trim($namaToko));
        $data = $this->getStores();
        foreach ($data['stores'] as $store) {
            if (mb_strtoupper((string) ($store['nama_toko'] ?? '')) === $target) {
                return $store;
            }
        }

        return null;
    }

    public function getCatalogSummary(string $level = 'SERI', ?string $search = null, int $limit = 120): array
    {
        $allowedLevels = ['SERI', 'NAMABARA', 'MERK', 'UKURAN', 'MERK_UKURAN'];
        if (! in_array($level, $allowedLevels, true)) {
            $level = 'SERI';
        }

        $allGrouped = Cache::remember("keramine_catalog_{$level}_v1", 600, function () use ($level) {
            $csvPath = config('keramine.clean_data_dir') . DIRECTORY_SEPARATOR . 'transactions_clean.csv';
            if (! is_file($csvPath)) {
                return [];
            }

            $col = match ($level) {
                'MERK' => 'merk_clean',
                'UKURAN' => 'ukuran',
                'MERK_UKURAN' => 'merk_ukuran',
                'NAMABARA' => 'namabara',
                default => 'seri',
            };

            $agg = [];
            $fh = fopen($csvPath, 'r');
            if ($fh === false) {
                return [];
            }

            $headers = fgetcsv($fh);
            if (! is_array($headers)) {
                fclose($fh);

                return [];
            }
            $idx = array_flip($headers);

            while (($row = fgetcsv($fh)) !== false) {
                $key = strtoupper(trim((string) ($row[$idx[$col] ?? -1] ?? '')));
                if ($key === '') {
                    continue;
                }
                if (! isset($agg[$key])) {
                    $agg[$key] = [
                        'name' => $key,
                        'total_qty_dus' => 0.0,
                        'tx_count' => 0,
                        'stores' => [],
                        'sample_merk' => (string) ($row[$idx['merk_clean'] ?? -1] ?? '-'),
                        'sample_ukuran' => (string) ($row[$idx['ukuran'] ?? -1] ?? '-'),
                    ];
                }
                $agg[$key]['total_qty_dus'] += (float) ($row[$idx['qty'] ?? -1] ?? 0);
                $agg[$key]['tx_count'] += 1;
                $toko = (string) ($row[$idx['nama_toko'] ?? -1] ?? '');
                if ($toko !== '') {
                    $agg[$key]['stores'][$toko] = true;
                }
            }
            fclose($fh);

            $items = [];
            foreach ($agg as $entry) {
                $items[] = [
                    'name' => $entry['name'],
                    'total_qty_dus' => round($entry['total_qty_dus'], 2),
                    'tx_count' => $entry['tx_count'],
                    'store_count' => count($entry['stores']),
                    'sample_merk' => $entry['sample_merk'],
                    'sample_ukuran' => $entry['sample_ukuran'],
                ];
            }

            usort($items, fn (array $a, array $b) => $b['total_qty_dus'] <=> $a['total_qty_dus']);

            return $items;
        });

        $col = collect($allGrouped);
        if ($search !== null && trim($search) !== '') {
            $q = mb_strtolower(trim($search));
            $col = $col->filter(function (array $item) use ($q) {
                return str_contains(mb_strtolower($item['name'] . ' ' . $item['sample_merk'] . ' ' . $item['sample_ukuran']), $q);
            });
        }

        return [
            'items' => $col->take($limit)->values()->all(),
            'total_unique' => count($allGrouped),
            'total_matched' => $col->count(),
        ];
    }

    public function getRules(array $filters = []): array
    {
        $basketType = (string) ($filters['basket_type'] ?? 'store_month');
        $itemLevel = (string) ($filters['item_level'] ?? 'SERI');
        $fdrOnly = (bool) ($filters['fdr_only'] ?? false);
        $minLift = (float) ($filters['min_lift'] ?? 0);
        $minZhang = (float) ($filters['min_zhang'] ?? -1);
        $limit = (int) ($filters['limit'] ?? 200);
        $sort = (string) ($filters['sort'] ?? 'utility');

        $source = 'local_fallback';
        $supaFilters = [
            'select' => '*',
            'order' => 'utility_score.desc,lift.desc',
            'limit' => 800,
        ];
        if ($basketType !== '' && $basketType !== 'ALL') {
            $supaFilters['basket_type'] = "eq.{$basketType}";
        }
        if ($itemLevel !== '' && $itemLevel !== 'ALL') {
            $supaFilters['item_level'] = "eq.{$itemLevel}";
        }

        $rawRules = $this->querySupabase('keramine_rules', $supaFilters);
        if ($rawRules !== null) {
            $source = 'supabase_rest';
        } else {
            $rawRules = $this->readCsvRules();
        }

        $normalized = [];
        foreach ($rawRules as $r) {
            $bt = (string) ($r['basket_type'] ?? '');
            $il = (string) ($r['item_level'] ?? '');
            if ($basketType !== '' && $basketType !== 'ALL' && $bt !== $basketType) {
                continue;
            }
            if ($itemLevel !== '' && $itemLevel !== 'ALL' && $il !== $itemLevel) {
                continue;
            }

            $qVal = (float) ($r['q_value_bh'] ?? 1.0);
            $pVal = (float) ($r['p_value_fisher'] ?? 1.0);
            $isSig = $this->toBool($r['is_significant'] ?? false);
            $isFdr = isset($r['is_fdr_significant']) ? $this->toBool($r['is_fdr_significant']) : ($qVal < 0.05);

            $lift = (float) ($r['lift'] ?? 0);
            $zhang = (float) ($r['zhang_metric'] ?? 0);

            if ($fdrOnly && ! ($isSig || $isFdr)) {
                continue;
            }
            if ($lift < $minLift) {
                continue;
            }
            if ($zhang < $minZhang) {
                continue;
            }

            if (! empty($filters['q'])) {
                $q = mb_strtolower(trim((string) $filters['q']));
                $hay = mb_strtolower(($r['antecedents_str'] ?? '') . ' ' . ($r['consequents_str'] ?? '') . ' ' . ($r['id'] ?? ''));
                if (! str_contains($hay, $q)) {
                    continue;
                }
            }

            $normalized[] = [
                'id' => (string) ($r['id'] ?? ''),
                'basket_type' => $bt,
                'item_level' => $il,
                'antecedents' => $this->parseListField($r['antecedents'] ?? []),
                'consequents' => $this->parseListField($r['consequents'] ?? []),
                'antecedents_str' => (string) ($r['antecedents_str'] ?? ''),
                'consequents_str' => (string) ($r['consequents_str'] ?? ''),
                'n_a' => (int) ($r['n_a'] ?? 0),
                'n_b' => (int) ($r['n_b'] ?? 0),
                'n_ab' => (int) ($r['n_ab'] ?? 0),
                'total_baskets' => (int) ($r['total_baskets'] ?? 0),
                'support' => (float) ($r['support'] ?? 0),
                'confidence' => (float) ($r['confidence'] ?? 0),
                'lift' => $lift,
                'leverage' => (float) ($r['leverage'] ?? 0),
                'conviction' => (float) ($r['conviction'] ?? 0),
                'kulczynski' => (float) ($r['kulczynski'] ?? 0),
                'zhang_metric' => $zhang,
                'total_qty_dus' => (float) ($r['total_qty_dus'] ?? 0),
                'avg_qty_dus' => (float) ($r['avg_qty_dus'] ?? 0),
                'utility_score' => (float) ($r['utility_score'] ?? 0),
                'p_value_fisher' => $pVal,
                'q_value_bh' => $qVal,
                'is_significant' => $isSig,
                'is_fdr_significant' => $isFdr,
            ];
        }

        usort($normalized, function (array $a, array $b) use ($sort) {
            return match ($sort) {
                'lift' => $b['lift'] <=> $a['lift'],
                'confidence' => $b['confidence'] <=> $a['confidence'],
                'zhang' => $b['zhang_metric'] <=> $a['zhang_metric'],
                'support' => $b['support'] <=> $a['support'],
                default => $b['utility_score'] <=> $a['utility_score'],
            };
        });

        $sigCount = count(array_filter($normalized, fn (array $r) => $r['is_significant'] || $r['is_fdr_significant']));

        return [
            'rules' => array_slice($normalized, 0, $limit),
            'total_matched' => count($normalized),
            'significant_count' => $sigCount,
            'source' => $source,
        ];
    }

    public function getRecommendations(
        array $triggers,
        ?string $namaToko,
        string $basketType = 'store_month',
        string $itemLevel = 'SERI',
        int $topK = 8
    ): array {
        $triggers = array_values(array_unique(array_filter(array_map(
            fn ($x) => strtoupper(trim((string) $x)),
            $triggers
        ))));

        $storeProfile = $namaToko ? $this->getStoreDetail($namaToko) : null;

        // Try live FastAPI /api/recommend first (single trigger or store-profile auto-trigger)
        if (count($triggers) <= 1) {
            try {
                $params = [
                    'basket_type' => $basketType,
                    'item_level' => $itemLevel,
                    'top_k' => $topK,
                ];
                if (count($triggers) === 1) {
                    $params['item'] = $triggers[0];
                }
                if ($namaToko !== null && trim($namaToko) !== '') {
                    $params['nama_toko'] = $namaToko;
                }

                $resp = Http::timeout(6)->get(config('keramine.fastapi_url') . '/api/recommend', $params);
                if ($resp->successful()) {
                    $data = $resp->json() ?? [];

                    return [
                        'triggers' => $data['triggers'] ?? $triggers,
                        'store_profile' => $data['store_profile'] ?? $storeProfile,
                        'recommendations' => $data['recommendations'] ?? [],
                        'source_mode' => 'fastapi',
                    ];
                }
            } catch (\Throwable) {
                // Transparently degrade to local/Supabase rule catalog matcher below
            }
        }

        // Local or multi-trigger rule resolution
        if (empty($triggers) && $storeProfile !== null) {
            $field = match ($itemLevel) {
                'MERK' => 'top_merks',
                'UKURAN' => 'top_ukurans',
                default => 'top_seris',
            };
            $triggers = collect($storeProfile[$field] ?? [])->pluck('name')->take(3)->values()->all();
        }

        $rulesResp = $this->getRules([
            'basket_type' => $basketType,
            'item_level' => $itemLevel,
            'limit' => 800,
            'sort' => 'utility',
        ]);

        $recs = [];
        $seen = array_flip($triggers);
        foreach ($rulesResp['rules'] as $r) {
            $ants = $r['antecedents'];
            if (! empty($triggers) && empty(array_intersect($triggers, $ants))) {
                continue;
            }
            foreach ($r['consequents'] as $cons) {
                if (isset($seen[$cons])) {
                    continue;
                }
                $seen[$cons] = true;
                $recs[] = [
                    'recommended_item' => $cons,
                    'source_type' => ($r['is_significant'] || $r['is_fdr_significant'])
                        ? 'Significant Association Rule (Fisher & FDR)'
                        : 'Exploratory Association Rule',
                    'rule_id' => $r['id'],
                    'antecedents_str' => $r['antecedents_str'],
                    'confidence' => $r['confidence'],
                    'lift' => $r['lift'],
                    'zhang_metric' => $r['zhang_metric'],
                    'avg_qty_dus' => $r['avg_qty_dus'],
                    'utility_score' => $r['utility_score'],
                    'p_value_fisher' => $r['p_value_fisher'],
                ];
                if (count($recs) >= $topK) {
                    break 2;
                }
            }
        }

        // Transparent Volume Popularity Fallback if still under $topK
        if (count($recs) < $topK) {
            $catalog = $this->getCatalogSummary($itemLevel, null, 30)['items'];
            foreach ($catalog as $cat) {
                $name = $cat['name'];
                if (isset($seen[$name])) {
                    continue;
                }
                $seen[$name] = true;
                $recs[] = [
                    'recommended_item' => $name,
                    'source_type' => 'Volume Popularity Fallback (Top DUS)',
                    'rule_id' => null,
                    'antecedents_str' => ! empty($triggers) ? implode(', ', $triggers) : 'GLOBAL',
                    'confidence' => null,
                    'lift' => null,
                    'zhang_metric' => null,
                    'avg_qty_dus' => round($cat['total_qty_dus'] / max(1, $cat['store_count']), 2),
                    'utility_score' => null,
                    'p_value_fisher' => null,
                ];
                if (count($recs) >= $topK) {
                    break;
                }
            }
        }

        return [
            'triggers' => $triggers,
            'store_profile' => $storeProfile,
            'recommendations' => $recs,
            'source_mode' => $rulesResp['source'] === 'supabase_rest' ? 'supabase_fallback' : 'local_fallback',
        ];
    }

    public function triggerMining(array $payload): array
    {
        try {
            $resp = Http::timeout((int) config('keramine.http_timeout', 20))
                ->post(config('keramine.fastapi_url') . '/api/mine', $payload);

            return [
                'ok' => $resp->successful(),
                'status' => $resp->status(),
                'data' => $resp->json(),
                'error' => $resp->successful() ? null : $resp->body(),
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'status' => 503,
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function simulateTransactions(array $payload, string $channel = 'fastapi'): array
    {
        $url = $channel === 'n8n'
            ? (string) config('keramine.n8n_webhook_url')
            : config('keramine.fastapi_url') . '/api/simulate-transactions';

        try {
            $resp = Http::timeout(25)->post($url, $payload);

            return [
                'ok' => $resp->successful(),
                'channel' => $channel,
                'status' => $resp->status(),
                'data' => $resp->json(),
                'error' => $resp->successful() ? null : $resp->body(),
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'channel' => $channel,
                'status' => 503,
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function resetDemoData(): array
    {
        try {
            $resp = Http::timeout(25)->post(config('keramine.fastapi_url') . '/api/reset-demo');
            Cache::forget('keramine_stores_v2');

            return [
                'ok' => $resp->successful(),
                'status' => $resp->status(),
                'data' => $resp->json(),
                'error' => $resp->successful() ? null : $resp->body(),
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'status' => 503,
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function querySupabase(string $table, array $params = []): ?array
    {
        $restUrl = config('keramine.supabase_rest_url');
        $key = config('keramine.supabase_secret_key') ?: config('keramine.supabase_publishable_key');
        if ($restUrl === '' || $key === '') {
            return null;
        }

        try {
            $pending = Http::timeout(8)->withHeaders([
                'apikey' => $key,
                'Authorization' => "Bearer {$key}",
            ]);

            $caBundle = (string) config('keramine.ca_bundle');
            if ($caBundle !== '' && is_file($caBundle)) {
                $pending = $pending->withOptions(['verify' => $caBundle]);
            }

            $resp = $pending->get("{$restUrl}/{$table}", $params);

            if ($resp->successful() && is_array($resp->json())) {
                return $resp->json();
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    private function readJsonArtifact(string $filename): mixed
    {
        $path = config('keramine.clean_data_dir') . DIRECTORY_SEPARATOR . $filename;
        if (! is_file($path)) {
            return null;
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            return null;
        }

        return json_decode($raw, true);
    }

    private function readCsvRules(): array
    {
        $csvPath = config('keramine.clean_data_dir') . DIRECTORY_SEPARATOR . 'mined_rules_catalog.csv';
        if (! is_file($csvPath)) {
            return [];
        }

        $rows = [];
        $fh = fopen($csvPath, 'r');
        if ($fh === false) {
            return [];
        }

        $headers = fgetcsv($fh);
        if (! is_array($headers)) {
            fclose($fh);

            return [];
        }

        while (($cols = fgetcsv($fh)) !== false) {
            if (count($cols) === count($headers)) {
                $rows[] = array_combine($headers, $cols);
            }
        }
        fclose($fh);

        return $rows;
    }

    private function parseListField(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_map('strval', $value));
        }

        $str = trim((string) $value);
        if ($str === '') {
            return [];
        }

        if (str_starts_with($str, '[')) {
            $jsonStr = str_replace("'", '"', $str);
            $decoded = json_decode($jsonStr, true);
            if (is_array($decoded)) {
                return array_values(array_map('strval', $decoded));
            }
        }

        return array_values(array_filter(array_map('trim', explode(',', $str))));
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower(trim((string) $value)), ['true', '1', 'yes'], true);
    }
}
