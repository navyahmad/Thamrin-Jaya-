<?php

namespace Database\Seeders;

use App\Actions\Cms\SeedOnce;
use App\Models\Company;
use App\Models\Service;
use App\Models\Site;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DynamicContentSeeder extends Seeder
{
    public function run(): void
    {
        SeedOnce::run('dynamic-content-v1', function (): void {
            $services = collect([
                ['cigarette-material', 'Cigarette Material', ['sinar-jaya']],
                ['packaging-printing', 'Packaging Printing', ['globalindo', 'hte-rotopack', 'top-printing']],
                ['tobacco-machine', 'Tobacco Machine', ['maxtech']],
                ['carton-converting', 'Carton & Converting', ['multipack']],
            ])->mapWithKeys(function (array $definition, int $index): array {
                [$slug, $name, $companySlugs] = $definition;
                $service = Service::firstOrCreate(['slug' => $slug], ['name' => $name, 'sort_order' => $index + 1]);
                foreach (Company::whereIn('slug', $companySlugs)->get() as $company) {
                    DB::table('company_service')->insertOrIgnore([
                        'company_id' => $company->id, 'service_id' => $service->id,
                        'sort_order' => $index + 1, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }

                return [$slug => $service];
            });

            $group = Site::firstOrCreate(['slug' => 'group'], [
                'name' => 'PT Thamrin Jaya Group', 'template_key' => 'group-gateway',
                'primary_color' => '#13add8', 'secondary_color' => '#101820',
                'footer_description' => 'Menghubungkan keahlian. Menciptakan peluang bersama.',
                'copyright_text' => 'PT Thamrin Jaya Group',
                'seo_title' => 'Thamrin Jaya Group',
            ]);
            $this->seedPagesAndMenus($group, $services->all());

            $brands = [
                'globalindo' => ['globalindo.webp', '#f3ac18', '#242424'],
                'multipack' => ['multipack.webp', '#09add8', '#12354a'],
                'hte-rotopack' => ['hterootopack.webp', '#d71920', '#20282e'],
                'maxtech' => ['maxtech.webp', '#414b92', '#171e35'],
                'sinar-jaya' => ['sinarjaya.webp', '#00a9cd', '#df1234'],
                'top-printing' => ['topprintingjaya.webp', '#ed1b2f', '#15a6d1'],
            ];
            foreach (Company::orderBy('sort_order')->get() as $company) {
                $brand = $brands[$company->slug] ?? null;
                $site = Site::firstOrCreate(['company_id' => $company->id], [
                    'slug' => $company->slug, 'name' => $company->name,
                    'logo_path' => $brand ? 'images/'.$brand[0] : null,
                    'logo_alt' => 'Logo '.$company->name,
                    'template_key' => $brand ? $company->slug : 'default',
                    'primary_color' => $brand[1] ?? $company->accent,
                    'secondary_color' => $brand[2] ?? '#f7f5ef',
                    'footer_description' => $company->summary,
                    'copyright_text' => $company->name, 'seo_title' => $company->name,
                    'seo_description' => $company->summary,
                ]);
                $this->seedPagesAndMenus($site, $services->all());
            }
        });
    }

    /** @param array<string, Service> $services */
    private function seedPagesAndMenus(Site $site, array $services): void
    {
        $definitions = [
            'home' => ['Home', [
                ['hero', $site->company_id ? 'carousel' : 'gateway', 'Selamat datang', true],
                ['about', 'about', 'Tentang Kami', true],
                ['services', 'services', 'Our Service', true],
                ['clients', 'clients', 'Client', false],
                ['capacity', 'capacity', 'Capacity', false],
                ['csr', 'csr', 'CSR', false],
            ]],
            'about-us' => ['About Us', [
                ['history', 'history', 'History', true],
                ['vision-mission', 'vision_mission', 'Visi & Misi', false],
                ['certifications', 'certifications', 'Sertifikasi', false],
                ['the-group', 'group', 'The Group', true],
                ['pillars', 'pillars', 'Keunggulan Kami', (bool) $site->company_id],
            ]],
            'services' => ['Service', [
                ['services', 'services', 'Layanan Kami', true],
                ['products', 'products', 'Katalog Produk', (bool) $site->company_id],
                ['process', 'process', 'Proses Kami', (bool) $site->company_id],
            ]],
            'contact' => ['Contact', [
                ['map', 'map', 'Lokasi Kami', true],
                ['contact', 'contact', 'Hubungi Kami', true],
            ]],
        ];
        $pages = [];
        foreach ($definitions as $slug => [$title, $sections]) {
            $page = $site->pages()->firstOrCreate(['slug' => $slug], [
                'title' => $title, 'is_published' => true, 'sort_order' => count($pages) + 1,
            ]);
            $pages[$slug] = $page;
            foreach ($sections as $index => [$key, $type, $sectionTitle, $active]) {
                $source = match ($type) {
                    'about', 'history', 'products', 'pillars', 'process' => $site->company_id ? 'company' : 'manual',
                    'services', 'gateway', 'group' => 'relations',
                    'map', 'contact' => 'contact_details',
                    default => 'items',
                };
                $section = $page->sections()->firstOrCreate(['key' => $key], [
                    'type' => $type, 'title' => $sectionTitle, 'sort_order' => $index + 1,
                    'is_active' => $active, 'settings' => ['source' => $source],
                ]);
                if ($type === 'carousel' && $site->company) {
                    $section->items()->firstOrCreate(['key' => 'primary-slide'], [
                        'title' => $site->company->tagline, 'body' => $site->company->summary,
                        'image_path' => $site->company->banner_path ? 'storage/'.$site->company->banner_path : null,
                        'image_alt' => $site->company->name,
                        'link_label' => 'Jelajahi produk', 'link_url' => '#products', 'sort_order' => 1,
                    ]);
                }
            }
        }

        foreach (['header' => 'Navigasi Utama', 'footer' => 'Navigasi Footer'] as $key => $name) {
            $menu = $site->menus()->firstOrCreate(['key' => $key], ['name' => $name]);
            foreach ($pages as $slug => $page) {
                $item = $menu->items()->firstOrCreate(['key' => $slug], [
                    'page_id' => $page->id, 'label' => $page->title,
                    'type' => $slug === 'services' && $key === 'header' ? 'mega_menu' : 'link',
                    'sort_order' => $page->sort_order,
                ]);
                if ($key === 'header' && $slug === 'services') {
                    foreach (array_values($services) as $index => $service) {
                        $menu->items()->firstOrCreate(['key' => 'service-'.$service->slug], [
                            'parent_id' => $item->id, 'service_id' => $service->id,
                            'label' => $service->name, 'sort_order' => $index + 1,
                        ]);
                    }
                }
                if ($key === 'header' && $slug === 'about-us') {
                    foreach (['history' => 'History', 'vision-mission' => 'Visi & Misi', 'certifications' => 'Sertifikasi', 'the-group' => 'The Group'] as $anchor => $label) {
                        $menu->items()->firstOrCreate(['key' => 'about-'.$anchor], [
                            'parent_id' => $item->id, 'page_id' => $page->id, 'anchor' => $anchor,
                            'label' => $label, 'is_active' => ! in_array($anchor, ['vision-mission', 'certifications']),
                        ]);
                    }
                }
            }
        }
    }
}
