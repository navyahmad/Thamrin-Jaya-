# Backend Plan — PT Thamrin Jaya Group

Tanggal audit: 2 Oktober 2026. Status: **Tahap 1–3 selesai; tahap 4–15 belum dikerjakan dalam rangkaian ini.**

Dokumen ini membedakan kondisi yang sudah diverifikasi dengan kontrak implementasi berikutnya. Keberadaan tabel tidak berarti CRUD, resolver publik, atau desainnya sudah selesai.

## 1. Lingkup produk

- Company profile dinamis: satu portal holding dan enam situs anak perusahaan, dikelola admin pusat.
- Tidak ada transaksi, kasir, keranjang, pembayaran, harga jual/beli, atau stok.
- Gateway menampilkan enam panel perusahaan, ringkasan, dan tautan menuju profil masing-masing.
- Setiap anak perusahaan memiliki template/layout, tipografi, dan identitas visual sendiri sesuai logo. Mengganti warna saja belum memenuhi kebutuhan desain.
- Menu Home: hero/carousel, about, services, clients, capacity, CSR. About Us: history, visi/misi, sertifikasi, the group. Service: mega menu, katalog, keunggulan dan proses sesuai konteks. Contact: kontak, peta, WhatsApp, inquiry.
- Fortify menangani autentikasi. CRUD menggunakan controller, FormRequest, policy, Eloquent, dan Blade aplikasi.
- Pekerjaan backend mencakup layar admin fungsional. Enam desain publik final menjadi pekerjaan frontend sesudah kontrak data dan resolver siap.
- Data demo bukan informasi resmi. Jangan mengarang kapasitas, klien, sertifikasi, alamat, atau sejarah perusahaan.

## 2. Snapshot audit awal (sebelum tahap 2)

Bagian ini mempertahankan hasil audit tahap 1. Perubahan aktual tahap 2 dicatat di bagian 13.

### Runtime dan konfigurasi

| Komponen | Hasil pemeriksaan |
| --- | --- |
| PHP | 8.5.4 |
| Laravel | 13.34.0 |
| PHPUnit | 12.5.37 |
| Laravel Fortify | Belum terpasang; autentikasi masih `AuthController` |
| Frontend | Manifest menggunakan Tailwind CSS `^4.0.0`, Vite `^8.0.0` |
| Database aktif | MySQL, `thamrin_jaya` |
| Migration | Kelima migration berstatus Ran |
| Mail | `log`; pengiriman email nyata belum terbukti |
| Queue / session | `database` / `database`; worker belum diverifikasi |
| Environment | `local`, debug aktif; bukan konfigurasi produksi |
| Storage | `public/storage` menunjuk ke `storage/app/public` |
| Routing aplikasi | 24 route non-vendor |
| Akun | 0 user di MySQL aktif; perlu bootstrap admin saat tahap autentikasi |

Database diperiksa hanya-baca. Tidak menjalankan migration, seeder, atau membuat akun dalam tahap ini. Pembacaan MySQL memerlukan akses di luar sandbox; setelah diizinkan, koneksi dan pemeriksaan berhasil. Nilai rahasia `.env` tidak dicantumkan.

### Data yang tersedia

| Tabel | Jumlah |
| --- | ---: |
| companies | 6 |
| sites | 7 (1 holding, 6 perusahaan) |
| pages | 28 |
| page_sections | 112 |
| section_items | 6 |
| menus / menu_items | 14 / 112 |
| services / company_service | 4 / 6 |
| products | 18 |
| pillars | 24 |
| process_steps | 25 |
| inquiries / users | 0 / 0 |

`transactions`, `transaction_details`, dan `categories` tidak ada. Kategori katalog saat ini berupa `products.category`; tabel kategori tersendiri tidak diperlukan untuk lingkup sekarang.

### Yang sudah berjalan

- Portal dan profil perusahaan membaca `Company`, katalog, pilar, dan langkah proses.
- Login/logout, reset password, ganti password, gate `access-admin`, serta perintah `admin:create` tersedia melalui implementasi autentikasi lama.
- Admin dapat mengedit profil, banner, urutan perusahaan, katalog, pilar, proses, dan inbox.
- Nested CRUD katalog mengambil item melalui relasi perusahaan; pengubahan ID lintas perusahaan sudah diuji ditolak.
- Model struktur CMS memiliki relasi, casts, pengurutan, dan scope dasar. Foreign key menu membatasi halaman ke site yang sama dan parent ke menu yang sama. Model menu menolak siklus.
- Kontak anak perusahaan sudah bersumber dari `Company` melalui `Site::resolvedContactDetails()`.
- Enam file logo tersedia: `globalindo.webp`, `multipack.webp`, `hterootopack.webp`, `maxtech.webp`, `sinarjaya.webp`, `topprintingjaya.webp`. Ejaan nama file tidak menjadi dasar mengubah nama legal perusahaan.

### Kesenjangan dan tahap penanganan

| Temuan dari kode saat ini | Dampak | Tahap |
| --- | --- | --- |
| `PortalController` belum membaca Site/Page/Section/Menu | Konten tabel dinamis belum menentukan tampilan publik | 6–13 |
| Belum ada CRUD halaman, section, item, menu, service, dan branding site | Admin belum bisa mengelola keseluruhan konten | 6–12 |
| Fortify belum terpasang; route autentikasi manual aktif | Integrasi harus mengganti alur lama tanpa route ganda | 2–3 |
| Validasi masih inline; belum ada policy per modul | Perlu konsistensi izin, validasi dan pembatasan konteks | 3, 6–14 |
| Nama/slug/warna tersedia di Company dan Site | Berpotensi menjadi dua sumber data yang berbeda | 4, 6 |
| `company_id` Site nullable unique | Satu site per perusahaan terjaga, tetapi database masih mengizinkan lebih dari satu holding NULL | 4 |
| Seeder memakai `firstOrCreate` berdasarkan slug/key yang dapat berubah | Konten yang dihapus bisa dibuat lagi; rename dapat memicu duplikasi | 4 |
| Section type/source/settings belum memiliki registry validasi | Payload bebas, sumber tidak cocok, dan template tidak dikenal belum ditangani | 4, 10–13 |
| Page delete menghapus menu terkait lewat cascade; service delete hanya men-null-kan target menu | Perlu dampak penghapusan yang jelas dan tautan tanpa target tidak tampil | 7, 9, 12 |
| Upload/delete tersebar di controller | Kegagalan DB dapat meninggalkan file; file bersama dapat terhapus | 5 |
| Products/pillars/process belum punya status aktif per item | Belum dapat menyembunyikan satu item tanpa menghapus | 4, 8 |
| CTA slide seed `#products` sementara katalog direncanakan di halaman services | Tautan perlu diselesaikan terhadap halaman tujuan yang benar | 11, 13 |
| Semua situs masih memakai tampilan publik generik | Template key yang tersimpan belum berarti enam desain sudah ada | 13 + frontend |
| Email log, akun MySQL kosong | Login operasional/reset email nyata belum siap | 2, 15 |

Referensi utama: `routes/web.php`, `app/Http/Controllers/`, `app/Models/`, `database/migrations/2026_09_29_133758_create_portal_tables.php`, `database/migrations/2026_10_01_144643_create_dynamic_content_tables.php`, dan `database/seeders/DynamicContentSeeder.php`.

## 3. Kontrak kepemilikan data

Bagian berikut adalah keputusan implementasi, bukan klaim bahwa seluruh perilakunya sudah terpasang.

| Informasi | Sumber utama dan aturan |
| --- | --- |
| Identitas anak perusahaan | `companies.name`, `short_name`, `slug`, `sector`; URL menggunakan company slug |
| Identitas holding | Site holding dengan slug internal `group` dan `company_id = null`; `sites.name` |
| Site anak perusahaan | Tepat satu per company; `company_id` tidak dapat dipindahkan melalui formulir biasa |
| `sites.name` / `sites.slug` anak perusahaan | Kompatibilitas/identitas internal; tidak menjadi editor nama atau URL kedua. Display dan URL menggunakan Company |
| Logo/favicon/template/warna/font | Site. `companies.accent` hanya fallback selama transisi; jangan mempertahankan dua editor warna |
| Ringkasan dan panel gateway | Company: summary, tagline, banner, sort_order; logo dan branding dari Site |
| Profil dan sejarah anak perusahaan | Company: about, history, about_image_path. Section bertipe terkait menampilkan sumber ini |
| Profil/sejarah holding | Body/item pada section holding sesuai registry; tidak membuat company palsu untuk holding |
| Carousel anak perusahaan | Section items; merupakan konten slide mandiri. Salinan seed tidak otomatis tersinkron dengan summary Company |
| Katalog/pilar/proses | Relasi Company; tidak disalin menjadi section items |
| Kontak anak perusahaan | Company: email, phone, whatsapp, hours, address, map_query |
| Kontak holding | `sites.contact_details`, dengan struktur key tervalidasi |
| Footer/social | Site: footer_description, copyright_text, social_links; salinan awal summary bukan sinkronisasi otomatis |
| SEO | Page jika diisi, fallback Site, lalu identitas perusahaan/holding |
| Struktur halaman | Page → PageSection → SectionItem; section menentukan komposisi, variant dan sumber konten |
| Navigasi | Menu → MenuItem; link internal memakai ID target dan named route, bukan salinan URL |
| Kelompok layanan | Service dan pivot company_service; kategori layanan berbeda dari kategori produk |
| Pesan | Inquiry wajib milik satu Company; tidak mencampur inbox antar tujuan |

Migrasi penyesuaian harus additive dan mempertahankan data. Jangan menghapus kolom lama sebelum semua pembaca/penulisnya dipindahkan dan diuji. Penegakan satu holding, status item, dan seed initialization dibangun pada tahap 4; perubahan tersebut belum dilakukan di audit ini.

## 4. Kontrak URL dan identitas

| Tujuan | URL canonical |
| --- | --- |
| Home holding | `/` |
| Halaman inti holding | `/about-us`, `/services`, `/contact` |
| Halaman tambahan holding | `/pages/{page_slug}` |
| Home anak perusahaan | `/{company_slug}` |
| Halaman anak perusahaan | `/{company_slug}/{page_slug}` |
| Inquiry holding | `POST /inquiries`, wajib memilih perusahaan aktif |
| Inquiry anak perusahaan | `POST /{company_slug}/inquiry`, tujuan ditentukan route |
| CMS | `/admin/...` |
| Preview | `/admin/sites/{site}/pages/{page}/preview` |

Prefix `/pages` untuk halaman tambahan holding menghindari perebutan slug root dengan perusahaan. Halaman layanan tidak memerlukan sistem katalog penjualan. Target service menu diarahkan ke section layanan pada halaman services, memakai anchor stabil berbasis ID service dan daftar perusahaan terkait sesuai konteks.

- Page `home` merupakan identitas Home setiap site; slug tidak dapat diubah atau dihapus. Home tidak muncul pada URL canonical.
- Empat slug inti `home`, `about-us`, `services`, `contact` dilindungi; judul, isi, urutan, menu, dan status publikasi tetap dapat diedit. Halaman tambahan dapat diubah slug-nya.
- Alias `/home` dan `/{company}/home`, jika diimplementasikan, hanya redirect ke canonical setelah lolos pemeriksaan publikasi.
- Company slug unik global, lowercase alfanumerik dengan dash; page slug unik per site. Perubahan slug tidak otomatis menyediakan riwayat redirect. UI wajib menunjukkan URL baru.
- Daftar root reserved minimal: `home`, `about-us`, `services`, `contact`, `pages`, `inquiries`, `admin`, `login`, `logout`, `register`, `forgot-password`, `reset-password`, `user`, `two-factor-challenge`, `email`, `confirm-password`, `up`, `storage`, `build`, `images`, `api`. Cocokkan kembali dengan route Fortify yang benar-benar terpasang, termasuk prefix vendor, pada tahap 2/4.
- Daftarkan route statis/Fortify/admin sebelum catch-all publik. Untuk page anak perusahaan, `inquiry` dilindungi sebagai endpoint sistem.
- Route admin memakai ID stabil dan relasi berjenjang: site → page → section → item; site → menu → menu item; company → product/pillar/process.
- Route name publik yang sudah dipakai (`home`, `company.show`, `company.inquiry`) dipertahankan bila memungkinkan. Route baru publik memakai `public.*`, admin `admin.*`, autentikasi memakai nama Fortify yang diverifikasi saat instalasi.
- Pemindahan route update lama dilakukan bersama pembaruan formulir dan tes; jangan mengandalkan redirect untuk request mutasi.

## 5. Status publikasi dan preview

- Holding tampil hanya jika Site aktif dan Page terpublikasi. Anak perusahaan juga mensyaratkan Company aktif. Section dan item harus aktif beserta seluruh induknya.
- Gateway/the-group hanya memuat perusahaan yang aktif, memiliki site aktif dan Home terpublikasi. Menu tidak menampilkan target yang tidak dapat diakses publik.
- Draft/inaktif pada URL publik menghasilkan 404, termasuk ketika admin membuka URL publik biasa.
- Draft menggunakan `pages.is_published = false`; tidak ada scheduled publication atau revision snapshot pada lingkup awal.
- **Menyimpan perubahan konten yang sudah published langsung mengubah konten publik.** UI harus menjelaskannya. Untuk menyunting tanpa tampil, unpublish dahulu lalu preview; halaman sementara tidak tersedia publik.
- Preview memerlukan login admin, authorization dan scoped binding; memakai `noindex` serta `Cache-Control: private, no-store`. Tidak ada token preview publik pada lingkup awal.
- Pengurutan menggunakan sort_order lalu ID. Reorder tervalidasi sebagai daftar ID unik dalam parent yang benar dan disimpan atomik.
- Data resmi yang belum tersedia dibiarkan kosong/nonaktif. Client, capacity, CSR, visi/misi, dan sertifikasi seed tidak boleh dianggap konten resmi.

## 6. Registry section dan template

Template key berasal dari allowlist kode: `group-gateway`, enam key brand yang sudah disimpan, dan fallback `default`. Variant juga dibatasi per template/type. Input admin tidak boleh menjadi path Blade, nama class, atau kode executable. Key brand menyediakan kontrak desain berbeda; view finalnya tetap perlu dibangun kemudian.

| Type | Sumber yang diizinkan | Form/kebutuhan |
| --- | --- | --- |
| gateway | relations | Perusahaan publik dan identitas panel; holding saja |
| carousel | items | Gambar, alt, judul, ringkasan, CTA |
| about | company untuk anak / manual untuk holding | Profil dan gambar sesuai sumber |
| history | company untuk anak / manual untuk holding | Narasi; timeline item hanya jika variant registry secara eksplisit memilihnya |
| services | relations | Service aktif, relasi perusahaan sesuai konteks |
| group | relations | Daftar perusahaan publik |
| products, pillars, process | company | Relasi perusahaan pemilik site; tidak tersedia sebagai katalog holding |
| clients | items | Nama, logo, tautan opsional |
| capacity | items | Label, value, unit; bukan stok produk |
| csr | items | Judul, narasi, tanggal, gambar |
| vision_mission | items | Visi/misi dengan key yang tervalidasi |
| certifications | items | Nama, penerbit/tanggal pada settings tervalidasi, gambar/PDF |
| map, contact | contact_details | Sumber kontak terpusat dan form inquiry |

`settings.source` yang sekarang ada akan divalidasi melalui registry. Setiap tipe memiliki field, batas ukuran dan bentuk settings sendiri; bukan textarea JSON bebas. Konten bersumber relasi tidak memiliki editor body tandingan. Type/source yang tidak cocok dengan konteks ditolak. Section seed holding untuk produk/pilar/proses yang nonaktif tidak boleh dapat diaktifkan sebagai sumber company.

Pergantian type yang akan menghilangkan item/field tidak boleh diam-diam membuang data; jika memiliki data yang tidak kompatibel, tolak dan minta membuat section baru. Key section menjadi anchor stabil, unik per page dan tidak diubah lewat edit konten biasa.

## 7. Menu dan batas konteks

- Header dan footer dimiliki masing-masing site. Label, urutan, status, serta pohon item dinamis.
- Target item tepat satu: page (dengan anchor opsional), service, URL yang aman, atau heading tanpa tautan. Bentuk menu `link`/`mega_menu` berbeda dari jenis target; heading tidak memerlukan penambahan enum sembarangan.
- Page target berasal dari site yang sama; anchor harus ada pada page tersebut. Parent berasal dari menu yang sama, tanpa siklus; batasi kedalaman tiga tingkat untuk lingkup awal.
- Service anak perusahaan hanya boleh dipilih bila terhubung ke perusahaan tersebut. Holding dapat menampilkan service aktif dengan perusahaan publik terkait. Seed menu yang memuat service tak sesuai konteks perlu dinormalisasi tanpa mengubah data resmi lain.
- URL eksternal hanya HTTP/HTTPS; `mailto:`/`tel:` hanya pada field kontak yang divalidasi khusus. Tolak javascript/data/protocol-relative URL. Internal CTA harus terselesaikan terhadap page/anchor yang valid.
- Induk nonaktif menyembunyikan seluruh cabang. Target draft/inaktif/hilang tidak dirender sebagai tautan. Heading/mega menu kosong disembunyikan.
- Admin pusat boleh mengelola semua site, tetapi ID yang tidak cocok dengan parent route tetap ditolak; tidak ada auto-pindah konten ke site lain.

## 8. Penghapusan dan media

| Objek | Perilaku CMS yang ditetapkan |
| --- | --- |
| Holding Site | Tidak dapat dihapus atau dibuat duplikat |
| Company / Site anak | Default nonaktifkan; hard delete ditolak selama masih memiliki konten, site atau inquiry. Tidak menyediakan penghapusan site anak secara terpisah yang meninggalkan company tanpa site |
| Empat page inti | Tidak dapat dihapus; gunakan status publikasi |
| Page tambahan | Tolak jika masih ditarget menu; admin melepas target terlebih dahulu. Sesudah itu penghapusan membawa section/item, dengan informasi dampak di UI |
| Section | Menghapus item miliknya; tolak jika anchor masih dirujuk menu sampai referensi dilepas |
| Section item / katalog / pilar / proses | Hapus hanya record pada konteks parent yang sah |
| Service | Nonaktifkan untuk menyembunyikan; hard delete ditolak selama ditarget menu atau masih terhubung perusahaan |
| Menu item beranak | Tolak sampai anak dipindahkan/dihapus; tidak menghapus cabang tanpa terlihat |
| Menu | Slot header/footer dilindungi; edit item/statusnya |
| Inquiry | Admin dapat menghapus secara eksplisit; menghapus pesan tidak menghapus perusahaan |

Aturan aplikasi di atas sengaja lebih ketat daripada cascade database saat ini. FK tetap menjadi perlindungan terakhir, bukan satu-satunya kebijakan UI. Penegakan dilakukan atomik dan menguji relasi baru yang masuk bersamaan bila relevan.

Media bawaan `public/images` dibedakan dari upload di disk public. Normalisasi path dilakukan melalui satu layanan; jangan mencampur `images/...`, `storage/...`, dan path relatif disk tanpa resolver. File bawaan tidak dihapus oleh CRUD. Gambar upload: JPG/PNG/WebP; PDF khusus dokumen sertifikasi; SVG upload tidak diterima tanpa sanitasi yang secara khusus diimplementasikan. Validasi MIME, ukuran, dan path traversal; ukuran gambar awal mengikuti batas yang sudah ada yaitu 5 MB, PDF ditetapkan di registry.

Simpan file baru, tangani kegagalan DB dengan membersihkan file baru, lalu hapus file lama setelah commit hanya bila tidak lagi direferensikan. Periksa referensi lintas seluruh field media. Cascade DB tidak memicu event deletion Eloquent anak, sehingga penghapusan berjenjang harus mengumpulkan media sebelum data hilang. File upload publik tidak dianggap dokumen rahasia meski halaman pemiliknya draft.

## 9. Autentikasi, inquiry, dan operasi

- Guard web/session dan flag `is_admin` dipertahankan. Tidak ada public registration atau role kasir/pelanggan.
- Tahap 2 memasang Fortify setelah memeriksa versi kompatibel; memindahkan login/logout/reset/update password dan view, dengan satu pemilik setiap endpoint. Gate admin tetap wajib pada CMS.
- Login rate limit, session regeneration/logout invalidation, respons reset generik, dan akun nonadmin tidak memperoleh akses CMS wajib diuji. Seluruh jalur reset/update harus menjaga aturan akun admin.
- Tahap 3 menambah policy, password confirmation dan 2FA opsional dengan recovery codes; hanya aktif sesudah konfirmasi setup. Secret/recovery tidak masuk log/serialisasi publik.
- Akun pertama dibuat melalui `admin:create` dengan identitas yang diberikan pemilik, bukan password default atau seeder kredensial. Audit ini tidak membuat akun.
- Inquiry anak memakai company dari route; jangan mempercayai company/status/read_at dari body. Inquiry holding mewajibkan pilihan perusahaan yang tersedia publik. Tidak menambah inbox holding terpisah pada lingkup awal.
- Terapkan validasi, consent, honeypot dan rate limit. Status inbox tetap `new`, `in_progress`, `resolved`, dengan read_at terpisah. Perubahan status/read sebaiknya mutasi eksplisit; GET show yang kini menandai read dipindahkan ke request mutasi saat refactor inbox.
- Simpan pesan terlebih dahulu. Notifikasi email opsional setelah commit melalui queue; kegagalan email tidak menghilangkan inquiry. Link mailto bukan bukti email terkirim.
- Database test harus terpisah dari `thamrin_jaya`. Dilarang migrate:fresh/refresh atau reset data utama. Seeder demo untuk inisialisasi eksplisit, bukan perintah rutin deployment; tahap 4 menambah mekanisme inisialisasi yang tidak menghidupkan kembali konten terhapus/diubah.

## 10. Tahapan dan kriteria selesai

Semua tahap implementasi wajib membaca kontrak ini, mengikuti AGENTS.md, mempertahankan fitur lama, menjalankan tes yang relevan, dan memperbarui status dengan bukti. Suatu modul CRUD selesai jika layar admin dapat dipakai, validasi dan authorization bekerja, ID lintas konteks ditolak, serta create/read/update/delete atau pembatasan delete sesuai kontrak telah diuji.

| Tahap | Cakupan | Bukti/kriteria selesai | Status |
| --- | --- | --- | --- |
| 1 | Audit dan kontrak backend | Kode, database, route, test baseline dan keputusan terdokumentasi | Selesai |
| 2 | Fortify dasar | Login/logout/reset/ganti password lewat Fortify, view berfungsi, tanpa registrasi dan route ganda; admin:create tetap bekerja; regresi auth lulus | Selesai |
| 3 | Authorization dan keamanan akun | Policy tiap modul, password confirmation, 2FA setup/challenge/recovery/disable; guest/nonadmin ditolak; tidak bisa menaikkan privilege sendiri | Selesai |
| 4 | Invariant data dan registry | Satu holding/satu site per company, reserved slug, type/source/template whitelist, status item; migrasi additive; reseed tidak menduplikasi rename/mengembalikan delete | Belum |
| 5 | Layanan media | Upload/replace/remove konsisten, file bersama aman, rollback dan cascade cleanup diuji dengan storage fake | Belum |
| 6 | Company/site/gateway | Pilih konteks site; profil, branding, kontak, footer/social/SEO, urutan/status panel dapat dikelola; company baru membentuk site konsisten; proteksi delete | Belum |
| 7 | Pages | CRUD halaman tambahan, empat halaman inti terlindungi, urutan/SEO/draft/publish, slug per site dan dampak menu diuji | Belum |
| 8 | Katalog/pilar/proses | Refactor request/policy/media; create/edit/delete/order/status/filter/pagination sesuai modul; isolasi perusahaan; tanpa field transaksi | Belum |
| 9 | Services | CRUD empat kelompok dan relasi perusahaan, urutan/status, target menu dan aturan penghapusan konsisten | Belum |
| 10 | Sections | Form per type, sumber/variant tervalidasi, tambah/edit/delete/reorder/status, tipe tidak kompatibel ditolak | Belum |
| 11 | Section items | Carousel/client/capacity/CSR/history variant/visi-misi/sertifikat memiliki form tepat; upload/PDF/status/order; tidak membuat klaim resmi palsu | Belum |
| 12 | Navigasi | Header/footer/mega menu, target eksklusif, tree tanpa siklus, scope/depth/order, tautan invalid/draft difilter | Belum |
| 13 | Resolver publik dan preview | Seluruh URL/content/theme/menu memakai data CMS, eager loading, status induk dipatuhi; preview aman; Blade minimum cukup untuk membuktikan alur | Belum |
| 14 | Kontak dan inbox | Form holding/perusahaan masuk ke tujuan benar; inbox read/status/filter/delete; validasi/antispam; notifikasi opsional tidak mengorbankan penyimpanan | Belum |
| 15 | Integrasi dan kesiapan operasi | Semua admin flow terhubung; suite SQLite dan MySQL terisolasi; pengujian HTTP/browser CSRF/auth/upload/publish; konfigurasi, queue, SMTP, backup/restore dan batas tersisa dicatat | Belum |

Tahap 13 belum menandakan desain publik final selesai. Tahap 15 tidak mencakup deployment otomatis. SMTP, restore backup, maupun browser acceptance tidak boleh ditandai berhasil tanpa pelaksanaan dan bukti yang sesuai.

## 11. Bukti verifikasi tahap 1

- `composer show --direct` dan `php -v`: versi pada tabel audit terkonfirmasi.
- `php artisan route:list --except-vendor --no-interaction`: 24 route aplikasi, masih autentikasi manual dan CRUD lama.
- `php artisan migrate:status --no-interaction`: kelima migration Ran pada MySQL aktif.
- Query hanya-baca melalui bootstrap aplikasi: jumlah tabel/data dan konfigurasi nonrahasia sesuai snapshot di atas; tabel transaksi tidak ditemukan.
- `vendor/bin/phpunit`: **43 tests passed, 306 assertions**, durasi keluaran runner 6.200 ms (6,2 detik).
- Percobaan awal `php artisan test --compact --no-interaction` ditolak karena opsi `--no-interaction` diteruskan ke PHPUnit; pengujian kemudian dijalankan langsung melalui runner di atas dan lulus.
- Test menggunakan SQLite `:memory:`, mail array, queue sync, session array sesuai `phpunit.xml`; tidak mengubah database MySQL utama.
- Cakupan saat ini meliputi auth lama, CRUD lama, inquiry, schema/relasi, seed, scoping dan beberapa constraint. Belum membuktikan Fortify, seluruh CRUD dinamis, browser CSRF, SMTP nyata, worker, atau semua perilaku MySQL dalam HTTP request.
- Tahap ini hanya menambahkan dokumen ini. Tidak mengubah PHP, dependency, `.env`, migration maupun data utama. Pint/build tidak diperlukan untuk perubahan dokumentasi saja.

## 12. Langkah berikutnya

Berikutnya **tahap 4: invariant data dan registry**: satu holding, hubungan company/site, reserved slug, allowlist template/section/source, status item, dan inisialisasi seed yang aman terhadap rename/delete. Lanjutkan dengan migration additive dan pertahankan pengaman database pengujian.


## 13. Hasil tahap 2 — Fortify dasar

Tanggal implementasi: 2 Oktober 2026.

### Implementasi

- Terpasang `laravel/fortify` **v1.40.0**, constraint `^1.40`; Laravel tetap 13.34.0. Composer menambahkan dependency Fortify dan melaporkan tidak ada security advisory saat instalasi.
- Acuan pemasangan: [dokumentasi resmi Fortify Laravel 13](https://raw.githubusercontent.com/laravel/docs/13.x/fortify.md). API dan route dicocokkan langsung dengan kode paket v1.40.0 terpasang.
- `FortifyServiceProvider` terdaftar di bootstrap; fitur aktif hanya reset password dan update password. Registrasi, profile update, verifikasi email, 2FA, dan passkey tidak diaktifkan.
- `AuthController` dan route autentikasi manual dihapus. Form Blade lama digunakan kembali dengan route Fortify. Tidak ada endpoint update password lama yang dibiarkan sebagai alur kedua.
- Provider `admin-eloquent` menambahkan batas `is_admin = true` pada pencarian user untuk login, session, remember cookie, dan password broker. Dengan demikian token valid milik nonadmin tetap tidak dapat dipakai untuk reset lewat Fortify. User yang dicabut status adminnya tidak lagi dapat dipulihkan dari session/remember provider.
- Middleware `EnsureFortifyAdmin` menolak user nonadmin yang sudah terautentikasi pada endpoint Fortify; logout tetap tersedia untuk keluar. CMS tetap memakai `auth`, `auth.session`, dan `can:access-admin`.
- Password minimum 12 karakter, huruf dan angka, wajib konfirmasi. Update mewajibkan current password, memutar remember token dan session ID; Fortify menghapus reset token yang masih tersisa. Reset memutar remember token dan menggunakan event bawaan Fortify.
- Login dibatasi 5 request/menit per email+IP dan 20 per IP. Permintaan recovery 3/menit per IP, reset 5/menit per IP, konfirmasi/update password 5/menit per IP. Batas HTTP berlaku sama tanpa mengungkap status akun.
- Respons reset-link sukses, email tidak ditemukan/nonadmin, maupun throttle broker memberikan pesan generik yang sama, termasuk JSON. Throttle HTTP tetap 429.
- Email tidak otomatis diubah ke lowercase agar akun lama tetap kompatibel; key rate limiter dinormalisasi. Tidak ada perubahan email atau password pada data utama.
- Form konfirmasi password tersedia agar endpoint bawaan Fortify tidak mengalami missing view; penerapan konfirmasi untuk tindakan sensitif mengikuti tahap 3.
- Reserved slug perusahaan diperluas untuk namespace auth/Fortify. Kontrak slug lebih luas dan registry tetap tahap 4.
- Migration bawaan 2FA dan passkeys yang dipublikasikan installer belum pernah dijalankan dan dikeluarkan dari tahap ini. Skema users/password_reset_tokens/sessions yang ada sudah mencukupi autentikasi dasar. Dependency passkeys milik Fortify tetap terpasang, tetapi tidak ada route/fitur passkey aktif.

### Endpoint yang berlaku

| Operasi | Method / URL | Route name |
| --- | --- | --- |
| Form login | GET `/login` | `login` |
| Login | POST `/login` | `login.store` |
| Logout | POST `/logout` | `logout` |
| Form recovery | GET `/forgot-password` | `password.request` |
| Kirim reset-link | POST `/forgot-password` | `password.email` |
| Form reset | GET `/reset-password/{token}` | `password.reset` |
| Reset password | POST `/reset-password` | `password.update` |
| Update password akun | PUT `/user/password` | `user-password.update` |
| Konfirmasi password | GET/POST `/user/confirm-password` | `password.confirm` / `password.confirm.store` |
| Status konfirmasi | GET `/user/confirmed-password-status` | `password.confirmation` |

`admin.password.update` dan `password.store` tidak lagi digunakan. Halaman akun tetap `/admin/account`. Login sukses menuju intended URL atau `/admin`; logout menuju `/login`.

### Verifikasi dan batas operasional

- Tes autentikasi diperluas dari 7 menjadi 19: endpoint Fortify, fitur nonaktif, admin-only, validasi, privacy recovery, token invalid/expired/reuse/nonadmin, rate limit, rotasi session/remember, pembatalan reset token, invalidasi session lama, view konfirmasi, dan `admin:create`.
- `vendor/bin/phpunit`: **55 tests passed, 413 assertions**, durasi runner 22.917 ms (22,917 detik). Seluruh regresi portal, admin CRUD, schema dan konten dinamis lulus.
- Pint dijalankan pada seluruh file PHP yang berubah. `--dirty` tidak dapat dipakai karena folder ini bukan Git repository; formatter kemudian dijalankan dengan daftar path eksplisit.
- Blade view cache, config cache, dan route cache berhasil dibuat. Config/route cache dibersihkan lagi untuk pengembangan.
- Satu percobaan suite sempat berbenturan dengan pembuatan config cache sehingga sebagian test mencoba MySQL dan ditolak sandbox. Pemeriksaan cache dihentikan sebelum pengulangan suite. `tests/TestCase.php` kini memeriksa environment testing, SQLite `:memory:`, dan DB URL kosong sebelum trait migration berjalan, agar kondisi konfigurasi salah gagal secara aman. Suite MySQL terisolasi pada tahap 15 harus memakai pengaman eksplisit tersendiri, bukan melepas proteksi ke database utama.
- Pemeriksaan ulang hanya-baca MySQL menunjukkan semua jumlah data tetap sama dengan snapshot tahap 1; kelima migration berstatus Ran, tanpa pending migration.
- Tidak membuat akun produksi atau mengubah `.env`. Tabel users MySQL masih kosong. Bootstrap tersedia melalui `php artisan admin:create EMAIL_ADMIN --name="NAMA ADMIN"` dengan prompt password; ganti placeholder dengan identitas pemilik. Jangan memakai email/password demo sebagai akun operasional.
- `MAIL_MAILER=log` tetap berlaku; tes memakai Notification fake. Pengiriman reset-link ke inbox email nyata menunggu konfigurasi SMTP. Log reset-link berisi token sensitif dan tidak boleh dibagikan.
- Pengujian browser/CSRF nyata, SMTP, worker, dan MySQL HTTP suite tetap bagian acceptance integrasi tahap 15.


## 14. Hasil tahap 3 — Authorization dan keamanan akun

Tanggal implementasi: 2 Oktober 2026. Laravel 13.34.0 dan Fortify 1.40.0 tetap digunakan; tidak ada dependency tambahan.

### Authorization

- `CmsContentPolicy` didaftarkan eksplisit untuk Company, Product, Pillar, ProcessStep, Inquiry, Site, Page, PageSection, SectionItem, Menu, MenuItem, dan Service. View/list/create/update/delete hanya diizinkan bagi `is_admin === true`; guest/nonadmin ditolak.
- Policy dipakai oleh seluruh aksi controller CMS yang saat ini tersedia, selain gate admin pada route. Tidak memakai global `Gate::before` yang dapat melewati penolakan policy.
- ID nested katalog/pilar/proses tetap diambil melalui relasi Company sebelum authorization. Admin pusat tetap dapat mengelola semua perusahaan, tetapi ID yang ditempatkan pada parent salah menghasilkan 404.
- Modul dinamis yang belum mempunyai controller telah memiliki pendaftaran policy; integrasinya ke endpoint dilakukan saat modul tersebut dibangun. Policy ini memutuskan siapa yang boleh bertindak. Aturan bisnis penghapusan holding/home/relasi pada kontrak tahap 1 tetap harus ditegakkan pada tahap masing-masing, bukan dianggap sudah terimplementasi oleh policy ini.
- Tidak menambah role, endpoint pengelolaan privilege, atau public registration. `is_admin` dan seluruh kolom 2FA tidak masuk fillable User. Payload user_id pada endpoint keamanan tidak dapat memilih akun lain.

### Konfirmasi password dan layar admin

- `/admin/account` menampilkan status dan tautan pengelolaan 2FA, tanpa QR, secret, atau recovery code.
- `/admin/account/two-factor` membutuhkan login admin dan konfirmasi password. Jendela konfirmasi default dipersingkat menjadi **900 detik (15 menit)**, dapat dikonfigurasi melalui `AUTH_PASSWORD_TIMEOUT`.
- Semua endpoint Fortify untuk mengaktifkan, mengonfirmasi, menonaktifkan, melihat QR/secret/recovery codes, serta regenerasi kode memakai password confirmation. Request JSON tanpa konfirmasi menerima 423.
- Update password tetap mewajibkan current password melalui action tahap 2. Penghapusan konten biasa tidak ditambah alur konfirmasi password pada tahap ini; scoping dan policy tetap berlaku.
- View setup menyediakan QR yang dihasilkan Fortify, kunci manual, form verifikasi TOTP, pembatalan setup, recovery code, regenerasi, dan tombol nonaktifkan. Tidak menggunakan layanan QR pihak ketiga.
- 2FA bersifat opt-in: status baru aktif setelah kode pertama benar. Setup yang belum dikonfirmasi tidak mengunci pengguna keluar dari login password biasa.

### Challenge login dan perlindungan secret

- User menggunakan trait `TwoFactorAuthenticatable`. Migration `2026_10_02_022953_add_two_factor_columns_to_users_table` menambahkan tiga kolom nullable: secret, recovery codes, dan confirmed_at. Migration berhasil diterapkan pada MySQL sebagai batch 3 tanpa menghapus data lama.
- Secret dan recovery codes dienkripsi oleh Fortify; tidak diberi encrypted cast tambahan agar tidak terjadi enkripsi ganda. Kedua field disembunyikan dari serialisasi model; confirmed_at dicast datetime.
- Setelah password benar, akun dengan 2FA aktif masih guest sampai challenge berhasil. Browser diarahkan ke `/two-factor-challenge`; form menerima kode autentikator atau recovery code.
- Challenge berlaku **10 menit**, session ID dirotasi setelah tahap password. Pemeriksaan ulang mewajibkan akun masih admin dan 2FA masih aktif. Fingerprint password/secret menolak challenge lama setelah perubahan password/secret. Login password baru membersihkan challenge sebelumnya, termasuk saat password baru salah.
- Fortify menggunakan recovery code sekali pakai dan mengganti kode yang telah digunakan. Regenerasi membatalkan seluruh daftar lama. Verifikasi POST dibungkus transaksi dan row lock akun untuk mencegah pemakaian recovery code bersamaan pada MySQL. Uji paralel MySQL tetap acceptance tahap 15; tes saat ini membuktikan pemakaian ulang berurutan ditolak.
- Rate limit challenge: 5/menit per akun+IP dan 20/menit per IP. Limiter berada di middleware global Fortify **sebelum** transaksi challenge; limiter bawaan `fortify.limiters.two-factor` dibuat null agar tidak ganda. Tes cache database membuktikan rollback validasi gagal tidak menghapus hitungan rate limit.
- Kode TOTP yang sudah diterima tidak dapat dipakai ulang melalui verifier/cache Fortify. Setup dan pengelolaan 2FA juga dibatasi oleh limiter request keamanan.
- Seluruh respons admin dan Fortify yang berhasil melewati pipeline menggunakan `Cache-Control: private, no-store`, `X-Robots-Tag: noindex, nofollow`, dan referrer same-origin.
- Kode TOTP/recovery/secret dikecualikan dari flashed input saat validasi gagal. Tidak menambahkan logging secret atau kode. Jangan membagikan APP_KEY, QR, atau recovery code; backup database terenkripsi tetap memerlukan pengamanan APP_KEY.
- Penonaktifan mengosongkan tiga kolom 2FA hanya pada akun sendiri. Perubahan password/reset tidak menghapus pengaturan 2FA. Tidak ada mekanisme bypass admin atau reset 2FA melalui email.

### Bukti dan batas verifikasi

- `SecurityTest`: **15 tes, 299 assertion lulus**. Mencakup policy 12 model, enforcement controller, batas akun, konfirmasi password kedaluwarsa, setup TOTP, enkripsi/serialisasi, pending setup, challenge, replay TOTP/recovery, regenerasi/disable, TTL, perubahan status akun/password, anti mass assignment, cache headers, dan limiter menggunakan cache database.
- Hasil suite lengkap dicatat setelah pemeriksaan akhir.
- `php artisan migrate:status --no-interaction`: keenam migration Ran, tidak ada migration tertunda.
- Route 2FA diverifikasi mengarah ke controller Fortify dan memakai middleware admin/password confirmation sesuai fungsinya.
- Pint dijalankan pada seluruh file PHP yang diubah. `--dirty` tidak tersedia karena folder bukan repository Git, sehingga formatter memakai path eksplisit.
- Akun operasional tetap tidak dibuat otomatis. Admin yang sudah dibuat lewat `admin:create` dapat mengaktifkan 2FA di Akun & keamanan → Kelola 2FA setelah login.
- Tes menggunakan SQLite terisolasi; pengiriman SMTP, pemindaian lewat perangkat autentikator nyata, browser acceptance/CSRF, dan stress test concurrency MySQL belum diklaim selesai. 2FA tidak memerlukan pengiriman email dan tetap opsional.
