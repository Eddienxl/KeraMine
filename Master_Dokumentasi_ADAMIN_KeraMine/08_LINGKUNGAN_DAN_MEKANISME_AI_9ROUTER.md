# Lingkungan Pengembangan & Mekanisme Kerja AI (9Router + Antigravity + Colab): KeraMine

**Versi:** 0.1.0 — **AKTIF (Protokol Trigger `-9router` + Alur Kerja Notebook Colab + Supabase MCP)**  
**Diperbarui:** 10 Nov 2026

---

## 1. Status Lingkungan Mesin Pengembang

| Komponen / Alat | Status Saat Ini | Peran pada Proyek KeraMine |
|---|---|---|
| **Node.js & npm** | ✔ `v22.14.0`, npm `10.9.2` | Menjalankan server **9Router**, jembatan MCP `router9`, serta siap menjalankan **n8n** (`npx n8n`) |
| **PHP / Laravel Tools** | ✔ **Terpasang** (`PHP 8.3.36`, `Composer 2.11.2`, ekstensi `pdo_sqlite`, `curl`, `openssl`; DLL `php_pdo_pgsql.dll` tersedia di `ext/`) | Opsi antarmuka web portal bisnis **Laravel** (`keramine-laravel`) yang terintegrasi dengan Supabase, FastAPI, n8n & Streamlit |
| **9Router Gateway** | ✔ **Aktif di Background** (`http://127.0.0.1:20128`) | Menyediakan *second opinion* lintas-model melalui combo gratis resmi `smart-free` (`groq/openai/gpt-oss-120b`, `groq/qwen/qwen3.8-27b`, dll.) |
| **Jembatan MCP `router9`** | ✔ **Terpasang & Teruji** | Memanggil `router9_list_models`, `router9_ask_model`, dan `router9_second_opinion` |
| **Jembatan MCP `supabase`** | ✔ **Terpasang & Terhubung** (`https://odtxjphklgvnpzicsysc.supabase.co`) | Mengelola migrasi tabel Postgres (`apply_migration`), query SQL (`execute_sql`), dan pengambilan kunci publik untuk project **KeraMine** |
| **Google Colab** | ✔ **Lingkungan Utama Pemodelan Python** | Menjalankan `notebooks/01_...ipynb` dan `notebooks/02_...ipynb` dan mengekspor `.zip` ke `data/clean/` |
| **Python Lokal (`py`)** | ✔ `Python 3.13.7` via launcher `py` (`streamlit 1.67.0`, `plotly 7.1.0`, `pandas`, `numpy`, `scipy`, `openpyxl` terpasang; `mlxtend` & `fastapi` akan dipasang saat Fase 5/6) | Menjalankan `api-engine` (FastAPI) dan `keramine-app` (Streamlit) di mesin lokal untuk demo UAS |

---

## 2. Protokol Mekanisme Kerja: Kata Kunci Trigger `-9router`

Sesuai instruksi pengguna, setiap kali pesan atau perintah pengguna menyertakan kata kunci **`-9router`** (atau selama pengguna menetapkan penggunaan `-9router` untuk perancangan/pengerjaan):

1. **Antigravity WAJIB memanggil tool MCP `router9`** (`router9_second_opinion` atau `router9_ask_model` dengan model `groq/openai/gpt-oss-120b` / `smart-free`) untuk meminta opini kedua yang kritis sebelum atau saat menyelesaikan tugas.
2. Cakupan penggunaan `-9router` meliputi:
   * Perancangan arsitektur sistem, definisi keranjang (*basket*), dan skema database Supabase.
   * Verifikasi rumus matematika dan statistik SOTA Association Rules (*Zhang's Metric*, *Conviction*, *Leverage*, *Kulczynski*, *Utility-Weighted Score*, *Fisher's Exact Test + Benjamini-Hochberg*).
   * *Code review* pada notebook Colab (`.ipynb`), layanan FastAPI, workflow n8n, dan aplikasi Streamlit.
   * Peninjauan struktur dan narasi akademis pada `Laporan_UAS_ADAMIN_KeraMine.html`.
3. Antigravity menyajikan sintesis antara analisis internal dan masukan dari 9Router secara transparan kepada pengguna.

---

## 3. Log Konsultasi 9Router pada Proyek KeraMine

| Tanggal | Model 9Router | Topik Diskusi (`task_type`) | Intisari Masukan 9Router & Keputusan yang Diambil |
|---|---|---|---|
| 10 Nov 2026 | `openai/gpt-oss-120b` | Evaluasi hasil profiling `datasetarule.xlsx` & definisi *Basket* (`general_analysis`) | Mengonfirmasi bahwa `NOTA` (1 item/baris) tidak boleh dipakai sebagai *basket*. Merekomendasikan `Toko + Bulan` (92,7% multi-item) sebagai unit utama dan hierarki item multi-level, serta memisahkan 160 baris retur (`QTY <= 0`). |
| 10 Nov 2026 | `openai/gpt-oss-120b` | Desain arsitektur pipeline `n8n` + FastAPI + Supabase + Streamlit (`general_analysis`) | Merekomendasikan alur 7-node di n8n (Trigger $\rightarrow$ Fetch/Ingest $\rightarrow$ Transform $\rightarrow$ FastAPI `/mine` $\rightarrow$ Parse $\rightarrow$ Supabase Upsert $\rightarrow$ Notification/Refresh). |
| 10 Nov 2026 | `openai/gpt-oss-120b` | Audit kritis struktur proyek `KeraMine` & temuan pola distribusi data (`general_analysis`) | Menyarankan agar temuan distribusi merek yang merata dijadikan nilai tambah metodologis (*data quality audit framework*), menambahkan uji signifikansi **Fisher's Exact Test + Benjamini-Hochberg**, menjadikan level **`SERI`** sebagai level utama rekomendasi dengan *fallback* popularitas, dan menjaga perhitungan *Utility-Weighted* tetap bersih tanpa ketergantungan pustaka Java eksternal. |
| 10 Nov 2026 | `openai/gpt-oss-120b` | Keep-alive Supabase Free Tier (7 hari), review Colab Notebook 01 (`SERI` & `ukuran`), dan Postgres Best Practices (`general_analysis`) | Menyetujui **GitHub Actions cron (`0 0 */3 * *`)** query ringan `keramine_mining_runs?select=id&limit=1` memakai `SUPABASE_PUBLISHABLE_KEY` (aman tanpa `service_role`); memvalidasi regex `ukuran`, ekstraksi `SERI` dengan skip token `KW/WD/angka`, serta penambahan `source_row_hash` & indeks FK `run_id`. |
| 10 Nov 2026 | `openai/gpt-oss-120b` | Diskusi Arsitektur Hibrida **Laravel + Streamlit + FastAPI + n8n + Supabase** (`general_analysis`) | Menegaskan pembagian peran bebas tumpang-tindih: **Laravel** = portal web bisnis B2B (auth, toko/produk, wizard rekomendasi, riwayat), **FastAPI** = *rule engine* Python, **Streamlit** = *Analysis Lab* (EDA, SOTA benchmark, *network graph*), **n8n** = orkestrator real-time, **Supabase** = pusat data tunggal. Algoritma ARM tetap di Python (tidak ditulis ulang di PHP). |


| 4 | **Fase 6–8 (`smart-free` / `openai/gpt-oss-120b`)** | Audit akhir portal Laravel Opsi 1, pencegahan double-click reset demo, argumentasi hasil `61/104` rule `SERI` vs level makro `MERK`/`UKURAN`, dan checklist n8n + `Laporan_UAS_ADAMIN_KeraMine.html`. | **Diadopsi:** (1) Menambahkan debounce/disable submit pada tombol Reset Demo & Simulator di `pipeline.blade.php`; (2) Memperbaiki verifikasi TLS PHP-Windows dengan salinan CA bundle terverifikasi (`storage/app/certs/cacert.pem`) agar badge `LIVE SUPABASE` menyala hijau tanpa menurunkan verifikasi sertifikat; (3) Menegaskan argumentasi dominasi level `SERI` dan panduan n8n webhook di `README_DEMO_UAS.md` & laporan akhir. |
