# Stock Management System

Aplikasi inventaris berbasis Laravel 13 dan Livewire 4. Fitur utama mencakup pengelolaan stok per akun, histori penyesuaian, impor/ekspor Excel, profil, panel admin read-only, dan draf barang dari OpenAI.

## Persiapan lokal

1. Siapkan PHP 8.3+, Composer, Node.js, MySQL, dan ekstensi Xdebug untuk menjalankan coverage.
2. Salin `.env.example` menjadi `.env`, lalu isi koneksi MySQL dengan database `pabwe_p7_stock` dan jalankan `php artisan key:generate`.
3. Jalankan `composer install`, `npm install`, `php artisan migrate --seed`, `php artisan storage:link`, dan `npm run build`.
4. Untuk membuat draf dengan AI, isi `OPENAI_API_KEY` di `.env`. API key tidak perlu disimpan di repository.
5. Jalankan aplikasi dengan `php artisan serve`.

Akun seed: `admin` / `admin` dan `member` / `member`.

## Pengujian dan pemeriksaan

`composer test` menjalankan seluruh suite dan mensyaratkan coverage `app/` 100%. `composer audit` dan `npm audit --audit-level=moderate` memeriksa advisori dependency. `vendor/bin/pint --test` memeriksa format PHP.

Jenkins pipeline tersedia di `Jenkinsfile`; agen Jenkins perlu menyediakan PHP dengan Xdebug, Composer, Node.js, dan MySQL/SQLite untuk tes.
