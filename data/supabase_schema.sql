-- ============================================================================
-- SKEMA DATABASE SUPABASE POSTGRESQL: PROYEK UAS ADAMIN - KERAMINE
-- Target Project: https://odtxjphklgvnpzicsysc.supabase.co
-- Standar: Postgres Best Practices (IDENTITY PK, TIMESTAMPTZ, Indexed FK, RLS)
-- ============================================================================

-- 1. Tabel Transaksi Distribusi Keramik B2B (15.986 baris non-kosong + live n8n)
CREATE TABLE IF NOT EXISTS public.keramine_transactions (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    source_row_hash TEXT UNIQUE NOT NULL,
    nota TEXT NOT NULL,
    tgl DATE NOT NULL,
    year_month TEXT NOT NULL,
    year_week TEXT NOT NULL,
    nama_toko TEXT NOT NULL,
    kode_toko INTEGER,
    namabara TEXT NOT NULL,
    kodebara INTEGER,
    merk_raw TEXT,
    merk_clean TEXT NOT NULL,
    ukuran TEXT NOT NULL,
    seri TEXT NOT NULL,
    merk_ukuran TEXT NOT NULL,
    qty NUMERIC NOT NULL,
    satuan TEXT DEFAULT 'DUS',
    pof TEXT NOT NULL,
    is_return BOOLEAN NOT NULL DEFAULT FALSE,
    source_batch TEXT NOT NULL DEFAULT 'historical_xlsx',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_keramine_tx_store_month ON public.keramine_transactions(nama_toko, year_month);
CREATE INDEX IF NOT EXISTS idx_keramine_tx_store_week  ON public.keramine_transactions(nama_toko, year_week);
CREATE INDEX IF NOT EXISTS idx_keramine_tx_nota        ON public.keramine_transactions(nota);
CREATE INDEX IF NOT EXISTS idx_keramine_tx_pof         ON public.keramine_transactions(pof);
CREATE INDEX IF NOT EXISTS idx_keramine_tx_is_return   ON public.keramine_transactions(is_return);

-- 2. Tabel Riwayat Eksekusi Pipeline Mining (Colab Seed & Live n8n Runs)
CREATE TABLE IF NOT EXISTS public.keramine_mining_runs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    triggered_by TEXT NOT NULL DEFAULT 'colab_seed',
    basket_type TEXT NOT NULL DEFAULT 'store_month',
    item_level TEXT NOT NULL DEFAULT 'SERI',
    algorithm TEXT NOT NULL DEFAULT 'fpgrowth',
    min_support DOUBLE PRECISION NOT NULL,
    min_confidence DOUBLE PRECISION NOT NULL,
    total_baskets INTEGER NOT NULL,
    rules_found INTEGER NOT NULL,
    significant_rules INTEGER NOT NULL,
    exec_time_ms DOUBLE PRECISION NOT NULL,
    notes TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- 3. Tabel Katalog Aturan Asosiasi Multi-Level & Metrik SOTA
CREATE TABLE IF NOT EXISTS public.keramine_rules (
    id TEXT PRIMARY KEY, -- SHA-256 hash dari (basket_type | item_level | antecedents | consequents)
    run_id UUID REFERENCES public.keramine_mining_runs(id) ON DELETE SET NULL,
    basket_type TEXT NOT NULL,
    item_level TEXT NOT NULL,
    antecedents JSONB NOT NULL,
    consequents JSONB NOT NULL,
    antecedents_str TEXT NOT NULL,
    consequents_str TEXT NOT NULL,
    n_a INTEGER NOT NULL,
    n_b INTEGER NOT NULL,
    n_ab INTEGER NOT NULL,
    total_baskets INTEGER NOT NULL,
    support DOUBLE PRECISION NOT NULL,
    confidence DOUBLE PRECISION NOT NULL,
    lift DOUBLE PRECISION NOT NULL,
    leverage DOUBLE PRECISION NOT NULL,
    conviction DOUBLE PRECISION NOT NULL,
    kulczynski DOUBLE PRECISION NOT NULL,
    zhang_metric DOUBLE PRECISION NOT NULL,
    total_qty_dus NUMERIC NOT NULL DEFAULT 0,
    avg_qty_dus DOUBLE PRECISION NOT NULL DEFAULT 0,
    utility_score DOUBLE PRECISION NOT NULL DEFAULT 0,
    p_value_fisher DOUBLE PRECISION NOT NULL DEFAULT 1.0,
    q_value_bh DOUBLE PRECISION NOT NULL DEFAULT 1.0,
    is_significant BOOLEAN NOT NULL DEFAULT FALSE,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_keramine_rules_run_id
    ON public.keramine_rules(run_id);
CREATE INDEX IF NOT EXISTS idx_keramine_rules_lookup
    ON public.keramine_rules(basket_type, item_level, is_significant, lift DESC);

-- 4. Tabel Profil Ringkasan 237 Toko Pelanggan B2B
CREATE TABLE IF NOT EXISTS public.keramine_store_profiles (
    nama_toko TEXT PRIMARY KEY,
    kode_toko INTEGER,
    dominant_pof TEXT,
    total_transactions INTEGER NOT NULL DEFAULT 0,
    total_baskets_month INTEGER NOT NULL DEFAULT 0,
    total_unique_skus INTEGER NOT NULL DEFAULT 0,
    total_qty_dus NUMERIC NOT NULL DEFAULT 0,
    return_count INTEGER NOT NULL DEFAULT 0,
    top_merks JSONB NOT NULL DEFAULT '[]'::jsonb,
    top_seris JSONB NOT NULL DEFAULT '[]'::jsonb,
    top_ukurans JSONB NOT NULL DEFAULT '[]'::jsonb,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Row Level Security (RLS): Publik boleh SELECT untuk analitik UI, write hanya via secret/service_role
ALTER TABLE public.keramine_transactions ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.keramine_mining_runs ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.keramine_rules ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.keramine_store_profiles ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "Allow public read keramine_transactions" ON public.keramine_transactions;
CREATE POLICY "Allow public read keramine_transactions"
    ON public.keramine_transactions FOR SELECT TO anon, authenticated USING (true);

DROP POLICY IF EXISTS "Allow public read keramine_mining_runs" ON public.keramine_mining_runs;
CREATE POLICY "Allow public read keramine_mining_runs"
    ON public.keramine_mining_runs FOR SELECT TO anon, authenticated USING (true);

DROP POLICY IF EXISTS "Allow public read keramine_rules" ON public.keramine_rules;
CREATE POLICY "Allow public read keramine_rules"
    ON public.keramine_rules FOR SELECT TO anon, authenticated USING (true);

DROP POLICY IF EXISTS "Allow public read keramine_store_profiles" ON public.keramine_store_profiles;
CREATE POLICY "Allow public read keramine_store_profiles"
    ON public.keramine_store_profiles FOR SELECT TO anon, authenticated USING (true);
