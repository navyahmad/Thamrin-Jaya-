<?php
namespace App\Actions\Cms;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class CreateCompanyWithSite
{
    public function create(array $attributes): Company
    {
        return DB::transaction(function () use ($attributes): Company {
            $company = Company::create([...$attributes, 'is_active' => false, 'is_demo' => false]);
            $site = $company->site()->create([
                'slug' => 'company-'.Str::uuid(), 'name' => $company->name,
                'template_key' => 'default', 'primary_color' => $company->accent ?? '#2563eb',
                'secondary_color' => '#101820', 'font_family' => 'Inter', 'is_active' => true,
            ]);
            $pages = [];
            foreach (['home' => 'Home', 'about-us' => 'About Us', 'services' => 'Service', 'contact' => 'Contact'] as $slug => $title) {
                $pages[] = $site->pages()->create(['slug' => $slug, 'title' => $title, 'is_published' => false, 'sort_order' => count($pages) + 1]);
            }
            foreach (['header' => 'Navigasi Utama', 'footer' => 'Navigasi Footer'] as $key => $name) {
                $menu = $site->menus()->create(['key' => $key, 'name' => $name]);
                foreach ($pages as $page) {
                    $menu->items()->create(['key' => $page->slug, 'page_id' => $page->id, 'label' => $page->title, 'type' => 'link', 'sort_order' => $page->sort_order]);
                }
            }
            return $company;
        });
    }
}
