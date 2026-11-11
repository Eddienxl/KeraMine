# Design System: Standar Desain Antarmuka KeraMine

**Versi:** 0.1.0 (Standar UI/UX KeraMine — Diselaraskan dengan Standar Modern SaaS Dashboard)  
**Status:** FINAL — 10 Nov 2026  
**Keputusan:** Gaya **Modern SaaS Dashboard (Opsi C)** dengan **toggle Light/Dark Mode** (mode gelap memakai palet *Tech Spec Dark* Opsi B) dan dukungan **Dwibahasa (Indonesia & Inggris)**.

---

## 1. Filosofi & Prinsip Desain
1. **Kejelasan Analitik B2B (*Clarity First*):** Angka metrik aturan asosiasi (*Support*, *Confidence*, *Lift*, *Zhang's Metric*, *Volume DUS*, dan *p-value*) harus mudah dibaca dengan angka sejajar (`tabular-nums`) dan dilengkapi indikator warna semantik.
2. **Transparansi Ilmiah (*No Black-Box*):** Setiap rekomendasi paket keramik memiliki panel **"Lihat Bukti Transaksi & Rumus"** yang menampilkan jumlah keranjang `Toko + Bulan`, tabel kontingensi $2\times 2$, serta hasil perhitungan bertahap.
3. **Alur Kerja Berorientasi Tindakan (*Actionable B2B Flow*):** Sales (`POF`) atau Manajer Gudang dapat memperoleh rekomendasi paket *restocking* hanya dalam 3 klik: **Pilih Toko / Sales $\rightarrow$ Pilih Level Hierarki Produk $\rightarrow$ Lihat Paket Bundling & Estimasi DUS**.
4. **Bebas *Emoji Slop*:** Gunakan lencana (*badge*) terstruktur dan ikon SVG bersih untuk menandai kualitas aturan (*Significant Rule*, *High-Volume Utility*, *Popularity Fallback*).

---

## 2. Palet Warna & Token Semantik

| Token | Light Mode (Opsi C — SaaS Clean) | Dark Mode (Opsi B — Tech Dark) | Kegunaan Semantik pada KeraMine |
|---|---|---|---|
| `--bg` | `#F8FAFC` | `#0F1117` | Latar belakang utama halaman |
| `--surface` | `#FFFFFF` | `#1A1D27` | Kartu metrik KPI, panel filter, kartu rekomendasi |
| `--border` | `#E2E8F0` | `#2A2F3E` | Garis batas tabel dan kartu |
| `--text` | `#0F172A` | `#E6E8EE` | Teks utama dan judul bagian |
| `--text-muted` | `#64748B` | `#94A3B8` | Label sekunder, keterangan rumus |
| `--primary` | `#4F46E5` (Indigo) | `#8B5CF6` (Violet) | Aksi utama, tombol *Mine Rules*, node *Antecedent* (Premis $A$) |
| `--accent` | `#10B981` (Emerald) | `#22D3EE` (Cyan) | Node *Consequent* (Kesimpulan $B$), badge *Lift > 1.5* & *Fisher Signifikan* |
| `--warning` | `#F59E0B` (Amber) | `#FBBF24` (Amber) | Badge *Fallback Popularitas*, peringatan support rendah |
| `--danger` | `#EF4444` (Red) | `#F87171` (Coral) | Penanda barang retur (`QTY <= 0`), asosiasi negatif (*Zhang < 0*) |

Aturan: Kontras teks terhadap latar belakang wajib memenuhi standar aksesibilitas **WCAG AA ($\ge 4.5:1$)**.

---

## 3. Tipografi

| Peran | Font Family | Ketebalan & Ukuran | Catatan |
|---|---|---|---|
| Heading Utama | `Inter`, system-ui, sans-serif | `600–700`, `22–28 px` | Judul dashboard & modul |
| Subheading / Judul Kartu | `Inter`, sans-serif | `600`, `16–18 px` | Nama aturan ($A \Rightarrow B$) |
| Body Text | `Inter`, sans-serif | `400–500`, `14–15 px` | Interpretasi otomatis bahasa manusia |
| Angka Metrik & Tabel | `JetBrains Mono` / `Inter` (`tabular-nums`) | `500`, `13–14 px` | Nilai *Support, Confidence, Lift, Zhang, QTY* |

---

## 4. Komponen Kunci Antarmuka (`keramine-app`)

### 4.1 Sidebar Navigasi & Kontrol Global
* **Identitas Aplikasi:** Logo SVG `KeraMine` + indikator status koneksi **Supabase Cloud** (`Connected` hijau) & **FastAPI + n8n Engine**.
* **Pemilih Bahasa & Tema:** Toggle bahasa (`ID` | `EN`) dan toggle mode visual (`Light` | `Dark`) yang menyelaraskan palet grafik Plotly dan PyVis Network.
* **Kontrol Parameter Global:**
  - Pilihan Unit Keranjang: `Toko + Bulan (Utama — 92.7% Multi-Item)` atau `Toko + Minggu (Taktis)`.
  - Pilihan Level Hierarki Item:
    1. `Level 2B — Seri Motif Keramik (SERI)` *(Default Rekomendasi)*
    2. `Level 2A — Merek + Ukuran (MERK_UKURAN)`
    3. `Level 1A — Merek (MERK)`
    4. `Level 1B — Dimensi Ukuran (UKURAN)`
    5. `Level 3 — Produk Spesifik (NAMABARA / SKU)`

### 4.2 Kartu KPI Ringkasan Eksekutif (Top Ribbon)
Menampilkan 5 kartu metrik di bagian atas:
1. **Total Transaksi Bersih:** `15.840 baris` (+ `160 retur` terpisah).
2. **Total Keranjang Aktif:** `3.949 Toko-Bulan` (`237 Toko`, `17 Bulan`).
3. **Aturan Ditemukan:** Jumlah aturan aktif pada filter saat ini (beserta rasio yang lolos uji signifikansi Fisher-BH).
4. **Top Rule Lift & Zhang:** Nilai *Lift* dan *Zhang's Metric* tertinggi saat ini.
5. **Status Pipeline n8n:** Waktu sinkronisasi terakhir (`last_run_at`) dari tabel `keramine_mining_runs` di Supabase.

### 4.3 Kartu Paket Rekomendasi Bundling (*Smart Bundling Card*)
Setiap rekomendasi menampilkan:
* **Visualisasi Aturan:** Chip Premis `[Jika Toko Memesan: SERI A]` $\longrightarrow$ Chip Kesimpulan `[Tawarkan Juga: SERI B]`.
* **Empat Badge Metrik Utama:**
  - `Confidence: XX.X%`
  - `Lift: X.XXx`
  - `Zhang: +0.XX`
  - `Avg Volume: XX DUS/bulan`
* **Lencana Validasi Statistik:**
  - Hijau (`✔ Signifikan p < 0.05`) bila lolos uji Fisher's Exact, atau
  - Amber (`ℹ Berdasarkan Frekuensi / Popularitas`) bila digunakan sebagai *fallback* pada segmen dengan sampel kecil.
* **Kotak Interpretasi Dinamis:** Kalimat penjelasan otomatis dalam bahasa bisnis (ID/EN) untuk memandu Sales (`POF`).

### 4.4 Visualisasi Grafik Interaktif
1. **Interactive Rule Network Graph (PyVis / Plotly):** Node merepresentasikan item (ukuran node = total volume `QTY` dus), panah berarah merepresentasikan aturan $A \Rightarrow B$ (ketebalan & warna panah = *Zhang's Metric* / *Lift*). Dibatasi pada Top-$N$ aturan (default 25) agar tidak menjadi *hairball*.
2. **Multi-Metric Scatter Plot (Plotly):** Sumbu X = *Support*, Sumbu Y = *Confidence*, ukuran titik = *Volume DUS (`QTY`)*, warna = *Zhang's Metric* atau *Lift*.
3. **SOTA Algorithm Benchmark Chart:** Grafik batang waktu eksekusi (milidetik) dan jumlah *frequent itemsets* antara **Apriori**, **FP-Growth**, dan **FP-Max**.

### 4.5 Panel Transparansi Matematis (Accordion)
Saat pengguna mengklik **"Lihat Perhitungan Matematis"** pada sebuah aturan $A \Rightarrow B$, sistem membuka rincian:
* Tabel kontingensi $2\times 2$ ($n_{AB}, n_{A\bar{B}}, n_{\bar{A}B}, n_{\bar{A}\bar{B}}$ dari total $N$ keranjang).
* Substitusi angka ke rumus *Support*, *Confidence*, *Lift*, *Leverage*, *Conviction*, *Zhang's Metric*, dan *Fisher's Exact p-value*.

