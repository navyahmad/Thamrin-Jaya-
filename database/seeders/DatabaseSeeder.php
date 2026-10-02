<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['globalindo', 'PT. Globalindo Thamrin Jaya', 'Globalindo', 'Offset & Packaging', 'Kemasan yang memberi kesan.', '#a64632', 'boxes', 'Solusi cetak offset dan kemasan untuk menerjemahkan identitas merek menjadi pengalaman yang berkesan.', ['Folding Carton', 'Paper Sleeve', 'Custom Packaging']],
            ['multipack', 'PT. Multipack Thamrin Jaya', 'Multipack', 'Kardus & Karton', 'Perlindungan di setiap perjalanan.', '#806346', 'carton', 'Kemasan kardus dan karton yang dirancang mengikuti kebutuhan penyimpanan, distribusi, dan presentasi produk.', ['Corrugated Box', 'Mailer Box', 'Karton Partisi']],
            ['hte-rotopack', 'PT. HTE Rotopack Indonesia', 'HTE Rotopack', 'Flexible Packaging', 'Fleksibel dalam bentuk. Kuat dalam fungsi.', '#567269', 'pouch', 'Pilihan kemasan fleksibel untuk beragam bentuk produk, dengan perhatian pada fungsi dan tampilan visual.', ['Standing Pouch', 'Roll Packaging', 'Sachet Packaging']],
            ['maxtech', 'Maxtech Solution Indonesia', 'Maxtech', 'Cigarette Machinery', 'Teknologi untuk langkah berikutnya.', '#52677a', 'machine', 'Solusi kebutuhan mesin dan komponen produksi bagi industri rokok, dari konsultasi hingga dukungan teknis.', ['Mesin Produksi', 'Spare Parts', 'Dukungan Teknis']],
            ['sinar-jaya', 'CV. Sinar Jaya', 'Sinar Jaya', 'Cigarette Materials', 'Material tepat. Hasil yang konsisten.', '#8a7449', 'rolls', 'Pilihan material pendukung industri rokok untuk melengkapi kebutuhan proses produksi Anda.', ['Cigarette Paper', 'Tipping Paper', 'Plug Wrap']],
            ['top-printing', 'Top Printing', 'Top Printing', 'Digital Printing', 'Dari ide menjadi impresi.', '#875d69', 'print', 'Layanan digital printing untuk kebutuhan komunikasi visual, materi promosi, dan cetak personalisasi.', ['Brosur & Katalog', 'Kartu Nama', 'Media Promosi']],
        ];
        foreach ($units as $index => [$slug, $name, $short, $sector, $tagline, $accent, $illustration, $summary, $products]) {
            $company = Company::firstOrCreate(['slug' => $slug], [
                'name' => $name, 'short_name' => $short, 'sector' => $sector, 'tagline' => $tagline,
                'accent' => $accent, 'illustration' => $illustration, 'summary' => $summary,
                'about' => $summary.' Kami menghubungkan kebutuhan bisnis dengan pendekatan produksi yang terencana, komunikasi yang terbuka, dan perhatian pada setiap detail.',
                'history' => 'Ruang ini disiapkan untuk cerita perjalanan '.$short.'. Tambahkan sejarah, tonggak pencapaian, dan informasi resmi perusahaan melalui CMS.',
                'sort_order' => $index + 1, 'is_active' => true, 'is_demo' => true,
            ]);
            if (! $company->wasRecentlyCreated) {
                continue;
            }
            foreach ($products as $productIndex => $product) {
                $company->products()->create([
                    'name' => $product, 'category' => $sector,
                    'description' => 'Contoh katalog '.$product.'. Diskusikan kebutuhan desain, material, dan jumlah dengan tim '.$short.'. Informasi ini merupakan konten demonstrasi.',
                    'specifications' => "Material: Dikonsultasikan sesuai kebutuhan\nDimensi: Disesuaikan dengan desain\nJumlah minimum: Hubungi tim penjualan\nCatatan: Spesifikasi contoh, perlu konfirmasi resmi",
                    'sort_order' => $productIndex + 1,
                ]);
            }
            foreach ([
                ['Kualitas', 'Perhatian pada detail dan pemeriksaan hasil di setiap tahap pekerjaan.'],
                ['Harga', 'Penawaran yang disusun sesuai kebutuhan, material, dan skala pesanan.'],
                ['Ketepatan Waktu', 'Perencanaan tahapan kerja dengan komunikasi jadwal yang jelas.'],
                ['Peralatan Produksi', 'Pemilihan proses dan peralatan sesuai karakteristik setiap pekerjaan.'],
            ] as $pillarIndex => [$title, $description]) {
                $company->pillars()->create(['title' => $title, 'description' => $description, 'sort_order' => $pillarIndex + 1]);
            }
            $steps = match ($slug) {
                'globalindo' => ['Internal Marketing', 'Design', 'Plate Making', 'Cetak Plate', 'Finishing & QC'],
                'multipack' => ['Konsultasi', 'Desain Struktur', 'Produksi Karton', 'Finishing & QC'],
                'hte-rotopack' => ['Konsultasi', 'Desain Kemasan', 'Printing & Laminasi', 'Converting & QC'],
                'maxtech' => ['Analisis Kebutuhan', 'Rekomendasi Solusi', 'Persiapan & Pengujian', 'Dukungan Teknis'],
                'sinar-jaya' => ['Konsultasi Material', 'Pemilihan Spesifikasi', 'Pemeriksaan Material', 'Pengiriman'],
                default => ['Konsultasi', 'Persiapan Desain', 'Digital Printing', 'Finishing & QC'],
            };
            foreach ($steps as $stepIndex => $title) {
                $company->processSteps()->create(['title' => $title, 'description' => 'Contoh tahap '.strtolower($title).'. Detail proses dapat disesuaikan melalui CMS berdasarkan alur kerja resmi.', 'sort_order' => $stepIndex + 1]);
            }
        }
        $this->call(DynamicContentSeeder::class);
    }
}
