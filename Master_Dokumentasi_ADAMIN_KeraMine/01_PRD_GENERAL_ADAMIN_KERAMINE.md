# Product Requirements Document (PRD): KeraMine — Sistem Rekomendasi B2B Restocking & Bundling Produk Keramik Berbasis Multi-Level SOTA Association Rule Mining & Pipeline Auto-Realtime n8n

**Mata Kuliah:** Analisis Data Mining (ADAMIN) — UAS Semester 7  
**Versi Dokumen:** 1.0.0 (Fase 1 s.d. Fase 8 Selesai 100% — Ekosistem Hibrida Laravel :8000, Streamlit :8501, FastAPI :8001, n8n :5678, Supabase Cloud & Laporan UAS Live Terverifikasi — 11 Nov 2026)  
**Nama Produk:** `KeraMine` (*Keramik Association Mining & Real-Time Recommendation Engine*)

---

## Ringkasan Progres Proyek Saat Ini (Master Progress Tracker)

| Fase | Nama Fase | Progres | Status |
|---|---|---|---|
| **Fase 1** | Perencanaan, Forensik Awal Dataset, Arsitektur SOTA & Master Dokumentasi + Laporan Live | 100% | ✔ **SELESAI** |
| **Fase 2** | Pipeline Data Cleaning, Normalisasi Entitas & Rekayasa Multi-Level Basket (Colab Notebook 01) | 100% | ✔ **SELESAI** (15.840 tx positif + 146 retur) |
| **Fase 3** | Eksplorasi & Validasi SOTA Association Rules: Apriori vs FP-Growth vs FP-Max, Zhang's Metric, Utility-ARM & Uji Fisher-BH (Colab Notebook 02) | 100% | ✔ **SELESAI** (784 rules diekspor) |
| **Fase 4** | Konfigurasi Proyek **Supabase** (via MCP) & Migrasi + Seeding Database Cloud (`keramine_*`) | 100% | ✔ **SELESAI** (15.986 tx, 237 toko, 8 runs, 784 rules) |
| **Fase 5** | Pembangunan Layanan **Rule Engine API** (FastAPI `/api/*` untuk n8n, Laravel & Streamlit) | 100% | ✔ **SELESAI** (Parity `5.55e-17`, ~244 ms) |
| **Fase 6** | Pembangunan Aplikasi Hibrida **Laravel Portal B2B** (`keramine-laravel :8000`) & **Streamlit Analysis Lab** (`keramine-lab :8501`) | 100% | ✔ **SELESAI** (7 PHPUnit tests pass; AppTest 0 error) |
| **Fase 7** | Implementasi **Pipeline Auto-Realtime n8n** (Webhook/Simulator Transaksi $\rightarrow$ API $\rightarrow$ Supabase $\rightarrow$ Notifikasi) | 100% | ✔ **SELESAI** (n8n v2.8.4 `LPw6W9YXJjI1ktLV` HTTP 200) |
| **Fase 8** | Pengujian End-to-End, Finalisasi Bab 4 & Bab 5 `Laporan_UAS_ADAMIN_KeraMine.html` & Paket Demo 1-Klik | 100% | ✔ **SELESAI** (`start_keramine_demo.bat` & laporan lengkap) |

---

## 1. Executive Summary & Visi Produk

### 1.1 Latar Belakang & Pernyataan Masalah
* **Perbedaan Karakteristik Transaksi B2B vs Ritel B2C:** Pada studi kasus klasik buku teks (seperti *Data Mining for the Masses* Bab 5 oleh Matthew North), aturan asosiasi diterapkan pada survei biner atau keranjang belanja kasir (*point-of-sale*). Sebaliknya, pada distribusi bahan bangunan/keramik B2B (`datasetarule.xlsx`, 16.000 baris transaksi), setiap baris pengiriman dicatat dengan nomor faktur (`NOTA`) tersendiri (15.985 nomor `NOTA` unik dari 16.000 baris, rata-rata 1,00 item per nota). Pendekatan naif berbasis `NOTA` menghasilkan **0 aturan asosiasi**.
* **Kebutuhan Rekomendasi *Restocking* & *Bundling* Lintas-Level:** Toko bangunan memesan ulang (*restock*) barang secara periodik (mingguan/bulanan) melalui tenaga penjual (*Point of Sales/Field*, kolom `POF`: Dian, Robby, Joni, Bari). Sales membutuhkan panduan objektif pada tiga tingkat: kombinasi **Merek (`MERK`)**, kombinasi **Seri Motif & Ukuran (`SERI_UKURAN`, mis. 40X40 bersama 25X40)**, serta kombinasi **Produk Spesifik (`NAMABARA`)**.
* **Keterbatasan Algoritma Klasik (Apriori Murni):** Algoritma Apriori/FP-Growth klasik hanya melihat kemunculan biner (0/1) tanpa memperhitungkan **kuantitas dus (`QTY`)**, menghasilkan banyak aturan redundan, dan rentan memunculkan aturan kebetulan (*spurious rules*) ketika distribusi item merata.
* **Proses Pembaruan Aturan yang Statis:** Di banyak penelitian mahasiswa, model *association rule* berhenti di dalam *notebook* statis dan harus dijalankan ulang secara manual setiap kali ada transaksi baru.

### 1.2 Solusi: KeraMine
`KeraMine` adalah platform analitik dan sistem rekomendasi *restocking* B2B terintegrasi yang:
1. Mengagregasi transaksi B2B menjadi **Multi-Period Basket** (`Toko + Bulan` sebagai keranjang utama dengan **92,7% multi-item**, serta `Toko + Minggu` sebagai keranjang taktis) dan **Multi-Level Item Hierarchy** (Level 1: `MERK` / Ukuran, Level 2: `SERI` & `MERK_UKURAN`, Level 3: `NAMABARA` / SKU).
2. Mengimplementasikan eksplorasi **State-of-the-Art (SOTA) Association Rule Mining**:
   - Komparasi kinerja dan hasil antara **Apriori**, **FP-Growth**, dan **FP-Max (Maximal Frequent Itemsets)**.
   - Evaluasi multi-metrik modern: *Support*, *Confidence*, *Lift*, **Leverage**, **Conviction**, dan **Zhang's Metric** ($-1$ s.d. $+1$).
   - **Quantity/Utility-Weighted Association Rules (WARM):** Membobotkan kekuatan aturan menggunakan volume dus (`QTY`) sehingga paket rekomendasi selaras dengan kontribusi omzet fisik.
   - **Statistical Significance Pruning:** Menyaring aturan kebetulan menggunakan **Fisher's Exact Test** dua arah dengan koreksi *False Discovery Rate* **Benjamini-Hochberg (FDR-BH)**.
3. Mengotomatiskan pembaruan aturan secara *near-real-time* melalui **Pipeline n8n** yang terhubung ke **FastAPI Rule Engine** dan database cloud **Supabase PostgreSQL**.
4. Menyajikan antarmuka interaktif **Streamlit SaaS Dashboard** (dwibahasa ID/EN, Light/Dark mode) untuk eksplorasi aturan, grafik jaringan (*network graph*), simulator paket *bundling* per toko & per sales (`POF`), serta pemantauan *rule drift* langsung dari Supabase.

### 1.3 Ruang Lingkup Proyek UAS
| Termasuk dalam Ruang Lingkup | Tidak Termasuk dalam Ruang Lingkup |
|---|---|
| Pembersihan & rekayasa fitur `datasetarule.xlsx` (16.000 baris) via Google Colab | Sistem akuntansi/ERP penagihan piutang toko |
| Multi-Level ARM (Apriori, FP-Growth, FP-Max) + Zhang's Metric + Utility-Weighted (`QTY`) + Uji Fisher-BH | Pemindaian barcode perangkat keras di gudang |
| Penyimpanan transaksi, *run log*, dan katalog *rules* di **Supabase PostgreSQL** | Aplikasi mobile Android/iOS native |
| Layanan **FastAPI** (`/mine`, `/recommend`, `/simulate`, `/health`) | Integrasi WhatsApp Business API berbayar (digantikan Telegram Bot / Webhook gratis di n8n) |
| **Workflow Otomatis n8n** (Trigger Cron/Webhook $\rightarrow$ Mining Engine $\rightarrow$ Supabase Upsert $\rightarrow$ Alert) | — |
| Dashboard **Streamlit** dwibahasa (ID/EN) + Laporan Akademis Live HTML | — |

---

## 2. Target Pengguna (User Persona)

| Persona | Peran di Konteks Data | Kebutuhan Utama pada KeraMine |
|---|---|---|
| **Sales Distributor (`POF`: Dian, Robby, Joni, Bari)** | Mencatat pesanan ke 237 toko pelanggan (~4.000 transaksi per sales) | Mengetahui produk/seri/merek apa yang harus ditawarkan (*cross-sell*) saat mengunjungi toko tertentu bulan ini. |
| **Manajer Distribusi & Gudang** | Mengatur stok 438 SKU keramik dari 21–22 merek dalam satuan `DUS` | Mengetahui paket *bundling* ukuran (`40X40`, `25X40`, `50X50`, `25X25`) dengan volume (`QTY`) tertinggi serta pola barang retur (`QTY <= 0`). |
| **Pemilik Toko Bangunan (Pelanggan B2B)** | 237 toko (mis. *Toko Kunci Sukses*, *Toko Rizki Mandiri*) | Mendapatkan rekomendasi paket *restocking* bulanan yang sesuai dengan riwayat belanja tokonya dan tren toko serupa. |
| **Analis Data / Penguji Akademis** | Mengevaluasi metodologi CRISP-DM & SOTA Data Mining | Membandingkan waktu komputasi Apriori vs FP-Growth vs FP-Max, mengaudit nilai *p-value* Fisher-BH, Zhang's Metric, serta memicu pipeline *live* n8n. |

---

## 3. Spesifikasi Fungsional

### 3.1 Modul 1: Pipeline Forensik, Pembersihan & Multi-Level Basket (Colab Notebook 01)
* Membaca `datasetarule.xlsx` (16.000 baris, 10 kolom: `NAMA`, `NAMABARA`, `TGL`, `NOTA`, `QTY`, `SATUAN`, `KODE`, `KODEBARA`, `POF`, `MERK`).
* **Normalisasi Entitas & Perbaikan Anomali:**
  - Mengonversi tanggal serial Excel (`TGL`) menjadi format ISO `YYYY-MM-DD`, `YEAR_MONTH` (`YYYY-MM`), dan `YEAR_WEEK` (`YYYY-Www`).
  - Memperbaiki inkonsistensi penulisan merek (contoh: menyatukan typo `PISSCASO` [364 baris] ke `PICASSO` [368 baris] atau menandainya dengan flag audit, serta menangani 1 baris `MERK` kosong).
  - Memisahkan **160 baris transaksi retur/batal (`QTY <= 0`)** ke artefak khusus analisis retur (`returns_log.csv`) agar tidak mendistorsi perhitungan frekuensi pembelian positif.
* **Ekstraksi Fitur Hierarki Produk (4 Level Granularitas):**
  - **Level 1A (`MERK`):** Merek keramik bersih.
  - **Level 1B (`UKURAN`):** Dimensi keramik yang diekstrak via regex `(\d{2,3}\s*X\s*\d{2,3})` dari `NAMABARA` (`40X40`, `25X40`, `50X50`, `25X25`, `25X50`, `60X60`, `20X40`).
  - **Level 2A (`MERK_UKURAN`):** Kombinasi merek dan ukuran (mis. `PICASSO_40X40`).
  - **Level 2B (`SERI`):** Nama seri/motif utama keramik tanpa kode warna dan ukuran (mis. `ESMERALDA STONE`, `NEO TERRACOTA`, `DAKOTA`, `GLAMWOOD`).
  - **Level 3 (`NAMABARA`):** SKU lengkap (438 produk).
* **Pembentukan Keranjang (*Basket Aggregation*):**
  - `BASKET_STORE_MONTH` (`NAMA` + `YEAR_MONTH`): Unit keranjang utama (~3.949 basket positif, ~92,7% multi-item).
  - `BASKET_STORE_WEEK` (`NAMA` + `YEAR_WEEK`): Unit keranjang mingguan (~10.500 basket, ~38,7% multi-item).

### 3.2 Modul 2: Mesin SOTA Association Rule Mining & Validasi Statistik (Colab Notebook 02 & FastAPI)
* **Komparasi 3 Algoritma FIM:** Menjalankan dan mencatat waktu eksekusi serta jumlah *itemset* dari **Apriori**, **FP-Growth**, dan **FP-Max** pada berbagai ambang *min_support*.
* **Perhitungan Metrik Klasik & SOTA:**
  - *Support*, *Confidence*, *Lift*, *Leverage*, *Conviction*, **Zhang's Metric**, **Kulczynski**, dan **Jaccard**.
* **Quantity/Utility-Weighted Scoring (`QTY`):**
  - Menghitung *Volume Support* (total dus `QTY` pada transaksi yang memuat $A \cup B$) dan *Utility-Weighted Lift* untuk mengidentifikasi paket keramik beromzet fisik terbesar.
* **Uji Signifikansi Statistik (Anti-Spurious Filter):**
  - Menghitung tabel kontingensi $2\times 2$ untuk setiap aturan $A \Rightarrow B$, menjalankan **Fisher's Exact Test** (`p_value`), dan menerapkan koreksi **Benjamini-Hochberg (`q_value_bh`)** untuk memisahkan aturan yang benar-benar signifikan dari fluktuasi acak.

### 3.3 Modul 3: Database Cloud Supabase PostgreSQL
* Menyimpan data transaksi bersih, riwayat eksekusi pipeline (*mining runs*), katalog aturan asosiasi lintas-level (`mined_rules`), dan profil ringkasan toko/sales.
* Dikelola dan dimigrasikan secara terstruktur melalui **Supabase MCP Server** (`apply_migration`, `execute_sql`, `list_tables`).

### 3.4 Modul 4: Pipeline Auto-Realtime n8n + FastAPI Rule Engine
* **Webhook & Cron Trigger di n8n:** Menerima transaksi baru (baik dari input form/simulator maupun penjadwalan otomatis).
* **Simulator Transaksi Live (Untuk Demo UAS):** Memungkinkan dosen/penguji menyuntikkan batch transaksi baru (mis. pesanan baru untuk bulan berjalan) dengan 1 klik, memicu workflow n8n, menjalankan ulang *mining engine* di FastAPI, memperbarui tabel `mined_rules` di Supabase, dan mengirim ringkasan aturan baru ke Telegram/Log Dashboard.

### 3.5 Modul 5: Aplikasi Dashboard Interaktif Streamlit (`keramine-app`)
* **Tab 1 — Executive EDA & Forensik Dataset:** Visualisasi 16.000 baris transaksi, bukti empiris mengapa `NOTA` bukan keranjang belanja vs `Toko + Bulan`, distribusi ukuran/merek/sales (`POF`), dan analisis 160 transaksi retur.
* **Tab 2 — Multi-Level Rule Explorer & Network Graph:** Filter interaktif berdasarkan Level Item (`SERI`, `MERK_UKURAN`, `MERK`, `UKURAN`, `SKU`), periode keranjang (`Toko+Bulan` / `Toko+Minggu`), sales (`POF`), slider *Support / Confidence / Lift / Zhang's Metric*, toggle **"Hanya Aturan Signifikan (Fisher-BH)"**, serta visualisasi *Interactive Network Graph* & *Scatter Plot*.
* **Tab 3 — Smart Restocking & Bundling Recommender (Per Toko & Sales):** Pilih nama toko pelanggan (dari 237 toko) atau pilih produk yang sedang dipesan $\rightarrow$ sistem merekomendasikan pasangan produk *bundling* berdasarkan aturan terkuat + estimasi kuantitas dus (`QTY`) + *fallback* popularitas transparan bila aturan spesifik tidak ditemukan.
* **Tab 4 — SOTA Benchmark Lab:** Perbandingan langsung waktu komputasi & jumlah *itemsets* Apriori vs FP-Growth vs FP-Max, analisis *Utility-Weighted Rules* (`QTY`), dan distribusi nilai *p-value* uji hipotesis.
* **Tab 5 — Live n8n Pipeline & Supabase Monitor:** Status koneksi real-time ke Supabase & FastAPI, tombol **Trigger Simulator Transaksi Baru via n8n**, dan tabel log eksekusi pipeline (`mining_runs`) beserta pendeteksian *rule drift*.

---

## 4. Spesifikasi Non-Fungsional

| Aspek | Target |
|---|---|
| **Kinerja Mining** | Ekstraksi FP-Growth pada 3.949 basket selesai $\le 1,5$ detik di FastAPI/Streamlit |
| **Akurasi Matematis** | Rumus *Support, Confidence, Lift, Leverage, Conviction, Zhang's Metric*, dan *Fisher Exact Test* terverifikasi kontrak JSON (`arm_fixture.json`) antara Colab dan aplikasi lokal (toleransi $10^{-6}$) |
| **Transparansi Ilmiah** | Menampilkan secara jujur perbandingan aturan sebelum vs sesudah penyaringan signifikansi statistik (Fisher-BH) |
| **Desain UI/UX** | Mengikuti `02_DESIGN_SYSTEM_ADAMIN_KERAMINE.md` (Modern SaaS Dashboard Light/Dark + Dwibahasa ID/EN) |
| **Reproducibility** | Seluruh pemrosesan data dan eksperimen SOTA terbungkus dalam Google Colab Notebooks (`01` dan `02`) yang menghasilkan ZIP artefak bersih ke `data/clean/` |
| **Integrasi AI** | Mematuhi protokol kata kunci **`-9router`** untuk *second opinion* lintas-model (`smart-free`) pada setiap tahap |

---

## 5. Tahapan Detail Penyelesaian Proyek & Pelacak Progres (Detailed Roadmap)

Bagian ini menjadi acuan tunggal (*Single Source of Truth*) yang selalu diperbarui setiap kali sebuah fase selesai dikerjakan.

### Fase 1: Perencanaan, Forensik Awal Dataset, Arsitektur SOTA & Master Dokumentasi — ✔ SELESAI
- [x] **1.1** Profiling awal `datasetarule.xlsx` (16.000 baris, 237 toko, 438 produk, 22 merek, 4 sales `POF`, 160 baris retur `QTY <= 0`, dan pembuktian `NOTA` = 1 item/baris vs `Toko + Bulan` = 92,7% multi-item).
- [x] **1.2** Diskusi arsitektur & *second opinion* via **9Router** (`openai/gpt-oss-120b`) mengenai definisi Multi-Level Basket/Item, eksplorasi SOTA (FP-Growth/FP-Max, Zhang's Metric, Utility-Weighted Rules, Fisher's Exact + Benjamini-Hochberg), dan desain pipeline **n8n + Supabase + FastAPI + Streamlit**.
- [x] **1.3** Penyusunan 8 dokumen di `Master_Dokumentasi_ADAMIN_KeraMine/` (`01_PRD` s.d. `08_LINGKUNGAN_DAN_MEKANISME_AI_9ROUTER`).
- [x] **1.4** Penyusunan dokumen laporan akademis hidup [`Laporan_UAS_ADAMIN_KeraMine.html`](../Laporan_UAS_ADAMIN_KeraMine.html) (Cover, Kata Pengantar, Daftar Isi/Tabel/Gambar, Bab 1, Bab 2, Bab 3, kerangka Bab 4 & Bab 5, serta Daftar Pustaka).

### Fase 2: Pipeline Data Cleaning, Normalisasi Entitas & Rekayasa Multi-Level Basket (Colab Notebook 01) — ✔ SELESAI
- [x] **2.1** Pembuatan & verifikasi dry-run [`notebooks/01_data_cleaning_basket_engineering.ipynb`](../notebooks/01_data_cleaning_basket_engineering.ipynb) (`15.986` baris aktif, `146` baris `QTY = 0` dipisah ke `returns_log.csv`, `15.840` transaksi positif, `21` merek bersih setelah `PISSCASO` $\rightarrow$ `PICASSO`, `220` seri, `8` ukuran, `237` profil toko).
- [x] **2.2** Eksekusi *Run All* oleh pengguna di Google Colab selesai & `adamin_colab_01_outputs.zip` (`1.067 KB`) telah diekstrak ke `data/clean/` (`92,53%` multi-item pada `Toko + Bulan`, `794.064 DUS`).

### Fase 3: Eksplorasi & Validasi SOTA Association Rules (Colab Notebook 02) — ✔ SELESAI
- [x] **3.1** Pembuatan & kalibrasi ambang batas [`notebooks/02_sota_association_rules_validation.ipynb`](../notebooks/02_sota_association_rules_validation.ipynb) (`Apriori` vs `FP-Growth` vs `FP-Max`, Zhang's Metric, Utility-QTY, Fisher's Exact + Benjamini-Hochberg FDR, serta Evaluasi Temporal Out-of-Time).
- [x] **3.2** Eksekusi *Run All* di Google Colab selesai & `adamin_colab_02_outputs.zip` (`265 KB`) diekstrak ke `data/clean/` (`784 rules`, `61/104` signifikan Fisher pada `SERI`, `arm_fixture.json` tervalidasi).

### Fase 4: Konfigurasi Proyek Supabase (via MCP) & Migrasi Skema Database — ✔ SELESAI
- [x] **4.1** Koneksi project Supabase **KeraMine** (`https://odtxjphklgvnpzicsysc.supabase.co`) via MCP (`skip_elicitations=execute_sql,apply_migration`) & REST API terverifikasi, ditambah workflow *keep-alive* anti-pause 7 hari [`.github/workflows/supabase-keep-alive.yml`](../.github/workflows/supabase-keep-alive.yml).
- [x] **4.2** Migrasi Postgres Best Practices via MCP `apply_migration` (`20261110162650_create_keramine_initial_schema`) berhasil: 4 tabel (`keramine_transactions`, `keramine_mining_runs`, `keramine_rules`, `keramine_store_profiles`), RLS aktif, `get_advisors(security)` = `0` lints, REST keep-alive `HTTP 200 OK`.
- [x] **4.3** Seeding idempoten via [`data/seed_supabase.py`](../data/seed_supabase.py) selesai & diverifikasi lewat `execute_sql`: `15.986` transaksi (`15.840` positif + `146` retur), `237` profil toko, `8` *mining runs*, dan `784` katalog *rules*.

### Fase 5: Pembangunan Layanan Rule Engine API (FastAPI) — ✔ SELESAI
- [x] **5.1** Pembuatan struktur `api-engine/` ([`main.py`](../api-engine/main.py), [`mining_service.py`](../api-engine/mining_service.py), [`supabase_client.py`](../api-engine/supabase_client.py)).
- [x] **5.2** Implementasi & uji endpoint `GET /api/health` (`200 OK`, terhubung ke Supabase), `POST /api/mine` (`243,79 ms`, `104` rules `SERI`), `GET /api/recommend` (gabungan *Significant Rule* + *Volume Popularity Fallback*), dan `POST /api/simulate-transactions`.
- [x] **5.3** Pengujian kontrak numerik [`api-engine/tests/test_arm_contract.py`](../api-engine/tests/test_arm_contract.py) terhadap `data/clean/arm_fixture.json`: **10/10 aturan referensi LOLOS** dengan deviasi maksimum **$5,551 \times 10^{-17} \le 10^{-6}$**.

### Fase 6: Pembangunan Aplikasi Hibrida Laravel + Streamlit — ✔ SELESAI
- [x] **6.1** Pembangunan **Laravel Portal B2B** (`keramine-laravel :8000` dengan SQLite lokal untuk auth/riwayat + **Supabase REST API** & **FastAPI HTTP**): Login role, Katalog 237 Toko & 438 Produk, Wizard Rekomendasi Bundling, Riwayat & Export CSV/Print, serta tombol pemicu simulasi n8n & Reset Demo (`7/7` PHPUnit pass, `59` assertions).
- [x] **6.2** Pembangunan **Streamlit Analysis Lab** (`keramine-lab :8501`) dengan Design System Opsi C/B & Dwibahasa ID/EN: EDA Forensik, Multi-Level Rule Explorer, Interactive Plotly+NetworkX Graph, Kontingensi 2×2, SOTA Benchmark Lab, dan Monitor Pipeline n8n (`AppTest` 0 error).

### Fase 7: Implementasi Pipeline Auto-Realtime n8n — ✔ SELESAI
- [x] **7.1** Menjalankan `n8n v2.8.4` lokal (`npx --yes n8n start` pada port `5678`) serta mengimpor dan memublikasikan workflow `n8n/keramine_realtime_pipeline.json` (`ID: LPw6W9YXJjI1ktLV`).
- [x] **7.2** Menguji alur live end-to-end: `POST http://127.0.0.1:5678/webhook/keramine-ingest` $\rightarrow$ Ingest transaksi simulasi ke Supabase via FastAPI (`/api/simulate-transactions`, `+2` tx, re-mine `403,5 ms`) $\rightarrow$ Digest JSON `HTTP 200` $\rightarrow$ Pemulihan baseline via `/api/reset-demo` (`15.986` tx, `784` rules).

### Fase 8: Pengujian End-to-End, Finalisasi Laporan Live HTML & Demo UAS — ✔ SELESAI
- [x] **8.1** Pengujian integrasi penuh (`Colab artifacts <-> FastAPI :8001 <-> Supabase Cloud <-> n8n :5678 <-> Laravel :8000 & Streamlit :8501`).
- [x] **8.2** Pengambilan *screenshot* antarmuka (`assets/screenshots/*.png`) dan pengisian penuh Bab 4 & Bab 5 pada `Laporan_UAS_ADAMIN_KeraMine.html`.
- [x] **8.3** Pembuatan skrip peluncur satu-klik `start_keramine_demo.bat` (menjalankan 9Router, FastAPI, Laravel, Streamlit, dan n8n) beserta `README_DEMO_UAS.md`.

---

## 6. Acceptance Criteria (Kriteria Kelulusan Proyek)
1. Bukti forensik perbedaan keranjang `NOTA` (`0,01%` multi-item) vs `Toko + Bulan` (`92,53%` multi-item) terdokumentasi dengan metrik eksak di aplikasi dan laporan.
2. Tiga algoritma (**Apriori**, **FP-Growth**, **FP-Max**) dan metrik SOTA (**Zhang's Metric**, **Conviction**, **Leverage**, **Utility-Weighted `QTY`**, serta **Fisher's Exact $p$-value**) berjalan akurat dan tervalidasi kontrak `arm_fixture.json`.
3. Data transaksi dan katalog aturan tersimpan secara *live* di **Supabase PostgreSQL**.
4. Workflow **n8n** dapat dipicu secara langsung saat demo UAS (menyuntikkan transaksi baru $\rightarrow$ memicu perhitungan ulang *rules* di FastAPI $\rightarrow$ memperbarui Supabase & tampilan Streamlit dalam $< 5$ detik).
5. `Master_Dokumentasi_ADAMIN_KeraMine/` dan `Laporan_UAS_ADAMIN_KeraMine.html` sinkron 100% dengan angka hasil eksekusi nyata.

