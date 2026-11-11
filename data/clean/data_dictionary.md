# Data Dictionary: KeraMine Clean Artifacts

Dihasilkan oleh `notebooks/01_data_cleaning_basket_engineering.ipynb`.
- **Total baris aktif terbaca (`datasetarule.xlsx`):** 15,986 baris.
- **Transaksi positif (`transactions_clean.csv`):** 15,840 baris (`QTY > 0`).
- **Transaksi retur/nol (`returns_log.csv`):** 146 baris (`QTY <= 0`).
- **Rentang tanggal:** 2022-08-01 s.d. 2023-12-30.

## Kolom pada `transactions_clean.csv` & `returns_log.csv`
| Kolom | Tipe | Deskripsi |
|---|---|---|
| `source_row_hash` | string | Hash SHA-256 (32 hex) unik per baris Excel untuk idempotensi database |
| `excel_row` | int | Nomor baris sumber pada `datasetarule.xlsx` |
| `nota` | string | Nomor faktur pengiriman (non-unik: terdeteksi 1 tabrakan `JL*7211.3746`) |
| `tgl` | date | Tanggal ISO `YYYY-MM-DD` |
| `year_month` | string | Kunci periode bulanan `YYYY-MM` (unit basket utama bersama `nama_toko`) |
| `year_week` | string | Kunci periode mingguan ISO `YYYY-Www` |
| `nama_toko` | string | Nama toko bangunan pelanggan B2B (237 toko) |
| `kode_toko` | int | Kode numerik toko |
| `namabara` | string | Nama SKU produk lengkap (438 SKU) |
| `kodebara` | int | Kode numerik barang |
| `merk_raw` | string | Merek mentah sebelum normalisasi (22 label) |
| `merk_clean` | string | Merek bersih (21 merek setelah normalisasi `PISSCASO` -> `PICASSO`) |
| `ukuran` | string | Dimensi ukuran keramik hasil ekstraksi regex (`40X40`, `25X40`, ..., `NON_STD`) |
| `seri` | string | Nama seri motif utama (220 seri) |
| `merk_ukuran` | string | Gabungan `merk_clean + '_' + ukuran` |
| `qty` | int | Jumlah kardus keramik (`DUS`) |
| `satuan` | string | Satuan kemasan (`DUS`) |
| `pof` | string | Nama tenaga penjual (`DIAN`, `ROBBY`, `JONI`, `BARI`) |
| `is_return` | bool | `True` jika `qty <= 0`, `False` jika `qty > 0` |
| `source_batch` | string | Penanda asal batch data (`historical_xlsx` / `n8n_live`) |
