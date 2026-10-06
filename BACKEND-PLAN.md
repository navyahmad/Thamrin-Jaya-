# Backend Plan — PT Thamrin Jaya Group

Tanggal audit: 2 Oktober 2026. Status diperbarui 6 Oktober 2026: **Tahap 1–15 selesai untuk implementasi dan verifikasi lokal; gate produksi masih terbuka (lihat bagian 26).**

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
| 4 | Invariant data dan registry | Satu holding/satu site per company, reserved slug, type/source/template whitelist, status item; migrasi additive; reseed tidak menduplikasi rename/mengembalikan delete | Selesai |
| 5 | Layanan media | Upload/replace/remove konsisten, file bersama aman, rollback dan cascade cleanup diuji dengan storage fake | Selesai |
| 6 | Company/site/gateway | Pilih konteks site; profil, branding, kontak, footer/social/SEO, urutan/status panel dapat dikelola; company baru membentuk site konsisten; proteksi delete | Selesai |
| 7 | Pages | CRUD halaman tambahan, empat halaman inti terlindungi, urutan/SEO/draft/publish, slug per site dan dampak menu diuji | Selesai |
| 8 | Katalog/pilar/proses | Refactor request/policy/media; create/edit/delete/order/status/filter/pagination sesuai modul; isolasi perusahaan; tanpa field transaksi | Selesai |
| 9 | Services | CRUD empat kelompok dan relasi perusahaan, urutan/status, target menu dan aturan penghapusan konsisten | Selesai |
| 10 | Sections | Form per type, sumber/variant tervalidasi, tambah/edit/delete/reorder/status, tipe tidak kompatibel ditolak | Selesai |
| 11 | Section items | Carousel/client/capacity/CSR/history variant/visi-misi/sertifikat memiliki form tepat; upload/PDF/status/order; tidak membuat klaim resmi palsu | Selesai |
| 12 | Navigasi | Header/footer/mega menu, target eksklusif, tree tanpa siklus, scope/depth/order, tautan invalid/draft difilter | Selesai |
| 13 | Resolver publik dan preview | Seluruh URL/content/theme/menu memakai data CMS, eager loading, status induk dipatuhi; preview aman; Blade minimum cukup untuk membuktikan alur | Selesai |
| 14 | Kontak dan inbox | Form holding/perusahaan masuk ke tujuan benar; inbox read/status/filter/delete; validasi/antispam; notifikasi opsional tidak mengorbankan penyimpanan | Selesai |
| 15 | Integrasi dan kesiapan operasi | Semua admin flow terhubung; suite SQLite dan MySQL terisolasi; pengujian HTTP/browser CSRF/auth/upload/publish; konfigurasi, queue, SMTP, backup/restore dan batas tersisa dicatat | Selesai lokal; gate produksi tercatat |

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

Rangkaian implementasi backend dan verifikasi lokal selesai. Berikutnya adalah desain frontend final per perusahaan serta penyelesaian gate produksi di bagian 26 sebelum deployment. Belum melakukan deployment atau menyatakan siap produksi.


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
- Verifikasi akhir `vendor/bin/phpunit`: **70 tests passed, 711 assertions**, durasi runner 15.306 ms (15,306 detik). Meliputi autentikasi, keamanan, CRUD admin, portal, migration dan struktur konten dinamis.
- `php artisan view:cache --no-interaction`: seluruh template Blade berhasil dikompilasi, termasuk pengaturan dan challenge 2FA. Pemeriksaan akhir tahap 3 tidak lagi pending.
- `php artisan migrate:status --no-interaction`: keenam migration Ran, tidak ada migration tertunda.
- Route 2FA diverifikasi mengarah ke controller Fortify dan memakai middleware admin/password confirmation sesuai fungsinya.
- Pint dijalankan pada seluruh file PHP yang diubah. `--dirty` tidak tersedia karena folder bukan repository Git, sehingga formatter memakai path eksplisit.
- Akun operasional tetap tidak dibuat otomatis. Admin yang sudah dibuat lewat `admin:create` dapat mengaktifkan 2FA di Akun & keamanan → Kelola 2FA setelah login.
- Tes menggunakan SQLite terisolasi; pengiriman SMTP, pemindaian lewat perangkat autentikator nyata, browser acceptance/CSRF, dan stress test concurrency MySQL belum diklaim selesai. 2FA tidak memerlukan pengiriman email dan tetap opsional.


## 15. Hasil tahap 4 — Konsistensi data dan registry

Tanggal implementasi: 2 Oktober 2026. Tidak menambah dependency atau mengubah `.env`.

### Constraint dan kepemilikan

- Migration `2026_10_02_141126_enforce_cms_data_invariants` berhasil diterapkan pada MySQL. Kolom generated `sites.holding_slot` bernilai 1 untuk holding dan NULL untuk anak perusahaan, dengan unique index. Database menolak holding kedua, termasuk insert yang tidak melalui Eloquent. Unique `sites.company_id` yang sudah ada tetap membatasi satu site per perusahaan.
- Migration memeriksa duplikasi holding sebelum melakukan DDL; tidak memilih atau menghapus holding secara otomatis jika data bermasalah.
- Model Site menjaga identitas holding `group`, melarang penghapusan holding melalui model, dan mengunci company_id serta slug internal setelah tersimpan. Slug/nama publik anak perusahaan diselesaikan oleh `resolvedSlug()`/`resolvedName()` dari Company; nilai historis di Site tidak menjadi sumber URL kedua.
- Kepemilikan Page→Site, Section→Page, Item→Section, Menu→Site, MenuItem→Menu, dan katalog/pilar/proses→Company tidak bisa diganti lewat save/update model biasa. Foreign key menu antar-site/antar-parent tetap berlaku.
- Empat slug halaman inti tidak dapat diganti atau dihapus melalui model. Halaman tambahan tetap dapat mengganti slug. Key section dan menu dikunci agar identitas anchor/slot tidak bergeser.
- Constraint database membatasi jumlah maksimal satu holding/satu site per company. Database tidak otomatis membuat site ketika company dibuat atau mengharuskan database kosong sudah punya holding. Penyediaan company+site secara lengkap lewat CRUD merupakan tahap 6; seeder tidak dipakai sebagai mekanisme memperbaiki site yang hilang.

### Slug dan registry

- `ContentRules` memusatkan format slug lowercase/dash, batas 100 karakter, daftar root reserved, dan pemeriksaan field immutable. Controller edit perusahaan menggunakan aturan yang sama dengan model; page slug `inquiry` dilindungi untuk endpoint sistem.
- `ContentRegistry` mengizinkan delapan template key: default, group-gateway, dan enam brand. Template holding/anak dibatasi konteks; font, warna hex, dan theme_settings tervalidasi. Input tidak dapat menentukan path Blade/class sembarangan.
- Enam belas jenis section memiliki sumber yang ditentukan konteks: company, manual, relations, items, atau contact_details. Varian yang saat ini didukung hanya `default`; menambahkan varian desain baru harus memperluas registry secara eksplisit.
- Section settings dibatasi pada source dan limit 1–100. Theme settings dibatasi container_width normal/wide dan button_style rounded/square. Item settings saat ini menerima icon dari allowlist; sertifikasi dapat menambah issuer. Field tambahan per jenis akan dikembangkan bersama formulir tahap 10–11, bukan menerima JSON bebas.
- Gateway hanya dapat dipakai holding. Section placeholder produk/pilar/proses milik holding yang sudah ada tetap dipertahankan nonaktif dan tidak boleh diaktifkan. Section yang membaca relasi tidak menerima SectionItem sebagai sumber data tandingan.
- Perubahan jenis/variant yang dapat merusak konten ditolak jika section sudah berisi item, body, gambar, atau CTA. Buat section baru untuk perubahan struktur tersebut.
- Validasi model melindungi jalur Eloquent, termasuk controller yang sudah ada. Bulk update/query builder/raw SQL tidak menjalankan event model; modul berikutnya wajib menggunakan jalur validasi ini dan FormRequest. Hanya constraint DB yang diklaim berlaku juga terhadap raw SQL.

### Status konten dan inisialisasi

- Products, pillars, process_steps mendapat `is_active` dengan default true, cast boolean, dan scope active. Data lama tetap terlihat. Portal lama kini memfilter item nonaktif; daftar admin tetap dapat mengaksesnya. Kontrol status pada form CRUD diselesaikan bersama tahap 8.
- Tabel teknis `content_initializations` menyimpan key inisialisasi serta completed_at; bukan konten perusahaan atau modul penjualan.
- `SeedOnce` menjalankan inisialisasi dalam transaksi dengan row lock dan penanda selesai. Kegagalan membatalkan data/penanda; percobaan berikutnya dapat mengulang. Setelah berhasil, seeder tidak menambahkan ulang data berdasarkan slug/key yang bisa berubah.
- `demo-companies-v1` membatasi inisialisasi enam perusahaan demo. `dynamic-content-v1` membatasi inisialisasi struktur site/pages/sections/items/menu/services/pivot.
- Migration mengadopsi data yang sudah ada dengan menandai inisialisasi terkait selesai. Tidak mengisi ulang kekosongan secara diam-diam. Pada database parsial yang sudah memiliki company/site, data diperlakukan sebagai hasil pengelolaan yang harus dipertahankan; perbaikan parsial memerlukan operasi yang disengaja dan ditinjau.
- DatabaseSeeder hanya berjalan pada environment local/testing. Untuk inisialisasi struktur tanpa perusahaan demo tersedia DynamicContentSeeder. Keduanya bukan perintah rutin deployment atau cara menambah perusahaan baru sesudah inisialisasi. Jangan menghapus penanda hanya untuk memaksa seed ulang.
- Fixture Site sekarang default anak perusahaan; fixture holding menggunakan state `group()`. Fixture section menggunakan carousel/items yang valid terhadap registry.

### Verifikasi

- `vendor/bin/phpunit`: **84 tes lulus, 779 assertion**, durasi runner 29.811 ms (29,811 detik). Termasuk 14 tes invariant baru, tes relasi/seeder sebelumnya, keamanan Fortify/2FA, portal, dan CRUD admin.
- Tes baru meliputi unique holding raw SQL, pemilik/halaman inti, slug reserved, template/settings ilegal, konteks section/item, perubahan jenis, reseed setelah rename/delete/detach, rollback inisialisasi, status katalog, larangan demo production, serta up/down/up migration additive pada koneksi SQLite terisolasi dengan data lama tetap utuh.
- `php artisan migrate --no-interaction`: migration tahap 4 DONE pada MySQL proyek. Tidak menjalankan migrate:fresh, rollback, ataupun seeder pada database utama.
- Pemeriksaan hanya-baca sesudah migration: companies 6, sites 7, pages 28, sections 112, items 6, menus 14, menu_items 112, services 4, pivot 6, products 18, pillars 24, process_steps 25, inquiries 0, users 0. Holding 1; kedua penanda inisialisasi berstatus initialized.
- Pint selesai pada perubahan PHP; `git diff --check` tidak menemukan kesalahan whitespace.
- Pengujian migration aktual MySQL dan suite SQLite berhasil. Uji race/concurrency MySQL yang lengkap tetap tahap 15. Renderer publik berbasis registry, seluruh form CMS, media lifecycle, dan enam desain final belum dinyatakan selesai oleh tahap ini.


## 16. Hasil tahap 5 — Pengelolaan media bersama

Tanggal implementasi: 2 Oktober 2026. Tidak menambah dependency, migration, atau mengubah `.env` dan data MySQL utama.

### Layanan dan integrasi

- `App\Actions\Cms\MediaManager` menjadi layanan save/upload/replace/remove/delete dan resolver URL bersama. CompanyController dan ContentController sekarang memakainya, setelah authorization dan validasi request.
- Upload banner/foto fasilitas/foto produk tetap melalui form yang sudah ada. Seluruh preview media admin dan portal lama memakai resolver yang sama; tidak lagi membentuk URL upload dengan menambahkan storage secara terpisah.
- Aset bawaan menggunakan path `images/nama-file` dan tidak disalin atau dihapus oleh CRUD. Pemilihan aset bawaan melalui layanan hanya menerima file yang benar-benar ada di public/images dengan nama/ekstensi aman. SVG hanya diperbolehkan untuk aset bawaan yang dipercaya, bukan upload.
- Upload baru disimpan dengan nama hash di `media/images` atau `media/documents` pada disk public. Path legacy `companies/...`, `products/...`, `storage/...`, dan `/storage/...` tetap dapat dibaca serta diperiksa referensinya.
- Resolver menolak URL eksternal/protocol-relative, traversal, backslash, encoded path, dan prefix di luar area media. Tidak menghapus file dengan path yang tidak dapat dinormalisasi sebagai upload.
- Gambar JPG/JPEG/PNG/WebP maksimal 5 MB. Dokumen PDF maksimal 10 MB hanya pada `SectionItem.file_path` milik section certifications. Form sertifikasi sendiri baru dibuat pada tahap 11.

### Transaksi dan kegagalan

- `save(record, attributes, uploads)` memiliki transaksi terluarnya sendiri pada koneksi default. Pemanggilan dari transaksi lain ditolak sebelum upload, agar rollback pemanggil tidak meninggalkan file tanpa record. Controller/model lain jangan membungkus layanan ini dalam DB::transaction tambahan.
- Untuk update, record dibaca ulang dengan row lock. File baru dicatat sebelum upaya penyimpanan; kegagalan storage/validasi/save DB membersihkan kandidat file baru dan membatalkan perubahan DB. Penyimpanan banyak file pada satu record bersifat satu operasi.
- Sesudah commit nyata, file lama diperiksa lalu dihapus hanya jika sudah tidak direferensikan. Tidak menghapus file lama sebelum commit atau menjalankan cleanup di dalam transaksi. Field media tidak boleh disisipkan langsung ke attributes; gunakan uploads berupa UploadedFile, null untuk hapus, atau path aset bawaan tervalidasi.
- Jika cleanup storage gagal setelah commit, perubahan data yang berhasil tetap dipertahankan; log mencatat path dan kebutuhan retry. Tidak menyatakan file sudah terhapus ketika storage menolak.
- `media:cleanup PATH` memeriksa satu path upload secara read-only. `media:cleanup PATH --delete` mengulang cleanup file yang tercatat gagal, dengan pemeriksaan referensi ulang. Aset bawaan dan path berbahaya ditolak; file yang masih dipakai dilewati. Tidak menjalankan perintah penghapusan ini pada data utama dalam tahap 5.
- Retry belum otomatis melalui queue. Operator dapat memakai perintah tersebut untuk path pada log setelah operasi selesai. Jangan menggunakannya sebagai pemindai upload yang masih berlangsung.

### Referensi dan cascade

- Referensi diperiksa pada Company banner/about, Product image, Site logo/favicon, Page OG image, PageSection image, SectionItem image/file, dan Service image. Semua bentuk path legacy yang didukung dibandingkan sebelum cleanup.
- `delete(record)` mengumpulkan path record dan turunannya sebelum penghapusan: Company→Site/Products, Site→Pages, Page→Sections, Section→Items. Ini menangani FK cascade yang tidak memicu event Eloquent anak. File yang juga dipakai di luar cabang tetap dipertahankan.
- Layanan tetap menjalankan event model dan constraint DB: penolakan penghapusan holding, atau company yang masih memiliki inquiry, tidak menghapus medianya.
- Modul baru wajib menggunakan layanan ini untuk operasi media dan penghapusan berjenjang. Raw SQL/delete model langsung tidak otomatis menjalankan lifecycle media; aturan bisnis penghapusan tetap tanggung jawab modul sesuai kontrak tahap 1.
- Upload harus direferensikan lewat kolom yang terdaftar di `MediaManager::COLUMNS`, bukan disisipkan sebagai path tersembunyi pada JSON/body. Tambahkan registry referensi jika ada kolom media baru. Uji race dengan penulis raw SQL eksternal tidak termasuk jaminan tahap ini.

### Verifikasi

- MediaTest: **14 tes, 52 assertion lulus**, menggunakan Storage fake. Meliputi path aman/legacy, aset bawaan, file bersama, rollback DB/multi-upload, FK restrict, cascade, PDF sertifikasi, transaksi bersarang, kegagalan cleanup, file terlalu besar, script berkedok gambar, retry command, dan kegagalan tulis storage.
- AdminTest menggunakan DatabaseMigrations pada SQLite terisolasi agar perilaku setelah commit benar-benar diuji, bukan transaksi pengujian luar yang selalu di-rollback. Pengaman tests/TestCase tetap melarang penggunaan database utama.
- Verifikasi akhir `vendor/bin/phpunit`: **98 tes lulus, 831 assertion**, durasi runner 45.039 ms (45,039 detik). Regresi autentikasi, 2FA, invariant data, portal, dan CRUD admin tetap lulus.
- Pint selesai; `git diff --check` bersih. `php artisan view:cache --no-interaction` berhasil mengompilasi seluruh Blade.
- Tidak membangun media library UI terpisah. Form CRUD yang tersedia telah terintegrasi; modul branding/section/sertifikat berikutnya menggunakan layanan yang sama.


## 17. Implementasi tahap 6 — Company/Site/Gateway CMS

Diselesaikan 4 Oktober 2026. Menggunakan tabel yang sudah tersedia; tidak membutuhkan migration atau seeding ulang.

- `/admin/sites` menyediakan daftar holding dan anak perusahaan. Editor situs menyediakan pilihan pindah konteks serta tautan ke profil perusahaan.
- Pengaturan Site: logo/favicon (upload, ganti, hapus melalui MediaManager), teks alternatif, pilihan template sesuai konteks, dua warna, font, lebar konten/bentuk tombol, footer, copyright, tautan sosial HTTP/HTTPS, dan SEO default.
- Kontak holding melalui contact_details tervalidasi. Identitas dan kontak anak perusahaan tetap pada Company. Permintaan memindah company_id, mengganti slug internal, menulis path media langsung, atau membuat editor nama/kontak tandingan ditolak, termasuk payload kosong.
- CompanyRequest memisahkan validasi dari controller. Profil, banner, urutan gateway, status, dan kontak tetap dapat diedit. Warna Company hanya dapat diedit untuk data lama tanpa Site; perusahaan dengan Site menggunakan editor warna Site.
- `/admin/companies/create` membuat Company nonaktif dan bukan demo, satu Site, empat halaman inti draft, serta navigasi header/footer dalam satu transaksi. Kegagalan pembuatan Site membatalkan Company. Tidak menyalin konten contoh atau data kontak rekaan. Upload dilanjutkan pada editor setelah pembuatan.
- Halaman baru belum memiliki section/item; editor komposisi disediakan tahap 10–11. Publikasi halaman dilakukan melalui tahap 7. Seeder bukan alat melengkapi ulang situs baru.
- DELETE Company hanya menerima perusahaan nonaktif tanpa Site, katalog, pilar, proses, layanan, atau inquiry. Guard dijalankan dalam transaksi MediaManager setelah row lock. DELETE Site ditolak untuk holding maupun anak perusahaan; gunakan nonaktifkan.
- Gateway, navigasi perusahaan, profil, dan endpoint inquiry menghormati status Company/Site serta Home draft jika Home tersedia. Situs holding nonaktif menyembunyikan root portal. Data legacy tanpa Site/Home masih didukung sampai resolver penuh tahap 13.
- Warna hero publik lama membaca Site dengan fallback Company. Penyimpanan template/footer/SEO/logo tidak berarti seluruh renderer dinamis atau enam desain publik sudah selesai; itu tetap lingkup tahap 13 dan pengerjaan frontend.

### Verifikasi tahap 6

- SiteManagementTest: 10 tes, 98 assertion lulus; meliputi pembuatan atomik/rollback, draft, kepemilikan, autentikasi/otorisasi, field internal kosong, validasi URL/template/media, lifecycle logo, perubahan slug, warna canonical, penolakan penghapusan, dan status publik.
- Pengujian memakai SQLite :memory: dan Storage fake. Database utama serta berkas logo asli tidak diubah oleh pengujian.
- Regresi lengkap `vendor/bin/phpunit`: **108 tes, 929 assertion lulus**. Pint, `git diff --check`, dan kompilasi seluruh Blade berhasil.


## 18. Implementasi tahap 7 — Pages CMS

Diselesaikan 4 Oktober 2026. Tidak ada perubahan skema maupun seeding ulang database utama.

- Daftar situs dan editor branding memiliki tautan ke `/admin/sites/{site}/pages`. Daftar halaman diurutkan berdasarkan sort_order lalu ID, menampilkan status, penanda halaman inti, jumlah section dan tautan menu langsung.
- Admin dapat membuat halaman tambahan (default draft), mengganti judul/slug, urutan, draft/publish, meta title, meta description, serta upload/ganti/hapus gambar Open Graph. Validasi melalui PageRequest dan media melalui MediaManager.
- Slug unik per Site, mengikuti batas 100 karakter dan format lowercase/dash. `inquiry` reserved. Empat slug inti tidak dapat dibuat ulang melalui form halaman tambahan, diganti, atau dihapus; judul, urutan, SEO, dan statusnya tetap dapat diedit.
- Nested resource memakai scoped route binding: edit/update/delete dengan pasangan Site/Page yang berbeda ditolak 404. Policy dan middleware tetap membatasi seluruh operasi pada admin. site_id dan path media langsung ditolak, termasuk nilai kosong.
- Perubahan slug mempertahankan ID sehingga target menu yang menggunakan page_id tidak terputus. URL lama tidak mendapat redirect otomatis; form menjelaskan konsekuensinya.
- Form hapus halaman tambahan menjelaskan cascade section/item, tautan menu langsung serta cabang menu turunannya. MediaManager membersihkan media turunannya setelah commit, mempertahankan file yang masih direferensikan di tempat lain. Halaman inti tetap ditolak oleh guard model di dalam transaksi.
- Home draft menyembunyikan root holding atau profil anak perusahaan. Untuk anak perusahaan, gateway/navigasi dan inquiry juga mengikuti pemeriksaan publikasi Home yang sudah ada. Admin tetap dapat mengakses editor untuk menerbitkan kembali.
- Pengaturan halaman, status dan metadata sudah tersimpan melalui CMS. Rendering halaman tambahan, fallback SEO pada renderer publik, filter menu publik, dan preview lengkap tetap tahap 13; editor komposisi section/item tetap tahap 10–11.

### Verifikasi tahap 7

- PageManagementTest: **8 tes, 130 assertion lulus**. Mencakup CRUD, urutan/status, slug per situs, proteksi semua halaman inti, akses lintas situs, validasi, akses guest/non-admin, lifecycle OG image, konflik upload/hapus, cascade section/item/menu, shared media, serta publish/unpublish holding dan anak perusahaan.
- Pengujian memakai database SQLite :memory: dengan pengaman TestCase dan Storage fake; tidak mereset database utama.
- Regresi lengkap `vendor/bin/phpunit`: **116 tes, 1.059 assertion lulus**. Route admin telah diperiksa; Pint, `git diff --check`, dan kompilasi seluruh Blade berhasil.


## 19. Implementasi tahap 8 — Katalog, pilar, dan proses

- Tiap perusahaan memiliki daftar terpisah `/admin/companies/{company}/{type}` untuk products, pillars, dan process-steps. Profil perusahaan menampilkan jumlah serta tautan pengelolaan, sehingga tidak memuat semua record sekaligus.
- Daftar menyediakan pencarian nama/judul/deskripsi, filter status aktif/nonaktif, pagination 15 record, urutan sort_order lalu ID, dan parameter filter yang dipertahankan saat pindah halaman. Produk juga memiliki filter kategori; pilihan kategori hanya diambil dari perusahaan terkait.
- ContentRequest memusatkan validasi create/update dan otorisasi record sesuai perusahaan. ContentFilterRequest memvalidasi pencarian/filter. ID perusahaan dan path media langsung tidak dapat disisipkan, termasuk nilai kosong; tipe modul yang tidak dikenal ditolak.
- Form create/edit memiliki checkbox aktif dan urutan 0–999. Checkbox create dicentang secara default; payload tanpa is_active disimpan nonaktif, mengikuti perilaku checkbox HTML. Konten nonaktif tetap dapat dikelola admin dan tidak muncul pada profil publik.
- Produk menyimpan nama, kategori, deskripsi, spesifikasi dan gambar; pilar/proses menyimpan judul dan deskripsi. Tidak ada field transaksi, harga, stok, pembayaran, atau kategori penjualan baru.
- Upload, penggantian, penghapusan gambar dan delete record tetap melalui MediaManager. Permintaan upload sekaligus hapus gambar ditolak agar maksud operasi jelas. Pilar/proses menolak field media/produk yang tidak sesuai modul.
- Setelah create/update/delete, admin kembali ke daftar modul terkait. Tombol kembali/batal dan navigasi antar modul tersedia. Form hapus tetap meminta konfirmasi dan dilindungi CSRF, policy serta scoped relationship.
- Tidak membutuhkan migration atau seeding ulang; menggunakan is_active dan scope active yang sudah tersedia sejak tahap 4.

### Verifikasi tahap 8

- ContentManagementTest dan AdminTest: **16 tes, 203 assertion lulus**, meliputi CRUD ketiga modul, urutan/status publik, filter/status/pagination per perusahaan, kategori lokal, penolakan payload pemindahan pemilik, otorisasi, dan lifecycle media.
- Regresi lengkap `vendor/bin/phpunit`: **122 tes, 1.191 assertion lulus**. Route CRUD diperiksa, Pint dan `git diff --check` bersih.
- Pengujian menggunakan SQLite :memory: dan Storage fake. Database utama tidak direset maupun diseed ulang.


## 20. Implementasi tahap 9 — Layanan dan relasi perusahaan

- Menu admin **Layanan & perusahaan** membuka `/admin/services`. CRUD tersedia untuk empat kelompok layanan yang sudah ada maupun layanan tambahan; tidak menjalankan ulang seeder atau mengganti data resmi.
- Daftar memiliki pencarian nama/deskripsi, filter aktif/nonaktif dan perusahaan, pagination 15 record, serta urutan sort_order lalu ID. Profil perusahaan menyediakan tautan langsung ke daftar layanan yang difilter untuk perusahaan tersebut.
- Form mengelola nama, slug unik, deskripsi, urutan, status, gambar, pilihan perusahaan penyedia, dan urutan tiap relasi. Satu perusahaan dapat memiliki beberapa layanan, dan satu layanan dapat menghubungkan beberapa perusahaan. Kedua arah relasi membaca sort_order pivot yang sama, sesuai skema yang sudah ada.
- ServiceRequest memvalidasi identitas, gambar, ID perusahaan yang ada, ID duplikat, boolean pilihan, struktur baris relasi, serta urutan 0–999. Tidak menerima image_path langsung. Upload dan hapus gambar sekaligus ditolak.
- MediaManager.save mendapat callback opsional afterSave yang berjalan **di dalam transaksi yang sama**, sesudah save dan sebelum commit. Callback hanya untuk penulisan relasi DB; tidak boleh melakukan efek samping eksternal atau mengubah media langsung. ServiceController memakainya untuk sync pivot, sehingga kegagalan guard/relasi membatalkan atribut, relasi, dan upload baru sekaligus.
- Melepas perusahaan dari layanan ditolak bila menu situs perusahaan tersebut masih menarget layanan. Pesan menjelaskan agar mengubah target menu terlebih dahulu atau menonaktifkan layanan. Editor menu sendiri tetap tahap 12. Relasi invalid yang telah ada pada seed tidak dinormalisasi otomatis di tahap ini.
- Mengganti slug layanan mempertahankan ID target menu. Hard delete melalui CMS ditolak ketika masih memiliki perusahaan **atau** item menu, termasuk item nonaktif. Guard dijalankan setelah row lock dalam transaksi MediaManager; file baru/lama tidak dihapus sebelum keputusan transaksi.
- Layanan tanpa relasi dapat dihapus; lifecycle gambar menggunakan pemeriksaan referensi dan cleanup setelah commit yang sudah tersedia. Status nonaktif dapat disimpan tanpa memutus relasi.
- Tidak ada migration baru. Renderer layanan/mega menu publik dan penyembunyian target yang tidak memenuhi syarat tetap diselesaikan pada tahap 12–13; tahap ini menyediakan CRUD, relasi, status dan proteksi data backend.

### Verifikasi tahap 9

- ServiceManagementTest + MediaTest: **20 tes, 104 assertion lulus**. Meliputi CRUD, urutan pivot, status, filter/pagination, otorisasi, validasi slug/perusahaan/gambar, target menu setelah rename, proteksi delete, rollback perubahan/upload saat relasi ditolak, serta lifecycle media bersama regresi layanan media.
- Database pengujian SQLite :memory: dan Storage fake; database utama tidak direset maupun diseed ulang.
- Regresi lengkap `vendor/bin/phpunit`: **128 tes, 1.243 assertion lulus**. Pint, `git diff --check`, pemeriksaan route admin, dan kompilasi Blade berhasil.


## 21. Implementasi tahap 10 — Page Sections CMS

- Editor halaman menyediakan tautan **Kelola section halaman**. Nested route `/admin/sites/{site}/pages/{page}/sections` menyediakan daftar, tambah, edit, hapus, serta reorder lengkap. Semua route menggunakan scoped binding Site → Page → Section dan policy admin.
- Key/anchor wajib unik per halaman, lowercase/dash, dan tetap setelah dibuat. Judul, subjudul, urutan 0–999, status aktif dan batas konten opsional 1–100 dapat diatur. Section baru dimulai nonaktif pada form.
- Pilihan type dibatasi registry dan konteks. Anak perusahaan tidak dapat membuat gateway; holding tidak dapat membuat products/pillars/process. Placeholder lama ketiga tipe pada holding tetap dapat dilihat/diedit tetapi tidak dapat diaktifkan. Variant saat ini hanya default sesuai registry; tidak menerima path template bebas.
- settings.source diturunkan server dari tipe dan konteks, bukan JSON bebas. Request menolak settings, page_id dan image_path langsung, termasuk nilai kosong.
- About/history holding menyediakan body teks biasa, gambar, alt, dan CTA opsional. URL CTA hanya HTTP/HTTPS atau anchor valid pada halaman yang sama. Upload JPG/PNG/WebP maksimal 5 MB; upload sekaligus hapus ditolak.
- Section sumber company, relations, contact_details, atau items tidak menerima body/gambar/CTA tandingan. UI menyediakan tautan ke profil/katalog, layanan, atau kontak sumber utama. Item manual baru dikelola pada tahap 11.
- Pilihan jenis dilakukan sebelum mengisi form. Pergantian type/variant ditolak bila section memiliki item atau body/gambar/CTA. Guard juga memeriksa nilai original sehingga tidak dapat dilewati dengan mengosongkan field dan mengganti tipe pada request yang sama. Tidak menghapus konten otomatis ketika berganti jenis.
- Reorder menerima list ID unik yang harus tepat mencakup semua section dari halaman tersebut. Validasi parent dan kelengkapan dilakukan kembali di dalam transaksi dengan row lock; tidak menerima subset, duplikat, array asosiatif atau ID halaman lain. Semua urutan disimpan atomik mulai dari nol.
- Delete section ditolak jika key/anchor masih dirujuk menu pada halaman tersebut. Sesudah referensi menu dilepas, penghapusan membawa item miliknya melalui MediaManager; cleanup media setelah commit mempertahankan file bersama.
- UI menjelaskan dampak perubahan pada halaman published dan dampak penghapusan item. Preview serta rendering penuh section publik tetap tahap 13. Tidak menambah skema atau menjalankan seeder pada database utama.

### Verifikasi tahap 10

- Pengujian mencakup CRUD manual holding, sumber otomatis anak perusahaan, validasi CTA/key/settings/variant/limit, lifecycle gambar, lintas situs/halaman, akses non-admin, proteksi type, reorder atomik, menu anchor, cascade item, media bersama dan placeholder holding.
- SQLite :memory: dan Storage fake digunakan untuk pengujian; database utama tidak direset.
- Regresi lengkap `vendor/bin/phpunit`: **135 tes, 1.345 assertion lulus**. Pint dan pemeriksaan route selesai; `git diff --check` bersih.


## 22. Implementasi tahap 11 — Section Items CMS

Diselesaikan 5 Oktober 2026.

- Editor section dengan sumber items menyediakan **Kelola item section**. Nested route Site → Page → Section → Item menggunakan scoped binding, policy dan autentikasi admin. Section sumber company/relations/contact_details tidak menyediakan CRUD item manual.
- Form dibuat dari allowlist field registry: carousel (subjudul/narasi/gambar/CTA), clients (logo/tautan), capacity (nilai/satuan/ikon), CSR (narasi/gambar/tanggal/CTA), vision_mission (key vision atau mission dan narasi), certifications (narasi/penerbit/tanggal/gambar/PDF).
- History tetap bersumber Company atau body manual holding sesuai registry tahap 10. Tidak menciptakan variant history-items yang belum didukung registry.
- Item baru nonaktif secara default. Judul wajib; key opsional unik dalam section, kecuali visi/misi yang wajib memakai vision/mission. Capacity wajib memiliki value; field tersebut bukan stok produk. Semua teks ditampilkan escaped pada admin.
- Request menolak field di luar tipe, pemindahan page_section_id, settings mentah, image_path dan file_path langsung. Issuer/icon disusun server menjadi settings tervalidasi. Tanggal memakai format Y-m-d.
- Tautan hanya HTTP/HTTPS atau #anchor section yang ada pada halaman yang sama; skema script, protocol-relative, path internal belum terselesaikan dan anchor hilang ditolak. Carousel/CSR mensyaratkan pasangan label dan URL bila CTA diisi. Referensi CTA lintas halaman dan migrasi CTA seed lama tetap mengikuti penyelesaian URL pada tahap 13; data seed tidak diubah otomatis.
- Gambar JPG/PNG/WebP maksimal 5 MB, PDF maksimal 10 MB khusus sertifikasi. Upload/ganti/hapus file melalui MediaManager. Tidak membuat data sertifikasi, klien, kapasitas atau CSR rekaan.
- Perbaikan aturan konflik upload/hapus memakai Rule::prohibitedIf dengan boolean request, sehingga true, integer 1 dan string "1" konsisten ditolak bila dikirim bersama file baru. Pola sama diperbaiki pada request konten, halaman, section dan layanan.
- Reorder mewajibkan list lengkap ID unik dari section yang sama, diverifikasi kembali dan disimpan atomik dalam transaksi dengan row lock. Urutan manual per item juga tersedia. Delete menghapus item di parent yang benar dan menjalankan cleanup media setelah commit, mempertahankan media yang masih digunakan.
- UI menjelaskan perubahan item aktif pada halaman published. Rendering item publik beserta filter status seluruh induknya dan preview tetap tahap 13; file dan data disiapkan melalui backend ini.
- Tidak ada migration atau seeding ulang database utama.

### Verifikasi tahap 11

- SectionItemManagementTest + MediaTest: **20 tes, 213 assertion lulus**. Meliputi form/CRUD seluruh tipe, status, key visi/misi, sumber relasional, isolasi parent, otorisasi, field/tautan terlarang, PDF palsu berdasarkan MIME aktual, batas ukuran, lifecycle dua media, konflik upload/hapus boolean, dan reorder lengkap.
- Pengujian memakai SQLite :memory: dan Storage fake, bukan database utama.
- Regresi lengkap `vendor/bin/phpunit`: **141 tes, 1.506 assertion lulus**. Pemeriksaan route, Pint, `git diff --check`, dan kompilasi seluruh Blade berhasil.


## 23. Implementasi tahap 12 — Menus CMS

Diselesaikan 5 Oktober 2026.

- Editor situs menyediakan **Kelola menu header/footer**. Slot menu hanya dapat diedit nama/statusnya; tidak menyediakan route create/delete atau pengubahan key/site_id. Item menu menggunakan nested scoped binding Site → Menu → Item, autentikasi admin dan policy.
- CRUD item meliputi label, key opsional unik dalam menu, bentuk link/mega_menu, parent, urutan, status, dan tab baru. Form menampilkan daftar item beserta nama induk dan jumlah anak; pindah induk melalui editor.
- Jenis target eksplisit page/service/url/heading. Hanya satu target boleh diisi; ketika jenis berubah, kolom target lama dikosongkan. Heading tidak menyimpan URL atau flag tab baru. Halaman harus berasal dari site yang sama, anchor harus ada pada halaman tersebut. Service anak perusahaan hanya boleh dipilih dari pivot perusahaan pemilik site; holding dapat mengelola target Service global.
- Target URL eksternal hanya HTTP/HTTPS. Tidak menerima javascript/data/protocol-relative atau string path internal; tautan internal memakai Page ID dan anchor. Draft/nonaktif boleh disiapkan dalam menu admin, dengan status ditampilkan pada pilihan. Pemfilteran target dan cabang publik dilakukan pada resolver tahap 13.
- MenuEditor menyimpan perubahan item dalam transaksi dengan lock menu dan item. Memeriksa seluruh pohon termasuk kedalaman turunannya: tidak ada siklus, parent beda menu, atau kedalaman lebih dari tiga tingkat. Memindahkan induk yang menyebabkan turunannya melampaui batas ditolak tanpa menyimpan perubahan.
- Reorder tersedia per kelompok saudara/parent. Mewajibkan list lengkap ID unik dari parent dan menu yang sama, diverifikasi di dalam transaksi lalu disimpan atomik. Pemindahan parent dilakukan lewat editor item dan tetap menjalankan validasi seluruh pohon.
- Item beranak tidak dapat dihapus; pindahkan/hapus anak terlebih dahulu. Sebagai penyelarasan proteksi menu, PageController kini menolak penghapusan halaman tambahan yang masih ditarget menu. Ini memperketat perilaku cascade yang diterapkan pada tahap 7; UI dan regresi halaman diperbarui. Setelah target dilepas, section/item/media halaman tetap dibersihkan melalui MediaManager.
- Seeder baru tetap mempertahankan record menu layanan tetapi menandai nonaktif target yang tidak terhubung perusahaan pemiliknya. Seeder lama tidak dijalankan ulang.
- Perintah `cms:normalize-menu-services` melakukan audit target layanan aktif pada situs anak tanpa perubahan; `--apply` hanya mengubah status target yang tidak sesuai menjadi nonaktif, mempertahankan label, ID, target, parent, dan urutan. Perintah idempotent dan telah diuji.
- Audit database utama menemukan **18 item** layanan tidak sesuai konteks. `cms:normalize-menu-services --apply --no-interaction` berhasil menonaktifkan **18 item** tersebut. Tidak menghapus konten, mengubah layanan/pivot, atau mereset database. Admin harus memilih target valid sebelum mengaktifkan ulang item tersebut.
- Belum mengganti navigasi publik lama dengan renderer menu dinamis. Penyembunyian cabang induk nonaktif, target draft/hilang, heading/mega menu kosong, pemilihan perusahaan publik untuk service holding, dan resolusi URL canonical diselesaikan bersama tahap 13.

### Verifikasi tahap 12

- MenuManagementTest, PageManagementTest, DynamicContentTest: **28 tes, 298 assertion lulus**. Meliputi CRUD seluruh target, slot tetap, akses admin, scoped binding, anchor, service perusahaan, URL aman, siklus/kedalaman subtree, delete beranak, reorder saudara, normalisasi idempotent, serta regresi halaman dan seed.
- Pengujian memakai SQLite :memory:, terpisah dari audit/normalisasi database utama.
- Regresi lengkap `vendor/bin/phpunit`: **147 tes, 1.575 assertion lulus**. Pemeriksaan route, Pint, `git diff --check`, dan kompilasi Blade berhasil. Audit ulang database utama melaporkan **0 target layanan aktif tidak sesuai konteks**.

## 24. Implementasi tahap 13 — Resolver publik dan preview

Diselesaikan 6 Oktober 2026.

- PublicContent dan DynamicPortalController menghubungkan Site → Page → Section → Item dengan renderer Blade. Urutan, status, sumber konten dan batas jumlah record mengikuti registry/CMS. Katalog, pilar dan proses tetap bersumber dari perusahaan pemilik halaman.
- URL holding memakai `/`, `/about-us`, `/services`, `/contact`, serta `/pages/{slug}` untuk halaman tambahan. Anak perusahaan memakai `/{company_slug}` dan `/{company_slug}/{page_slug}`. Alias Home diarahkan ke URL canonical.
- Akses publik mensyaratkan site aktif dan halaman published. Anak perusahaan juga wajib aktif serta memiliki Site dan Home published. Fallback legacy tanpa Site/Home dihentikan. Section/item nonaktif dan tipe atau sumber yang tidak sesuai konteks tidak dirender.
- Header/footer membaca pohon menu CMS. Cabang induk nonaktif, target draft/hilang, anchor tidak tersedia, URL tidak aman, heading kosong dan mega menu kosong disembunyikan. Layanan harus sesuai perusahaan dan tersedia pada section layanan publik; anchor layanan menyertakan key section dan ID service.
- CTA seed `#products` dan `#contact` diselesaikan ke section tujuan pada halaman services/contact dalam site yang sama. Target yang tidak tersedia tidak menjadi tautan. Tidak mengubah ulang data seed utama.
- Logo, favicon, warna, font yang diizinkan, footer, kontak dan metadata SEO membaca Site/Page. Template memakai allowlist. Implementasi menyediakan renderer fungsional dengan variasi dasar per brand; enam desain final tetap pekerjaan frontend tersendiri.
- Katalog menyediakan filter kategori dan dialog detail; gateway menyediakan dialog ringkasan perusahaan. JavaScript filter dibatasi ke katalog masing-masing dan menangani lebih dari satu menu.
- Tombol **Preview halaman** tersedia pada editor halaman. Route `/admin/sites/{site}/pages/{page}/preview` menggunakan autentikasi, policy dan scoped binding; preview draft/nonaktif diberi noindex/nofollow serta private/no-store. Form inquiry tidak ditampilkan di preview. Gateway dan layanan terkait tetap memakai kumpulan perusahaan publik, bukan seluruh draft perusahaan.
- Form holding menyediakan pilihan perusahaan publik; pengiriman tetap menuju inquiry perusahaan yang dipilih. Form perusahaan memakai tujuan dari route. Penyempurnaan inbox dan alur inquiry menjadi tahap 14.
- Resolver memakai eager loading serta cache selama request untuk kumpulan perusahaan, layanan dan section. Tidak ada perubahan dependency, migration, reset atau seeding ulang database utama pada tahap ini.

### Verifikasi tahap 13

- Regresi lengkap `vendor/bin/phpunit`: **156 tes, 1.704 assertion lulus**, diperiksa ulang 6 Oktober 2026 menggunakan database pengujian SQLite :memory:.
- PublicContentTest mencakup seluruh halaman inti tujuh site, URL canonical, isolasi konten, status publik, otorisasi dan header preview, menu tidak valid, branding/SEO, escaping, tujuan inquiry, serta pengurutan/batas item. Fixture pengujian lama disesuaikan agar memiliki Site/Home yang valid.
- Build frontend berhasil. Pint, kompilasi Blade dan `git diff --check` berhasil.
- Pemeriksaan screenshot Chrome lokal dilakukan pada portal desktop dan halaman layanan Globalindo ukuran ponsel. Ini pemeriksaan rendering dasar, bukan acceptance lengkap seluruh interaksi atau enam desain final.


## 25. Implementasi tahap 14 — Kontak dan inbox

Diselesaikan 6 Oktober 2026.

- StoreInquiryRequest memusatkan validasi nama/email/telepon/subjek/pesan, consent dan honeypot bertipe string. Hanya field pesan yang diteruskan ke penyimpanan; status/read_at dari pengunjung tidak digunakan. Tujuan form perusahaan berasal dari route, sementara form holding wajib memilih perusahaan yang tersedia publik.
- Rate limiter bernama inquiries membatasi lima percobaan per menit per IP secara bersama pada kedua endpoint, termasuk percobaan validasi gagal. Berpindah perusahaan atau memakai form holding tidak memberi kuota baru.
- Pesan berhasil dikirim melalui holding kembali ke halaman kontak holding. Form perusahaan kembali ke halaman kontak published, atau Home jika halaman kontak tidak tersedia. Anchor berasal dari section contact aktif yang benar-benar dirender, bukan string tetap. Penyimpanan tidak bergantung pada pengiriman email.
- InboxFilterRequest memvalidasi filter perusahaan, status tindak lanjut, sudah/belum dibaca, pencarian nama/email/subjek, dan halaman pagination. Daftar tetap memuat perusahaan nonaktif agar pesan historis dapat dikelola; pagination mempertahankan filter dan urutan memakai waktu serta ID.
- GET detail tidak lagi mengubah read_at. Tombol PATCH khusus menandai sudah/belum dibaca; penandaan dibaca berulang mempertahankan waktu baca pertama sampai ditandai belum dibaca. Status new/in_progress/resolved terpisah dari status baca.
- UpdateInquiryRequest membatasi mutasi pada status dan tindakan baca. Payload pengubahan identitas pengirim, tujuan perusahaan, isi pesan atau timestamp baca langsung ditolak. Detail, perubahan dan hapus tetap dilindungi autentikasi serta policy admin; pesan ditampilkan escaped.
- Form publik mempertahankan consent saat validasi gagal dan menampilkan kesalahan pemilihan perusahaan. Hapus pesan tetap memakai konfirmasi antarmuka dan DELETE dengan CSRF.
- Notifikasi email otomatis tidak diaktifkan karena bersifat opsional. Tombol balas membuka aplikasi email; tidak mengklaim SMTP atau worker telah diuji. Tidak ada migration, dependency baru, atau reset/seeding database utama.

### Verifikasi tahap 14

- InquiryManagementTest, PortalTest, AdminTest dan PublicContentTest: **33 tes, 307 assertion lulus**. Meliputi mutasi baca eksplisit/idempotent, pemisahan status, filter/pagination, akses guest/non-admin, batas pengiriman lintas endpoint/perusahaan, anchor dinamis, payload terlarang, honeypot, tujuan pesan, validasi dan penghapusan.
- Regresi lengkap `vendor/bin/phpunit`: **162 tes, 1.756 assertion lulus**, menggunakan SQLite :memory: yang terpisah dari database utama. Pint, kompilasi Blade, pemeriksaan route inbox dan `git diff --check` berhasil.


## 26. Implementasi tahap 15 — Integrasi dan kesiapan operasi

Diselesaikan 6 Oktober 2026 untuk lingkungan lokal. Tidak menjalankan deployment, mengubah `.env`, membuat akun di database utama, atau mereset database utama.

### Implementasi dan bukti

- Menambahkan `cms:check` dan `cms:check --production`: audit hanya-baca key tersedia, build manifest, izin storage/cache, symlink upload, koneksi database, migration dan admin. Mode production juga memeriksa environment, debug, HTTPS URL, cookie secure/HttpOnly, mailer serta penanda data demo. Exit code nonzero jika ada pemeriksaan gagal. Output tidak mencetak kredensial atau detail exception koneksi. Perintah ini pemeriksaan dasar, bukan sertifikasi kesiapan produksi.
- BackendIntegrationTest menjalankan login Fortify, upload melalui CMS, draft/preview/publish, logout, inquiry dan tindak lanjut inbox dalam satu alur. Middleware PreventRequestForgery tetap berjalan dengan bypass unit-test dinonaktifkan: request mutasi tanpa token/origin yang diterima menghasilkan 419. Media menggunakan Storage fake pada PHPUnit, dan DatabaseMigrations supaya lifecycle commit media benar-benar berjalan.
- TestCase tetap menolak database biasa. Default SQLite :memory: dipertahankan. Opt-in `CMS_TEST_MYSQL_SOCKET` hanya menerima socket nyata tanpa symlink di pola `/tmp/tj-cms-tests-<nama>/mysql.sock`, environment testing, database tetap `cms_testing`, pengguna tetap `cms_test`. URL/kredensial `.env` tidak dipakai oleh koneksi tes ini. Instance uji memakai skip-networking dan pengguna dibatasi ke database uji.
- **SQLite: 165 tes, 1.796 assertion lulus.** **Driver mysql pada MariaDB 11.8.6 terisolasi: 165 tes, 1.796 assertion lulus.** Oracle MySQL tidak terpasang dan tidak diklaim diuji. DynamicContentMigrationTest tetap memakai koneksi SQLite internal untuk skenario rollback legacy-nya; tes CMS lain memakai koneksi default yang dipilih.
- Chrome headless dengan profil baru, database uji dan storage `/tmp`: login admin melalui form berhasil; upload WebP melalui form katalog berhasil; nama produk uji tampil pada katalog publik; toggle Home draft menghasilkan HTTP 404 dan published menghasilkan 200. Ini smoke test browser, bukan acceptance setiap layar/perangkat. Serving gambar baru melalui symlink public/storage khusus storage sementara tidak termasuk bukti browser; persistensi dan pemulihan file diuji terpisah.
- Backup database demo melalui mysqldump dipulihkan ke `cms_restore` dalam instance sementara. Hasil: 23 tabel, 6 perusahaan, 19 produk (18 seed + 1 upload browser). Dump data sumber dan hasil restore cocok byte-per-byte; arsip media diekstrak ke direktori berbeda dan seluruh file cocok. Ini simulasi data uji, bukan backup/restore database utama atau backup offsite.
- Worker database dijalankan pada antrean uji kosong dan berhenti normal. Ini membuktikan startup worker dan akses antrean, bukan eksekusi job bisnis, supervisor produksi atau pengiriman email.
- `npm run build`, Pint, kompilasi Blade, route:cache/route:clear dan git diff --check berhasil. Route cache sengaja dikembalikan clear untuk pengembangan. Server aplikasi/browser/database sementara dihentikan setelah verifikasi.

### Audit lokal dan gate sebelum produksi

| Area | Bukti lokal / pekerjaan tersisa |
| --- | --- |
| Database utama | Terhubung; tujuh migration sudah Ran; tidak dimigrasi ulang pada tahap ini |
| Admin | Belum ada akun admin utama. Pemilik perlu menentukan email/nama dan menjalankan `php artisan admin:create email@domain --name="Nama Admin"` secara interaktif; tanpa password default |
| Konten | Enam perusahaan masih is_demo; verifikasi isi, gambar, alamat, kontak, klien dan sertifikasi sebelum menonaktifkan penanda demo |
| Environment | Masih local, debug aktif dan APP_URL HTTP. Di server tujuan gunakan production, debug false, URL HTTPS yang benar, serta session secure dan HttpOnly |
| Email | Mailer log; reset-password belum terbukti diterima lewat SMTP nyata. Konfigurasikan layanan yang dipilih dan uji penerimaan email pada alamat milik pemilik |
| Queue | Driver database; belum ada bukti supervisor/worker produksi. Jika memakai job async, kelola worker sebagai service, periksa retry/failed jobs, dan restart worker setelah rilis |
| Backup | Simulasi database/media uji berhasil. Tetapkan jadwal, retensi, enkripsi, akses terbatas dan penyimpanan terpisah; uji restore backup produksi ke lingkungan terisolasi, termasuk APP_KEY untuk data terenkripsi |
| Hosting | Belum memeriksa TLS/proxy, web root public, izin server, batas upload/request PHP dan web server, log/monitoring, atau domain produksi |
| Frontend | Renderer fungsional tersedia; enam desain final sesuai brand dan acceptance lengkap perangkat/peramban tetap pekerjaan terpisah |

### Urutan pemeriksaan ulang dan rilis

1. Lokal: `vendor/bin/phpunit` (SQLite). Jangan menjalankan suite dengan database utama. Untuk engine mysql, siapkan instance socket sementara skip-networking dan database/pengguna terbatas sebagaimana kontrak TestCase, lalu jalankan `CMS_TEST_MYSQL_SOCKET=/tmp/tj-cms-tests-<nama>/mysql.sock vendor/bin/phpunit`.
2. Jalankan `npm run build`, `vendor/bin/pint --dirty --format agent`, dan `php artisan cms:check --no-interaction`. Audit bisa gagal secara sengaja selama akun admin belum dibuat.
3. Sebelum rilis: siapkan environment tujuan, backup database + storage/app/public serta konfigurasi/key secara aman, verifikasi data resmi dan akses admin. Jangan menggunakan `composer setup`, migrate:fresh, migrate:refresh atau seeder demo sebagai prosedur update produksi; jangan mengganti APP_KEY instalasi yang sudah menyimpan data terenkripsi.
4. Di lingkungan tujuan setelah backup: pasang dependency sesuai lock file, build aset, jalankan migration additive yang telah ditinjau, siapkan storage link/izin, lalu cache konfigurasi/route/view. Jalankan `php artisan cms:check --production --no-interaction` dan periksa setiap kegagalan. Jangan membuat config cache produksi sebelum menjalankan tes lokal.
5. Uji login/logout/2FA, reset-password sampai email diterima, upload dan URL media, draft/publish/preview, menu tiap site, inquiry hingga inbox, pembatasan akses dan rate limit melalui HTTPS. Jalankan worker jika diperlukan dan pastikan pemantauan serta restore drill tersedia sebelum menyatakan siap produksi.
