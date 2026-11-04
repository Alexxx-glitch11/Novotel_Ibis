# RRMS Novotel ibis

Panduan untuk menyiapkan dan menjalankan aplikasi di komputer lokal setelah mengambil source code dari GitHub.

## Teknologi

- PHP 8.2 atau lebih baru
- Laravel 12
- MySQL
- Composer
- Node.js dan npm

## Persiapan

Pastikan Git, PHP, Composer, Node.js/npm, dan MySQL sudah terpasang. Jika menggunakan Laragon, jalankan layanan MySQL sebelum menyiapkan database.

## Instalasi dari GitHub

1. Buka PowerShell atau terminal, lalu clone repository. Ganti URL berikut dengan URL repository GitHub yang benar:

   ```powershell
   git clone https://github.com/USERNAME/NAMA-REPOSITORY.git
   cd NAMA-REPOSITORY
   ```

2. Pasang dependensi PHP dan JavaScript:

   ```powershell
   composer install
   npm install
   ```

3. Buat file konfigurasi lokal `.env`:

   ```powershell
   Copy-Item .env.example .env
   ```

4. Buat database MySQL bernama `rrms_novobis` melalui phpMyAdmin atau MySQL. Sesuaikan pengaturan database pada `.env` dengan konfigurasi lokal:

   ```dotenv
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=rrms_novobis
   DB_USERNAME=root
   DB_PASSWORD=
   ```

   Jika MySQL menggunakan password, isi nilai `DB_PASSWORD` sesuai password tersebut.

5. Buat application key dan jalankan migrasi database:

   ```powershell
   php artisan key:generate
   php artisan migrate
   ```

   Untuk sekaligus menjalankan seeder bawaan (membuat data pengguna contoh), gunakan `php artisan migrate --seed` sebagai pengganti perintah migrasi di atas.

6. Build aset frontend:

   ```powershell
   npm run build
   ```

## Menjalankan aplikasi

Jalankan server Laravel:

```powershell
php artisan serve
```

Buka alamat yang ditampilkan di terminal, biasanya <http://127.0.0.1:8000>.

Untuk pengembangan frontend dengan Vite, jalankan `npm run dev` di terminal terpisah dan biarkan prosesnya tetap berjalan. Gunakan `npm run build` kembali untuk membuat build frontend.

## Menjalankan test

```powershell
php artisan test
```

## Troubleshooting

- **Koneksi database gagal:** pastikan MySQL aktif, database sudah dibuat, dan nilai `DB_*` di `.env` benar.
- **Perubahan `.env` belum terbaca:** jalankan `php artisan config:clear`.
- **Perintah Composer/npm tidak ditemukan:** pastikan Composer dan Node.js sudah terpasang dan tersedia di `PATH`, lalu buka terminal baru.
