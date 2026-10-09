# Kulashop ERP

Aplikasi Laravel untuk mengelola pesanan marketplace, produk dan HPP, stok gudang, pembelian, rekonsiliasi penghasilan, pengeluaran, dan laporan profit.

## Persyaratan hosting cPanel

- PHP 8.4.1 atau lebih baru, termasuk versi PHP CLI yang dipakai Terminal.
- MySQL atau MariaDB dan ekstensi PDO MySQL, cURL, DOM, Fileinfo, GD, Mbstring, OpenSSL, SimpleXML, XMLReader, XMLWriter, dan ZIP.
- Terminal/SSH cPanel untuk menjalankan Artisan, serta HTTPS aktif.
- Folder `storage` dan `bootstrap/cache` dapat ditulis oleh akun hosting.

## Deployment melalui paket ZIP

1. Upload dan extract paket deployment ke `/home/CPANEL_USER/kulashop`.
2. Pada menu **Domains**, arahkan document root domain/subdomain ke `/home/CPANEL_USER/kulashop/public`. Konfirmasikan dukungan pengaturan document root dengan penyedia hosting bila domain utama tidak dapat diubah. Jangan jadikan direktori root aplikasi sebagai document root.
3. Buat database dan user MySQL di cPanel, kemudian berikan hak akses user ke database tersebut.
4. Jalankan perintah berikut dari Terminal, memakai PHP 8.4 sesuai konfigurasi hosting:

```bash
cd /home/CPANEL_USER/kulashop
cp .env.cpanel.example .env
```

5. Edit `.env`: isi `APP_URL` dengan domain HTTPS, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` dengan konfigurasi cPanel. Pertahankan `APP_ENV=production` dan `APP_DEBUG=false`.
6. Paket ZIP sudah berisi dependency produksi (`vendor`) dan asset yang dibuild (`public/build`). Jalankan:

```bash
php artisan key:generate --no-interaction
php artisan migrate --force --no-interaction
php artisan storage:link --no-interaction
```

`key:generate` hanya untuk instalasi pertama. Saat memperbarui aplikasi, pertahankan `.env`, `APP_KEY`, dan isi `storage` dari instalasi yang ada.

## Membuat akun owner pertama

Seeder bawaan berisi akun untuk pengembangan dengan password contoh. Untuk production, buat akun awal melalui Terminal tanpa menjalankan seeder demo:

```bash
php artisan tinker
```

Di dalam Tinker, jalankan kode berikut. Ganti alamat email dengan alamat owner yang akan digunakan. Password dimasukkan melalui prompt tersembunyi, bukan ditulis dalam source code.

```php
$company = App\Models\Company::firstOrCreate(['name' => 'Kulashop'], ['features' => ['finance', 'profit', 'purchase']]);
$email = Laravel\Prompts\text('Email owner', required: true, validate: ['email']);
$user = App\Models\User::firstOrNew(['email' => $email]);
if ($user->exists) { throw new RuntimeException('Akun sudah ada; pembuatan akun awal dihentikan.'); }
$password = Laravel\Prompts\password('Password owner', required: true, validate: ['min:12']);
$user->fill(['name' => 'Owner', 'password' => Illuminate\Support\Facades\Hash::make($password), 'role' => 'owner', 'company_id' => $company->id])->save();
unset($password);
exit
```

Setelah itu, seed hanya master marketplace dan gudang:

```bash
php artisan db:seed --class=MarketplaceSeeder --force --no-interaction
php artisan db:seed --class=WarehouseSeeder --force --no-interaction
php artisan optimize --no-interaction
```

Login menggunakan akun owner, lalu atur profil perusahaan, akun toko, produk, dan HPP melalui aplikasi. Modul finance, profit, dan purchase aktif untuk perusahaan yang baru dibuat oleh langkah di atas.

## Pilihan antrean import

Template cPanel menggunakan `QUEUE_CONNECTION=sync` agar import dapat dijalankan tanpa proses worker permanen. Untuk file besar, sesuaikan batas upload dan waktu eksekusi PHP hosting. Jika hosting mendukung worker/cron dan antrean diperlukan, gunakan `QUEUE_CONNECTION=database` serta jalankan worker sesuai pengaturan penyedia hosting.

## Deployment dari GitHub

Repository: https://github.com/nizarsuryadi89-bit/kulashop

Clone repository ke direktori aplikasi di luar document root. File `vendor` dan `public/build` tidak disimpan di GitHub. Jalankan Composer pada server dan build frontend pada mesin yang mempunyai Node.js, lalu upload `public/build` ke server:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
```

Untuk build frontend di mesin pengembangan/build:

```bash
npm ci
npm run build
```

Lanjutkan konfigurasi `.env`, database, akun owner, storage link, dan optimisasi seperti langkah deployment ZIP di atas. Tidak ada deployment otomatis ke cPanel dari repository ini.

## Pemeriksaan

Dengan dependency pengembangan terpasang:

```bash
php artisan test --compact
npm run build
```

Sesudah deployment, buka halaman login dan route health `/up`, lalu uji dashboard, import, histori pembelian, serta laporan profit. Periksa log di `storage/logs/laravel.log` jika ada kegagalan.

File `.env`, database lokal, upload, log, cache, dan konfigurasi alat lokal tidak disertakan dalam repository atau paket deployment.

Referensi: [Laravel deployment](https://laravel.com/framework/docs/13.x/deployment), [cPanel Domains](https://docs.cpanel.net/cpanel/domains/domains/).
