# Panduan Demo 1-Klik — KeraMine B2B (UAS Data Mining)

Sistem Rekomendasi B2B Restocking & Bundling Produk Keramik Berbasis Multi-Level SOTA Association Rule Mining dengan Pipeline Auto-Realtime n8n & Supabase Cloud.

## 1. Menjalankan Semua Layanan (1-Klik)

Klik ganda **`start_keramine_demo.bat`** di folder root proyek. Script akan membuka 5 terminal terpisah dan membuka portal di browser:

| Layanan | URL Lokal | Peran Utama |
| :--- | :--- | :--- |
| **Laravel 13 Portal B2B** | http://127.0.0.1:8000 | Portal operasional Sales POF (`DIAN`, `ROBBY`) & Admin Gudang (SQLite Opsi 1 + REST Supabase & FastAPI) |
| **Streamlit Analysis Lab** | http://127.0.0.1:8501 | Laboratorium eksplorasi EDA, Graf Jaringan Plotly+NetworkX, Inspektor Kontingensi $2\times 2$, & Benchmark SOTA |
| **FastAPI Rule Engine** | http://127.0.0.1:8001/docs | Engine Association Rule Mining (NumPy vectorized + `scipy` Fisher's Exact + Benjamini-Hochberg + Utility Score) |
| **n8n Automation Studio** | http://127.0.0.1:5678 | Orkestrasi ingest transaksi real-time & scheduled auto-remine (`n8n/keramine_realtime_pipeline.json`) |
| **9Router AI Gateway** | http://127.0.0.1:20128 | Gateway AI kolaboratif (protokol `-9router`) |

## 2. Akun Demo Portal Laravel (`http://127.0.0.1:8000/login`)

Semua akun tersedia dalam tombol **Demo Login 1-Klik** (password default: `password`):
- **Admin Gudang:** `admin@keramine.id` (akses seluruh 237 toko, re-mine on-demand, simulator & reset demo)
- **Sales POF DIAN:** `dian@keramine.id` (otomatis memprioritaskan 71 toko kelolaan POF DIAN)
- **Sales POF ROBBY:** `robby@keramine.id` (otomatis memprioritaskan 55 toko kelolaan POF ROBBY)

## 3. Skenario Alur Demo Presentasi UAS

1. **Eksplorasi & Kejujuran Ilmiah (`Streamlit :8501`):**
   - Buka Tab 1 (*Forensik & EDA*) untuk memperlihatkan kenapa `NOTA` tidak dapat dijadikan keranjang (`0,01%` multi-item) vs `Toko + Bulan` (`92,53%` multi-item), serta isolasi 146 baris `QTY = 0`.
   - Buka Tab 2 (*Graf Jaringan*) & Tab 3 (*Inspektor $2\times 2$*) pada rule `CUBIC -> MIRANDA` (Lift `4,44x`, Zhang `+0,781`, Fisher signifikan).
2. **Rekomendasi B2B (`Laravel :8000/recommender`):**
   - Login sebagai `DIAN`, pilih toko `ABAD BANGUNAN, TOKO`, pemicu `DIEGO`, tampilkan rekomendasi tervalidasi `GRANIT` (Lift `3,96x`, Zhang `+0,760`), lalu simpan ke Riwayat SQLite dan uji ekspor CSV.
3. **Simulasi Real-Time & Reset 1-Klik (`Laravel :8000/pipeline`):**
   - Kirim transaksi simulasi baru (`DIEGO + GRANIT`, `75 DUS`) via FastAPI / n8n webhook, amati kenaikan jumlah baris di Supabase + auto re-mine `keramine_mining_runs`.
   - Klik **Reset Demo ke Baseline (15.986 Tx)** untuk membersihkan transaksi simulasi dan memulihkan tepat `784` aturan baseline.

## 4. Perintah Verifikasi Otomatis

- **Paritas Engine Python ↔ Colab:** `py api-engine/tests/test_arm_contract.py`
- **Portal Laravel Feature Tests:** `cd keramine-laravel && php artisan test`
- **Streamlit UI Smoke Test:** `py -c "from streamlit.testing.v1 import AppTest; at = AppTest.from_file('keramine-lab/app.py', default_timeout=25); at.run(); assert not at.exception"`

