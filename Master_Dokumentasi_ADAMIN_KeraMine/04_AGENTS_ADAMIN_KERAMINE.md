# AI Agents Development Guide: Aturan & Standar Rekayasa KeraMine

**Proyek:** KeraMine (Sistem Rekomendasi B2B Restocking & Bundling Produk Keramik — Multi-Level SOTA Association Rules & Pipeline n8n)  
**Versi:** 0.1.0 (10 Nov 2026 — Dilengkapi Protokol Wajib `-9router` & Mekanisme Google Colab)  
**Sasaran:** AI Coding Assistant (Antigravity, Cursor, dll.) dan pengembang proyek.

---

## 1. Maksud & Tujuan
Dokumen ini adalah kontrak aturan bagi agen AI saat membaca, memodifikasi, atau membangun komponen pada repositori `ADAMIN` (`notebooks/`, `data/clean/`, `api-engine/`, `keramine-app/`, `n8n/`, dan `Laporan_UAS_ADAMIN_KeraMine.html`). **Wajib membaca dokumen 01, 03, 05, 06, 07, dan 08 sebelum mengeksekusi perubahan kode.**

---

## 2. Prinsip Inti Rekayasa Data Mining

### 2.1 Integritas Definisi Keranjang (*Basket*) & Hierarki Item
* **DILARANG KERAS** menggunakan kolom `NOTA` sebagai unit keranjang (*basket*) utama karena hasil audit membuktikan 15.985 dari 16.000 baris memiliki `NOTA` unik (rata-rata $1,00$ item/nota, $0\%$ multi-item).
* Unit keranjang yang sah adalah:
  1. **`store_month` (`NAMA` + `YEAR_MONTH`):** Unit keranjang utama ($3.949$ keranjang aktif, $92,7\%$ multi-item).
  2. **`store_week` (`NAMA` + `YEAR_WEEK`):** Unit keranjang taktis mingguan ($10.522$ keranjang, $38,7\%$ multi-item).
* **DILARANG** mencampurkan baris retur/batal (`QTY <= 0`, sebanyak 160 baris) ke dalam keranjang pembelian positif. Baris `QTY <= 0` wajib dipisahkan ke `data/clean/returns_log.csv` dan diberi flag `is_return = true` di Supabase.

### 2.2 Integritas Rumus Matematis & SOTA Association Rules
* Setiap aturan asosiasi wajib menyertakan metrik klasik (*Support*, *Confidence*, *Lift*) **dan** metrik SOTA (*Leverage*, *Conviction*, *Kulczynski*, **Zhang's Metric**, *Utility-Weighted Score* berbasis `QTY`, serta **Fisher's Exact Test $p$-value** + **Benjamini-Hochberg $q$-value**).
* Engine di `api-engine/` dan `keramine-app/` wajib menghasilkan angka yang **identik (toleransi $\le 10^{-6}$)** dengan kontrak uji `data/clean/arm_fixture.json` yang dihasilkan oleh Colab Notebook 02.
* **DILARANG** membulatkan angka di tengah perhitungan; pembulatan hanya dilakukan pada lapisan tampilan UI/HTML.

### 2.3 Kejujuran Ilmiah & Anti-Silent Fallback
* Berdasarkan audit forensik dataset (lihat Dokumen 05), distribusi merek (`MERK`) dan penugasan ke produk memiliki sifat mendekati seragam, sehingga pada level `MERK` nilai *Lift* maksimum berada di sekitar $1,20$, sedangkan pada level `SERI` terdapat kombinasi dengan *Lift* $> 2,0$ pada segmen spesifik.
* **DILARANG** memalsukan angka *Support*, *Confidence*, atau *Lift*. Sajikan hasil uji statistik (**Fisher's Exact Test**) secara apa adanya.
* Pada fitur rekomendasi toko, apabila filter pengguna atau riwayat toko tidak menemukan aturan asosiasi yang memenuhi ambang batas, **DILARANG** menampilkan rekomendasi acak tanpa keterangan; tampilkan *badge* yang jelas apakah rekomendasi berasal dari:
  1. `Significant Association Rule (Fisher p < 0.05)`
  2. `Frequent Pattern Rule (Exploratory)`
  3. `Store/Brand Popularity Fallback (Top Volume DUS)`

---

## 3. Aturan Alur Kerja Python via Google Colab (Wajib Dipatuhi)

Sesuai instruksi pengguna, seluruh pemrosesan data berat dan eksperimen pemodelan Python dikerjakan menggunakan format **Jupyter Notebook (`.ipynb`) yang dijalankan oleh pengguna di Google Colab** (mekanisme yang sama persis dengan proyek SPK):

1. Agen AI membuat/memperbarui file `.ipynb` secara lengkap, mandiri (*self-contained*), bebas *DeprecationWarning* (`StderrFilter` untuk `jupyter_client` di Python 3.13 Colab), dan tahan dijalankan ulang dari awal (*Restart & Run All*) di folder `notebooks/`.
2. Sel terakhir pada setiap notebook wajib membungkus seluruh file keluaran bersih (CSV/JSON/MD) ke dalam satu file arsip `.zip` dan memicu `google.colab.files.download(...)` otomatis.
3. Pengguna mengunggah `.ipynb` ke Google Colab, menjalankan *Run All*, lalu meletakkan file `.zip` hasil unduhan ke dalam folder proyek.
4. Agen AI mengekstrak isi `.zip` ke folder `data/clean/`, memverifikasi integritasnya, lalu memperbarui `01_PRD`, `05_DATASET_DAN_METODE`, dan `Laporan_UAS_ADAMIN_KeraMine.html` dengan angka-angka nyata dari artefak tersebut.

---

## 4. Aturan Lingkungan, Supabase MCP & Protokol `-9router`

* **Sistem Operasi:** Windows. Perintah `python` adalah stub Microsoft Store; untuk eksekusi skrip ringan lokal gunakan `py`, namun untuk pemrosesan data dan pemodelan gunakan **Google Colab Notebook**.
* **Keamanan Kredensial:** Kunci API (`SUPABASE_SERVICE_ROLE_KEY`, token bot, dll.) hanya boleh berada di `.env` (masuk `.gitignore`) atau konfigurasi global MCP; **DILARANG** menulis kredensial rahasia di dalam dokumen Markdown, kode yang di-*commit*, atau mengirimkannya ke model eksternal.

### 4.1 Protokol Trigger Eksplisit `-9router` (Wajib Dipatuhi Agen)
* Apabila pesan/instruksi dari pengguna menyertakan kata kunci **`-9router`**, agen utama (Antigravity) **WAJIB** memanggil tool MCP `router9` (`router9_second_opinion` atau `router9_ask_model` melalui server lokal `http://127.0.0.1:20128/v1` dengan model `smart-free` atau `groq/openai/gpt-oss-120b`).
* Apabila server `9router` belum aktif saat `-9router` diminta, agen utama wajib menyalakannya terlebih dahulu di background (`9router --no-browser --skip-update -l`).
* Fungsi pemanggilan ini berlaku **secara universal** untuk diskusi arsitektur, *code review*, audit rumus matematika/statistik Data Mining, desain workflow n8n, skema Supabase, maupun penulisan laporan.
* Agen utama menampilkan ringkasan masukan/opini kedua dari 9Router kepada pengguna dan mensintesiskannya dengan analisis agen utama.

---

## 5. Definition of Done (Per Fase)
Sebuah fase baru boleh ditandai `✔ SELESAI` pada `01_PRD_GENERAL_ADAMIN_KERAMINE.md` apabila:
1. Artefak kode/data pada fase tersebut sudah terwujud dan teruji tanpa error.
2. Seluruh angka metrik telah disinkronkan ke `05_DATASET_DAN_METODE_ADAMIN_KERAMINE.md`.
3. Bab/Subbab terkait pada [`Laporan_UAS_ADAMIN_KeraMine.html`](../Laporan_UAS_ADAMIN_KeraMine.html) telah diperbarui secara langsung (*live documentation*).

