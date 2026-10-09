# BUKU PANDUAN PENGGUNAAN (MANUAL BOOK)
# SISTEM KULASHOP (KULASHOP)

Selamat datang di sistem **Kulashop**. Aplikasi ini dirancang khusus untuk mengelola operasional multi-channel marketplace (Shopee, TikTok Shop, Tokopedia), mulai dari pencatatan pesanan, pengelolaan pelanggan, pencocokan pencairan dana (rekonsiliasi keuangan), hingga standarisasi Master Produk internal.

---

## DAFTAR ISI
1. [Prinsip Dasar & Arsitektur Sistem](#1-prinsip-dasar--arsitektur-sistem)
2. [Hak Akses Pengguna (User Roles)](#2-hak-akses-pengguna-user-roles)
3. [Panduan Master Data](#3-panduan-master-data)
   - [3.1 Master Marketplace & Akun Toko](#31-master-marketplace--akun-toko)
   - [3.2 Master Pelanggan (Customers)](#32-master-pelanggan-customers)
   - [3.3 Master Produk Internal](#33-master-produk-internal)
4. [Panduan Pemetaan SKU (Product Mapping)](#4-panduan-pemetaan-sku-product-mapping)
   - [4.1 Konsep Dua Identitas Produk](#41-konsep-dua-identitas-produk)
   - [4.2 Pemetaan Manual](#42-pemetaan-manual)
   - [4.3 Fitur Cepat: Sinkronisasi Otomatis dari Transaksi](#43-fitur-cepat-sinkronisasi-otomatis-dari-transaksi)
   - [4.4 Perlindungan HPP Historis (Snapshot Immutability)](#44-perlindungan-hpp-historis-snapshot-immutability)
5. [Panduan Impor Data Marketplace](#5-panduan-impor-data-marketplace)
   - [5.1 Impor Data Pesanan (Shopee Order)](#51-impor-data-pesanan-shopee-order)
   - [5.2 Impor Data Penghasilan (Shopee Income)](#52-impor-data-penghasilan-shopee-income)
   - [5.3 Penanganan Gagal Impor & Validasi](#53-penanganan-gagal-impor--validasi)
6. [Rekonsiliasi Keuangan & Pencairan Dana](#6-rekonsiliasi-keuangan--pencairan-dana)
   - [6.1 Analisis Pencairan (Settlement)](#61-analisis-pencairan-settlement)
7. [Laporan & Dashboard Pemilik](#7-laporan--dashboard-pemilik)
8. [Sistem Lisensi Modular (Developer Panel)](#8-sistem-lisensi-modular-developer-panel)
9. [Tanya Jawab & Kendala Umum (FAQ)](#9-tanya-jawab--kendala-umum-faq)

---

## 1. PRINSIP DASAR & ARSITEKTUR SISTEM
Kulashop memisahkan antara **Data Eksternal Marketplace** dengan **Data Internal Perusahaan**:
- **Marketplace**: Sering berganti nama produk untuk optimasi SEO, variasi judul promosi, serta penamaan SKU toko yang acak.
- **ERP Internal**: Membutuhkan data rapi, baku, dan tetap untuk menghitung stok, HPP modal, dan margin keuntungan yang nyata.

Sistem bertindak sebagai jembatan yang menghubungkan kedua dunia ini tanpa mengubah data asli dari marketplace.

---

## 2. HAK AKSES PENGGUNA (USER ROLES)
Sistem memiliki 3 tingkatan hak akses:
1. **Owner (Pemilik Toko)**:
   - Memiliki akses penuh ke seluruh modul, termasuk data laba kotor, margin, dasbor keuangan, dan laporan performa bisnis.
2. **Admin**:
   - Memiliki akses operasional penuh: mengelola Master Marketplace, Akun Toko, Master Produk, melakukan Impor Data Excel/CSV, dan memetakan SKU.
3. **Staff / Viewer**:
   - Hanya memiliki akses baca (*read-only*) untuk melihat data pesanan dan pelanggan tanpa izin mengubah master konfigurasi maupun menghapus pemetaan produk.

---

## 3. PANDUAN MASTER DATA

### 3.1 Master Marketplace & Akun Toko
- **Marketplace**: Daftar kanal penjualan (Shopee, TikTok Shop, Tokopedia).
- **Toko Marketplace**: Setiap toko fisik yang Anda miliki di Shopee didaftarkan di menu ini (misalnya: *Toko Utama*, *Toko Cabang Jakarta*, *Toko Reseller*).
- Setiap toko memiliki parameter **Pajak (%)** yang akan digunakan sebagai perhitungan potongan pajak otomatis jika diaktifkan.

### 3.2 Master Pelanggan (Customers)
- **Auto-Generated**: Data konsumen secara otomatis diekstrak dan diperbarui setiap kali Anda mengimpor file Pesanan Shopee.
- Menampilkan ID unik pelanggan, Nama Pelanggan, Username Shopee, No. Telepon, Alamat, serta Wilayah Kabupaten/Provinsi.
- *Catatan Privasi Shopee*: Nomor telepon dan nama yang berformat sensor bintang (`M******i` / `******10`) berasal langsung dari proteksi privasi file ekspor Shopee.

### 3.3 Master Produk Internal
- **SKU Internal**: Kode identitas unik barang di gudang Anda (misal: `TSHIRT-BLK-L`, `MUG-KERAMIK`).
- **Nama & Varian Produk**: Deskripsi baku barang dagangan.
- **HPP (Harga Pokok Penjualan)**: Biaya modal per unit barang.
- **Harga Jual Standar**: Harga patokan ritel non-promo.
- **Status (Active / Inactive)**:
  - *Active*: Produk aktif diperjualbelikan dan muncul sebagai opsi mapping baru.
  - *Inactive*: Produk yang sudah diskontinu / tidak diproduksi lagi. Produk nonaktif disembunyikan dari pilihan mapping baru, **tetapi transaksi historis lama tetap terjaga utuh dan tidak akan hilang**.

---

## 4. PANDUAN PEMETAAN SKU (PRODUCT MAPPING)

### 4.1 Konsep Dua Identitas Produk
Satu produk gudang internal bisa dijual di Shopee dengan berbagai nama SKU berbeda pada berbagai toko.
> **Contoh**:
> - SKU Internal: `KAOS-POLOS-HITAM`
> - SKU Shopee di Toko A: `TSHIRT-HITAM-EDISI1`
> - SKU Shopee di Toko B: `PROMO-KAOS-HITAM-XL`
Melalui modul mapping, sistem akan tahu bahwa kedua SKU Shopee tersebut merujuk pada 1 barang fisik yang sama.

### 4.2 Pemetaan Manual
1. Masuk ke menu **Master Data > Produk**.
2. Klik tombol opsi (`...`) di kolom Aksi pada produk yang dituju, lalu pilih **Mapping Marketplace**.
3. Pilih **Akun Toko Shopee** dan masukkan **Marketplace SKU**.
4. Klik **Simpan Mapping**.
5. Sistem akan memvalidasi agar tidak ada SKU Shopee kembar di akun toko yang sama (*Composite Unique Constraint*).

### 4.3 Fitur Cepat: Sinkronisasi Otomatis dari Transaksi
Jika Anda baru pertama kali mengimpor ratusan transaksi Shopee dan belum sempat mendaftarkan produk satu per satu:
1. Masuk ke menu **Master Data > Produk**.
2. Klik tombol kuning **"SKU Belum Mapping"**.
3. Di halaman tersebut, Anda akan melihat daftar semua barang yang sudah laku di Shopee namun belum terhubung ke ERP.
4. Klik tombol **"Sinkronisasi Otomatis"**.
5. Konfirmasi pada jendela pop-up. Sistem akan secara cerdas:
   - Membuatkan Master Produk baru secara otomatis berdasarkan nama dan harga dari transaksi.
   - Langsung menyambungkan relasi mapping ke toko bersangkutan.
   - Memperbaiki data pesanan lama sehingga langsung memiliki relasi produk.

### 4.4 Perlindungan HPP Historis (Snapshot Immutability)
Sistem memiliki garansi integritas data keuangan:
- Setiap kali pesanan diimpor, nilai HPP saat transaksi terjadi dikunci pada kolom `order_items.hpp_snapshot`.
- **Jika sewaktu-waktu HPP pada Master Produk diubah** (misal harga bahan baku naik), sistem **TIDAK AKAN** mengubah catatan HPP pada transaksi masa lalu. Laporan profit historis Anda tetap akurat 100%.

---

## 5. PANDUAN IMPOR DATA MARKETPLACE

> **PENTING: STRUKTUR ORDER-FIRST**
> Sistem ERP Kulashop dirancang dengan logika **Order-First**. Artinya, seluruh data keuangan, pelanggan, dan HPP akan disematkan ke dalam **Pesanan (Order)** sebagai pondasinya. Anda diwajibkan untuk selalu mengunggah file **Order** terlebih dahulu sebelum mengunggah file **Income (Penghasilan)**.

### 5.1 Impor Data Pesanan (Shopee Order)
1. Masuk ke Shopee Seller Centre > **Pesanan Saya > Pengiriman Perlu Diproses / Selesai**.
2. Klik tombol **Export Data** (Pilih rentang tanggal yang diinginkan).
3. Di Kulashop, buka menu **Import Data > Import Excel**.
4. Pilih Marketplace, Akun Toko, dan Jenis File **Laporan Pesanan (Order)**.
5. Unggah file `.xlsx` atau `.csv`, lalu klik **Upload & Proses**.
6. Sistem memproses file menggunakan *Background Queue Chunking* sehingga aman untuk puluhan ribu baris transaksi tanpa membuat website macet (*freeze*).

### 5.2 Impor Data Penghasilan (Shopee Income)
> **SOP**: Lakukan impor ini **HANYA SETELAH** seluruh file Order pada periode yang sama telah berhasil diimpor 100%.
1. Masuk ke Shopee Seller Centre > **Keuangan > Saldo Penjual > Penghasilan Saya**.
2. Pilih rentang waktu dan klik **Download Laporan / Rincian**.
3. Di Kulashop, buka menu **Import Data > Import Excel**.
4. Pilih Jenis File **Laporan Penghasilan (Income)**.
5. Sistem akan mencatat seluruh potongan biaya admin, voucher penjual, biaya transaksi, gratis ongkir, hingga nominal bersih yang cair ke rekening.

### 5.3 Penanganan Gagal Impor & Validasi
- Jika terdapat baris yang salah format atau kolom tidak lengkap, sistem tidak akan membatalkan seluruh file (*fail-safe partial import*).
- Baris yang bermasalah akan dicatat di halaman **Riwayat Import > Detail Error**, lengkap dengan nomor baris dan penyebab masalahnya untuk dapat dianalisis.

**Error: "Income tidak sesuai dengan order" (`order_not_found`)**
Error ini terjadi ketika Anda mengimpor file **Income**, namun sistem tidak menemukan nomor pesanan (No. Pesanan) tersebut di dalam database ERP.
- **Penyebab Utama:** Anda melanggar urutan SOP (*Order-First*). Anda mengimpor file Income untuk bulan Januari, sementara file Order untuk bulan Januari belum diimpor atau prosesnya masih berjalan.
- **Solusi:** Abaikan error tersebut, lalu segera unggah **File Order** untuk bulan/periode yang bersangkutan. Setelah Order berhasil terunggah sepenuhnya, masuk ke halaman *Detail Error* file Income, dan klik tombol **Retry (Coba Ulang Baris Error)**. Sistem otomatis akan menempelkan data Income tersebut ke Order yang baru saja Anda masukkan.

---

## 6. REKONSILIASI KEUANGAN & PENCAIRAN DANA
Menu **Rekonsiliasi Shopee** berguna untuk memastikan uang dari setiap pesanan benar-benar telah dicairkan oleh Shopee ke saldo penjual Anda:
1. **Status Cocok (Matched)**:
   - Pesanan sudah memiliki pasangan data di laporan penghasilan Shopee dan nominal pembayaran sesuai.
2. **Menunggu Penghasilan (Waiting Income)**:
   - Pesanan sudah berstatus selesai atau terkirim, namun dananya belum tercatat masuk di laporan penghasilan Shopee (masih tertahan di dompet Shopee).
3. **Selisih Nominal (Discrepancy)**:
   - Jika terdapat perbedaan antara nilai pesanan awal dengan uang yang cair (misalnya karena komplain pembeli atau retur sebagian).

### 6.1 Analisis Pencairan (Settlement)
Menu ini digunakan untuk mengelompokkan data penghasilan (Income) berdasarkan **Tanggal Cair (Settlement Date)**.
- **Filter Bulan & Tanggal:** Pilih bulan atau rentang waktu spesifik untuk melihat riwayat pencairan dari Shopee.
- **Tabel Ringkasan:** Menampilkan total pesanan, total harga jual, total potongan biaya admin beserta **persentasenya (%)**, dan nominal akhir yang cair (Settlement) pada tanggal tersebut.
- **Rincian Pencairan (Modal):** Klik pada salah satu baris tanggal untuk melihat detail Nomor Pesanan apa saja yang uangnya cair pada hari tersebut tanpa perlu memuat ulang halaman.

---

## 7. LAPORAN & DASHBOARD PEMILIK

### 7.1 Dashboard Pemilik (Owner Dashboard)
- **Ringkasan Penjualan (KPI Cards)**:
  - *Total Penjualan*: Akumulasi kotor nilai transaksi.
  - *Total Order*: Jumlah transaksi pesanan yang sukses diproses.
  - *Order Selesai*: Persentase dan jumlah pesanan yang tuntas.
  - *Total Income Marketplace*: Total uang bersih yang telah cair dari Shopee.
  - *Total Produk Terjual*: Total unit fisik produk yang keluar dari toko.
  - *Total Customer*: Jumlah pelanggan unik yang bertransaksi.
- **Trend Penjualan Harian**:
  - Grafik garis interaktif yang memvisualisasikan kenaikan atau penurunan omzet harian.
- **Top Produk Terlaris**:
  - Menampilkan daftar produk dengan volume penjualan tertinggi. Jika produk telah dipetakan, sistem akan menampilkan nama produk internal ERP. Jika belum, sistem tetap menampilkan nama produk Shopee.

### 7.2 Laporan Keuangan Bulanan
- Mengelompokkan pendapatan bersih per bulan per akun toko.
- Membantu pemilik toko melihat performa toko mana yang paling menghasilkan dan berapa total potongan komisi marketplace dalam kurun waktu 30 hari.

---

## 8. SISTEM LISENSI MODULAR (DEVELOPER PANEL)
Mulai dari KulaShop v1.0, sistem didesain sebagai platform *SaaS Modular*. Modul tambahan dapat diaktifkan atau dinonaktifkan per akun *Company* melalui Panel Developer:
1. **Modul Basic**: Mengunci fitur fundamental seperti Import Data, Pesanan, Pelanggan, Produk, dan Inventori (Modul ini bersifat *Backbone* dan tidak dapat dimatikan).
2. **Modul Finance & Rekonsiliasi**: Membuka fitur impor penghasilan (Income), Rekonsiliasi, dan Analisis Pencairan.
3. **Modul Profit & Laba**: Membuka akses ke laporan laba-rugi kotor, margin, HPP, dan manajemen pengeluaran bisnis (*Expense*).

Informasi lisensi aktif juga ditampilkan secara elegan di *Top Navigation Bar* berlogo lencana hijau, menampilkan rincian lisensi di dalam *dropdown* khusus bagi Owner.

---

## 9. TANYA JAWAB & KENDALA UMUM (FAQ)

**Q: Mengapa data Nama Pelanggan dan No HP berformat sensor bintang (`M******i`)?**
> **A**: Ini adalah kebijakan perlindungan data pribadi (GDPR/Data Privacy) langsung dari Shopee pada file ekspor Seller Centre. Sistem ERP membaca dan menyimpan data persis sesuai apa yang diberikan oleh Shopee.

**Q: Apakah data konsumen bisa diambil dari file Laporan Penghasilan (Income)?**
> **A**: Tidak. Laporan Penghasilan Shopee hanya memuat angka keuangan dan nomor pesanan. Seluruh data pembeli (Nama, No HP, Alamat) hanya tersedia di file Laporan Pesanan (Order).

**Q: Apa yang terjadi jika ada pesanan baru masuk dengan SKU yang belum pernah dimapping?**
> **A**: Proses impor pesanan akan tetap berhasil 100% tanpa error. SKU tersebut akan ditandai dan masuk ke daftar **SKU Belum Mapping**. Anda dapat memetakannya kapan saja atau menggunakan tombol **Sinkronisasi Otomatis**.

**Q: Jika saya mengedit harga HPP produk di Master Produk, apakah HPP order lama akan ikut berubah?**
> **A**: Tidak. Nilai HPP pada transaksi lama bersifat kebal (*immutable*) karena disimpan dalam bentuk *snapshot* pada saat pesanan diproses. Laporan laba masa lalu Anda akan tetap aman.

---

## 10. MANAJEMEN AKUN (SOP ADMIN & OWNER)

### 10.1 Pemisahan Wewenang
Sistem membedakan secara tegas apa yang bisa dilakukan oleh Admin (Operasional) dan Owner (Pemilik).
- **Admin**: Hanya dapat menjalankan tugas harian (impor data, pemetaan SKU, rekonsiliasi, melihat dashboard operasional). Admin tidak dapat mengakses laporan laba bersih maupun mengelola profil pengguna lain.
- **Owner**: Memiliki kontrol absolut. Dapat melihat laporan keuangan, mengatur profitabilitas, dan menambah/menghapus akun staf.

### 10.2 Cara Menambah / Mengelola Akun
1. Login menggunakan akun dengan role **Owner**.
2. Pada menu *Sidebar* kiri, di bawah kategori PENGATURAN, klik menu **Akun Pengguna**.
3. Di halaman ini, Anda akan melihat daftar seluruh pengguna sistem.
4. Klik tombol **Tambah Akun** untuk membuat akun Admin baru. Anda akan diminta memasukkan Nama, Email, Password, serta menentukan apakah pengguna ini bertindak sebagai *Admin* atau sesama *Owner*.
5. Untuk mereset password staf yang lupa kata sandi, klik tombol **Edit** pada akun bersangkutan, lalu ketikkan password baru dan simpan.

---

## 11. LAPORAN PROFIT & MARGIN LENGKAP (PROFITABILITY)

Laporan ini adalah mahkota dari Kulashop. Setelah Anda mengimpor data *Pesanan* dan memetakannya dengan *HPP*, serta mengimpor data *Penghasilan* untuk mengetahui potongan riil Shopee, sistem dapat menghitung profitabilitas nyata Anda.

### 11.1 Cara Akses
1. Pastikan Anda login sebagai **Owner**.
2. Masuk ke menu **Laporan > Profit & Margin**.
3. Secara default, sistem akan menampilkan data laba kotor pada bulan berjalan. Gunakan *Filter* di pojok kanan atas untuk memilih rentang bulan spesifik atau meninjau profit per toko marketplace tertentu.

### 11.2 Metrik Laba Kotor (Gross Profit)
Sistem menghitung laba kotor melalui formula:
`Laba Kotor = Total Uang Cair (Income) - Total HPP (Modal Barang)`

Terdapat 4 kartu indikator utama yang disajikan:
- **Total Income**: Uang riil yang masuk ke dompet penjual dari transaksi yang sudah selesai.
- **Total HPP**: Biaya modal dari barang-barang yang terjual.
- **Gross Profit**: Laba kotor dalam bentuk nominal Rupiah.
- **Gross Margin**: Persentase keuntungan kotor terhadap modal.

### 11.3 Peringatan Data Hilang (Profit Incomplete)
Sistem tidak akan asal menebak laba. Laba hanya bisa dihitung jika data lengkap (Pesanan sudah dipetakan dengan SKU Internal yang memiliki HPP, DAN dana sudah cair ke dompet).
- **Profit Complete**: Transaksi yang seluruh datanya lengkap dan labanya valid.
- **Profit Incomplete (Hilang/Tidak Lengkap)**: Transaksi selesai namun HPP-nya kosong (belum mapping) atau penghasilan belum diimpor. Sistem akan menyorot angka ini dalam warna merah agar Anda tahu bahwa angka laba Anda di layar masih belum 100% dari potensi aslinya. Segera lakukan mapping SKU untuk pesanan yang HPP-nya masih kosong.

---
*Manual Book ini disusun untuk Kulashop Versi 1.0 (Meliputi Tahap 1 hingga Tahap 15 - Final).*

---
## ROADMAP KULASHOP V2.0 (DALAM PERENCANAAN)
**Fokus Pengembangan: Modul Purchase & Supplier Management**
- Fitur Master Supplier
- Pembuatan *Purchase Order (PO)*
- Penerimaan Barang (*Good Receipt*) & Otomasi *Stock-In*
- Otomatisasi riwayat perubahan *HPP (Harga Beli)*
- Pencatatan status pelunasan sederhana (Hutang Pembelian / *Simple AP*)
