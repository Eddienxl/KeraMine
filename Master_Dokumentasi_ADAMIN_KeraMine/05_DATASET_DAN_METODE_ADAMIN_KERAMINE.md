# Dataset & Metodologi SOTA Association Rule Mining: KeraMine

**Versi:** 0.1.0 (Hasil Profiling Forensik Awal `datasetarule.xlsx` — 10 Nov 2026)

---

## 1. Profil Umum Dataset (`datasetarule.xlsx`)

* **Lokasi Berkas:** [`d:\TUGAS\SEMESTER 7\ADAMIN\datasetarule.xlsx`](../datasetarule.xlsx)
* **Domain Bisnis:** Distribusi Bahan Bangunan / Keramik Lantai & Dinding B2B (*Business-to-Business* dari distributor ke toko-toko bangunan).
* **Ukuran Mentah:** **16.000 baris transaksi** $\times$ **10 kolom** (`Sheet1`, baris 1 adalah header, baris 2–16.001 adalah data).
* **Rentang Waktu Transaksi (`TGL`):** **1 Agustus 2022** (`44774`) s.d. **30 Desember 2023** (`45290`) — mencakup **17 bulan** kalender penuh.

### 1.1 Kamus Atribut Mentah
| Kolom | Tipe Asli | Jumlah Unik | Contoh Nilai | Deskripsi |
|---|---|---|---|---|
| `NAMA` | Teks | **237** | `KUNCI SUKSES, TOKO`, `RIZKI MANDIRI, TOKO` | Nama toko bahan bangunan pelanggan (B2B) |
| `NAMABARA` | Teks | **438** | `ESMERALDA STONE BROWNISH 40X40` | Nama produk keramik lengkap (Seri + Warna/Tipe + Ukuran) |
| `TGL` | Numerik (Serial Excel) | 516 hari | `44774` ($\rightarrow$ `2022-08-01`) | Tanggal pencatatan faktur pengiriman |
| `NOTA` | Teks | **15.985** | `JL*6690.9238`, `JL*0046.4111` | Nomor nota/faktur per baris barang |
| `QTY` | Numerik | Bervariasi | `29`, `91`, `-5` | Jumlah kardus keramik yang dikirim (atau diretur bila $\le 0$) |
| `SATUAN` | Teks | 1 (`DUS`) | `DUS` | Satuan kemasan produk (15.986 baris terisi `DUS`) |
| `KODE` | Numerik | 237 | `801`, `923` | Kode identifikasi numerik untuk toko pelanggan (`NAMA`) |
| `KODEBARA` | Numerik | 438 | `4115`, `5530` | Kode identifikasi numerik untuk produk (`NAMABARA`) |
| `POF` | Teks | **4** | `DIAN`, `ROBBY`, `JONI`, `BARI` | Tenaga penjual (*Point of Field / Salesperson*) yang menangani pesanan |
| `MERK` | Teks | **22** (+1 kosong) | `PICASSO`, `FORTUNER`, `VALENSIA`, `MCC` | Merek keramik |

---

## 2. Temuan Forensik Kualitas Data (Wajib Dipahami Seluruh Agen & Penguji)

Hasil audit mendalam terhadap 16.000 baris di `datasetarule.xlsx` mengungkap **5 temuan krusial** yang menjadi kontribusi analisis ilmiah utama pada Laporan UAS:

| # | Temuan Forensik | Bukti Statistik pada Dataset | Penanganan pada Pipeline KeraMine |
|---|---|---|---|
| **1** | **Jebakan Kolom `NOTA` & Tabrakan Duplikat (`Single-Item Invoice Trap`)** | Dari 15.986 baris transaksi aktif (+ 14 baris kosong *trailing*), terdapat **15.985 `NOTA` unik**. Hanya **1 nomor `NOTA` (`JL*7211.3746`)** yang muncul dua kali, dan itu pun pada dua toko serta tanggal yang berbeda (*random invoice collision*). Rata-rata item per nota tetap **1,00**. | `NOTA` bukan keranjang belanja dan tidak dapat dijadikan *primary key* unik database (`source_row_hash` SHA-256 digunakan sebagai PK idempoten). Keranjang (*basket*) wajib didefinisikan ulang sebagai **`Toko + Bulan`** (`NAMA` + `YYYY-MM`). |
| **2** | **Baris Kosong *Trailing* & Transaksi Retur/Nol (`QTY <= 0`)** | Ditemukan **14 baris kosong total** di bagian bawah *sheet* (baris 15.988–16.001) dan **146 baris transaksi dengan `QTY = 0`** (`0` baris `QTY < 0`). | 14 baris kosong dibuang; 146 baris `QTY <= 0` dipisahkan ke `returns_log.csv` (`is_return = true`), menyisakan **15.840 baris transaksi pembelian positif** pada `transactions_clean.csv`. |
| **3** | **Inkonsistensi Ejaan Merek (`PICASSO` vs `PISSCASO`)** | Terdapat 22 label string `MERK` pada baris aktif, di mana `PICASSO` (745 baris) dan `PISSCASO` (725 baris) merujuk pada merek yang sama (sedangkan `EFL` [703] vs `EIFFEL` [702] dicatat dalam log audit). | Di Colab Notebook 01 dibuat kolom `merk_clean` yang menormalisasi typo `PISSCASO` $\rightarrow$ `PICASSO` (menjadi 21 merek bersih dengan tetap menyimpan `merk_raw` untuk audit). |
| **4** | **Ekstraksi Dimensi Ukuran & Seri Motif dari `NAMABARA`** | Dari 438 produk unik, **436 produk (99,5%)** memuat pola dimensi ukuran `NNXNN` (`40X40`: 139 SKU, `25X40`: 112 SKU, `50X50`: 101 SKU, `25X25`: 60 SKU, `25X50`: 9 SKU, `60X60`: 8 SKU, `20X40`: 7 SKU; hanya 2 SKU tanpa ukuran eksplisit). | Diekstrak secara terprogram dengan regex `(\d{2,3})\s*X\s*(\d{2,3})` menjadi kolom `ukuran` dan kata awal menjadi `seri` (220 seri motif unik). |
| **5** | **Karakteristik Distribusi Atribut & Pentingnya Uji Signifikansi Statistik** | Distribusi transaksi per `POF` (`DIAN`: 4.064, `ROBBY`: 4.005, `JONI`: 3.970, `BARI`: 3.947) dan per `MERK` (~730–760 baris per merek) sangat merata, serta satu `NAMABARA` dapat tercatat di berbagai `MERK`. | Menunjukkan bahwa pada level makro (`MERK`), frekuensi kemunculan bersifat mendekati seragam (*Lift* $\approx 0,78\text{–}1,20$). Oleh karena itu, penyaringan dengan **Fisher's Exact Test + Benjamini-Hochberg** dan analisis pada level **`SERI` / `MERK_UKURAN`** menjadi sangat penting untuk memisahkan paket *niche* yang benar-benar signifikan (*Lift* $1,40\text{–}8,44$) dari kebetulan statistik. |

---

## 3. Perbandingan Empiris Definisi Keranjang (*Basket Granularity*)

Tabel berikut merangkum hasil terverifikasi Google Colab (`cleaning_audit_summary.json`) pada **15.840 transaksi pembelian positif**, membuktikan mengapa **`Toko + Bulan`** dipilih sebagai unit keranjang utama pada `KeraMine`:

| Definisi Keranjang (*Basket Key*) | Jumlah Total Basket | Basket Multi-Item ($\ge 2$ item) | Persentase Multi-Item | Rata-rata Item / Basket | Maksimum Item / Basket | Kelayakan untuk Association Rule Mining |
|---|---|---|---|---|---|---|
| `NOTA` (Faktur Baris) | 15.839 | 1 | **0,01%** | 1,00 | 2 | **Tidak Layak** (Tabrakan acak 1 nota `JL*7211.3746`) |
| `Toko + Hari` (`NAMA` + `TGL`) | 14.874 | 927 | **6,23%** | 1,06 | 4 | **Sangat Jarang (*Sparse*)** — hanya untuk deteksi pesanan harian ganda |
| `Toko + Minggu` (`NAMA` + `YEAR_WEEK`) | 10.475 | 4.032 | **38,49%** | 1,51 | 6 | **Layak sebagai Opsi Taktis** (Pola pemesanan mingguan) |
| **`Toko + Bulan` (`NAMA` + `YEAR_MONTH`)** | **3.949** | **3.654** | **92,53%** | **3,99** | **12** | **SANGAT IDEAL (Unit Utama Rekomendasi Restocking)** |
| `Toko Sepanjang Periode` (`NAMA`) | 237 | 237 | **100,00%** | 62,03 | 83 | Terlalu lebar (menggabungkan 17 bulan menjadi 1 keranjang) |

---

## 4. Hierarki Multi-Level Item & Karakteristik Asosiasi Awal

Berdasarkan hasil pengujian pada **3.949 keranjang positif `Toko + Bulan`** (total volume **794.064 DUS**, rata-rata **50,13 DUS/baris**, median **50,0 DUS**), berikut adalah karakteristik setiap level item:

| Kode Level | Nama Level | Jumlah Item Unik | Max Single Support | Jumlah Pasangan ($n_{AB} \ge 5$) | Rentang *Lift* Pasangan | Peran dalam Aplikasi KeraMine |
|---|---|---|---|---|---|---|
| **L1-A** | `MERK` (Merek Bersih) | 21 (22 mentah) | ~29% (`PICASSO` gabungan) | 231 | $0,78 - 1,20$ | Analisis kombinasi merek & alokasi target sales (`POF`) |
| **L1-B** | `UKURAN` (Dimensi) | 8 (`NON_STD`: 76 baris) | **73,36%** (`40X40`) | 27 | $0,75 - 1,32$ | Perencanaan komposisi kapasitas muatan kardus (`40X40` = 255.854 DUS, `25X40` = 199.804 DUS) |
| **L2-A** | `MERK_UKURAN` | **168** | ~11,0% | >300 | $0,65 - 2,40$ | Rekomendasi paket *bundling* merek + ukuran lantai/dinding |
| **L2-B** | **`SERI` (Seri Motif)** | **220** | **7,47%** (`NEO`) | **949** | **$0,47 - 8,44$** | **Level Utama Rekomendasi Bundling Produk** (contoh: `DAKOTA` + `NEO`, `DOT` + `NEO`, `DAKOTA` + `GALANOS`) |
| **L3** | `NAMABARA` (Full SKU) | 438 | 1,39% (`ELWOOD GREY 40X40`) | 2 ($n \ge 5$) | $10,07 - 10,97$ | Rekomendasi SKU mikro sangat spesifik (*niche bundle*) |

---

## 5. Hasil Eksperimen & Validasi SOTA (Colab Notebook 02 — Terverifikasi)

Berdasarkan eksekusi [02_sota_association_rules_validation.ipynb](../notebooks/02_sota_association_rules_validation.ipynb) di Google Colab (`adamin_colab_02_outputs.zip`):

### 5.1 Benchmark Algoritma Lintas Level (`Toko + Bulan`, 3.949 Keranjang)
| Level Item | Item Unik | Min Support | Apriori (ms) | FP-Growth (ms) | FP-Max (ms) | Frequent Itemsets (Apriori=FP-Growth) | Maximal Itemsets (FP-Max) | Pemangkasan Redundansi |
|---|---|---|---|---|---|---|---|---|
| `MERK` | 21 | 0,0200 | **45,47** | 5.108,98 | 2.685,72 | 231 | 210 | 9,09% |
| `UKURAN` | 8 | 0,0200 | **4,12** | 417,47 | 125,77 | 41 | 9 | **78,05%** |
| `MERK_UKURAN` | 168 | 0,0020 | **93,27** | 20.725,28 | 18.999,11 | 1.603 | 1.519 | 5,24% |
| `SERI` | 220 | 0,0015 | **41,40** | 8.997,88 | 6.741,14 | 746 | 602 | **19,30%** |
| `NAMABARA` | 438 | 0,0010 | **56,11** | 1.055,25 | 1.122,43 | 474 | 409 | 13,71% |

> **Temuan Rekayasa Penting:** Pada dataset berkarakter keranjang pendek (`rata-rata 3,99 item`, `max_len = 3`) di pustaka `mlxtend`, implementasi **Apriori berbasis operasi matriks NumPy** berjalan **100×–200× lebih cepat** (`4,1–93,3 ms`) dibandingkan konstruksi FP-Tree rekursif Python pada `FP-Growth`, sementara **FP-Max** memangkas itemset redundan hingga **78,05%**. Temuan ini menjadi dasar pemilihan *engine* `< 100 ms` untuk endpoint real-time **FastAPI `/api/mine`**.

### 5.2 Ringkasan 784 Rules & Penyaringan Signifikansi Fisher-BH
| Basket | Level Item | Total Rules | Fisher ($p < 0,05$) | FDR-BH ($q < 0,10$) | Max Lift | Max Zhang |
|---|---|---|---|---|---|---|
| `store_month` | `MERK` | 349 | 14 | **0** | 1,197 | 0,198 |
| `store_month` | `UKURAN` | 84 | 2 | **0** | 1,109 | 0,106 |
| `store_month` | `MERK_UKURAN` | 127 | 34 | **27** | 2,790 | 0,660 |
| `store_month` | **`SERI`** | **104** | **61** | **72** | **5,683** | **0,838** |
| `store_month` | `NAMABARA` | 39 | 39 | **39** | 16,118 | 0,946 |
| `store_week` | `MERK` | 57 | 0 | 0 | 0,970 | -0,032 |
| `store_week` | `MERK_UKURAN` | 6 | 2 | 1 | 2,951 | 0,667 |
| `store_week` | `SERI` | 18 | 14 | 15 | 16,627 | 0,946 |

### 5.3 Evaluasi Temporal *Out-of-Time* (`Train <= 2023-09` vs `Test > 2023-09`)
| Level Item | Train Rules | Coverage ARM | Hit-Rate@5 ARM Murni | Hit-Rate@5 Popularitas | **Hit-Rate@5 Hybrid** | Precision@5 Hybrid |
|---|---|---|---|---|---|---|
| `MERK` | 347 | 100,00% | 53,63% | 63,25% | **66,25%** | **19,94%** |
| `MERK_UKURAN` | 154 | 80,25% | 11,76% | 30,09% | **31,50%** | **7,12%** |
| `SERI` | 143 | 44,03% | 3,14% | 19,18% | 18,71% | 3,99% |

* **Kontrak Matematis (`arm_fixture.json`):** Memuat Top-10 aturan acuan pada `store_month` + `SERI` (juara 1 oleh *p-value* Fisher `7,47e-05`: `DIEGO -> GRANIT`, `Lift 3,96`, `Zhang 0,760`, rata-rata `96,09 DUS`; juara 1 *Utility Score* `0,310`: `CUBIC -> MIRANDA`, `Avg 111,67 DUS`).

