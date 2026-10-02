# PT Thamrin Jaya Group

Portal company profile Laravel 13, Blade, Tailwind CSS 4, dan Vite. Enam unit bisnis memiliki profil, katalog produk, keunggulan, proses, dan inbox inquiry masing-masing. Database pengembangan menggunakan SQLite; migrasi juga menggunakan tipe yang kompatibel dengan MySQL.

## Menjalankan proyek

```bash
composer dev
```

Buka http://localhost:8000. Portal berada di `/`, login di `/login`, dan CMS di `/admin`.

## Instalasi baru

Lingkungan awal: PHP 8.5 dan Node.js 24. Gunakan versi yang sama untuk kompatibilitas dengan lock file.

```bash
composer install
cp .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate --seed
php artisan storage:link
npm ci
npm run build
php artisan admin:create admin@example.com
```

Perintah terakhir meminta password minimal 12 karakter dengan huruf dan angka. Gunakan alamat email administrator yang sebenarnya. Tidak ada pendaftaran publik atau password default. Opsi `--generate` membuat password acak dan menampilkannya sekali. Perintah menolak menimpa akun yang sudah ada.

## Konten & CMS

- Dashboard: kelola enam perusahaan dan lihat ringkasan inquiry.
- Profil & gateway: urutan kartu, slug, ringkasan, headline, foto/banner, warna identitas, status aktif, dan penanda konten contoh.
- Kontak: email, telepon, jam operasional, alamat, nomor WhatsApp internasional, dan alamat/koordinat Google Maps.
- Keunggulan, katalog, dan proses: tambah, edit, hapus, serta tentukan urutan tiap konten.
- Inbox: filter berdasarkan perusahaan, status, atau pencarian; baca pesan, ubah status, balas lewat aplikasi email, atau hapus pesan.
- Akun & keamanan: ganti password. Pemulihan password menggunakan email Laravel.

Seeder menyediakan **konten demonstrasi** dan ilustrasi SVG lokal, bukan foto atau informasi resmi perusahaan. Isi kontak sengaja kosong. Ganti materi melalui CMS dan nonaktifkan penanda contoh setelah diverifikasi. Seeder tidak menimpa perusahaan yang sudah ada dengan slug yang sama.

Upload menerima JPG, PNG, atau WebP maksimal 5 MB dan disimpan di disk `public`. Sesuaikan `upload_max_filesize` dan `post_max_size` PHP untuk menerima ukuran tersebut. File lama dibersihkan saat diganti atau dihapus. `public/storage` harus terhubung melalui `storage:link`.

## Email dan MySQL

Lokal memakai `MAIL_MAILER=log`; tautan reset tercatat di `storage/logs/laravel.log`, bukan terkirim ke email. Untuk email sungguhan, atur mailer/SMTP dan `MAIL_FROM_ADDRESS` pada `.env`. Tetapkan `APP_URL` sesuai domain agar tautan reset benar.

Untuk MySQL, buat database kosong dan atur `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, serta `DB_PASSWORD` pada `.env`, lalu jalankan `php artisan migrate --seed`. Ini membuat struktur baru; tidak memindahkan data SQLite secara otomatis. MySQL belum diuji di lingkungan lokal ini.

## Pemeriksaan

```bash
php artisan test --compact
vendor/bin/pint --format agent
npm run build
```

Tes mencakup portal enam unit, isolasi katalog, hak akses admin, autentikasi/reset password, pembatasan request, CRUD konten, upload, pemisahan inquiry, dan pengelolaan inbox.

Sebelum publikasi: ganti konten contoh, gunakan kontak resmi, konfigurasikan mailer/domain, aktifkan HTTPS, dan gunakan `APP_DEBUG=false`. Arahkan document root server ke direktori `public`.
