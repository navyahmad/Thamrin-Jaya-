<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\SectionItem;
use App\Models\Service;
use App\Models\Site;
use Database\Seeders\DynamicContentSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DynamicContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_prepares_group_and_six_independent_company_sites(): void
    {
        $this->seed();
        $this->assertDatabaseCount('sites', 7);
        $this->assertDatabaseCount('pages', 28);
        $this->assertDatabaseCount('page_sections', 112);
        $this->assertDatabaseCount('section_items', 6);
        $this->assertDatabaseCount('menus', 14);
        $this->assertDatabaseCount('services', 4);
        $this->assertDatabaseCount('company_service', 6);
        $group = Site::where('slug', 'group')->firstOrFail();
        $this->assertNull($group->company_id);
        $this->assertSame('group-gateway', $group->template_key);
        $this->assertSame('gateway', $group->pages()->where('slug', 'home')->firstOrFail()->sections()->where('key', 'hero')->firstOrFail()->type);

        foreach (Company::all() as $company) {
            $this->assertTrue($company->site->company->is($company));
            $this->assertFileExists(public_path($company->site->logo_path));
            $this->assertSame($company->slug, $company->site->template_key);
            $this->assertSame(4, $company->site->pages()->count());
            $this->assertSame(1, $company->services()->count());
        }
        $this->assertSame(6, Site::whereNotNull('company_id')->distinct()->count('template_key'));
    }

    public function test_reseeding_does_not_overwrite_editor_changes_or_duplicate_content(): void
    {
        $this->seed();
        $site = Site::where('slug', 'globalindo')->firstOrFail();
        $site->update(['primary_color' => '#112233', 'footer_description' => 'Edited footer']);
        $page = $site->pages()->where('slug', 'home')->firstOrFail();
        $page->update(['title' => 'Edited title']);
        $section = $page->sections()->where('key', 'hero')->firstOrFail();
        $section->update(['title' => 'Edited hero', 'is_active' => false]);
        $item = $section->items()->firstOrFail();
        $item->update(['body' => 'Edited slide']);
        $menuItem = $site->menus()->where('key', 'header')->firstOrFail()->items()->where('key', 'home')->firstOrFail();
        $menuItem->update(['label' => 'Beranda', 'sort_order' => 9]);
        $counts = [];
        foreach (['sites', 'pages', 'page_sections', 'section_items', 'menus', 'menu_items', 'services', 'company_service'] as $table) {
            $counts[$table] = DB::table($table)->count();
        }
        $this->seed();
        foreach ($counts as $table => $count) {
            $this->assertDatabaseCount($table, $count);
        }
        $this->assertSame('Edited footer', $site->fresh()->footer_description);
        $this->assertSame('#112233', $site->fresh()->primary_color);
        $this->assertSame('Edited title', $page->fresh()->title);
        $this->assertSame('Edited hero', $section->fresh()->title);
        $this->assertFalse($section->fresh()->is_active);
        $this->assertSame('Edited slide', $item->fresh()->body);
        $this->assertSame('Beranda', $menuItem->fresh()->label);
        $this->assertSame(9, $menuItem->fresh()->sort_order);
    }

    public function test_same_page_slug_is_allowed_on_different_sites_but_not_same_site(): void
    {
        $first = Site::factory()->create();
        $second = Site::factory()->create();
        Page::factory()->for($first)->create(['slug' => 'home']);
        Page::factory()->for($second)->create(['slug' => 'home']);
        $this->assertSame(1, $first->pages()->count());
        $this->assertSame(1, $second->pages()->count());
        $this->expectException(QueryException::class);
        Page::factory()->for($first)->create(['slug' => 'home']);
    }

    public function test_one_company_cannot_have_duplicate_sites(): void
    {
        $company = Company::factory()->create();
        Site::factory()->for($company)->create();
        $this->expectException(QueryException::class);
        Site::factory()->for($company)->create();
    }

    public function test_sections_and_items_are_ordered_and_active_scopes_filter_drafts(): void
    {
        $page = Page::factory()->create();
        $later = PageSection::factory()->for($page)->create(['sort_order' => 9, 'is_active' => false]);
        $first = PageSection::factory()->for($page)->create(['sort_order' => 1]);
        $this->assertSame([$first->id, $later->id], $page->sections->modelKeys());
        $this->assertSame([$first->id], $page->sections()->active()->get()->modelKeys());
        $lastItem = SectionItem::factory()->for($first, 'section')->create(['sort_order' => 9, 'is_active' => false]);
        $firstItem = SectionItem::factory()->for($first, 'section')->create(['sort_order' => 1, 'settings' => ['icon' => 'factory'], 'occurred_on' => '2020-01-01']);
        $this->assertSame([$firstItem->id, $lastItem->id], $first->items->modelKeys());
        $this->assertSame([$firstItem->id], $first->items()->active()->get()->modelKeys());
        $this->assertSame(['icon' => 'factory'], $firstItem->fresh()->settings);
        $this->assertSame('2020-01-01', $firstItem->fresh()->occurred_on->toDateString());
        $this->assertTrue($firstItem->section->is($first));
        $this->assertSame(0, $page->site->pages()->published()->count());
    }

    public function test_nested_menu_relations_and_site_are_resolved(): void
    {
        $menu = Menu::factory()->create();
        $page = Page::factory()->for($menu->site)->create();
        $parent = MenuItem::factory()->for($menu)->create(['page_id' => $page->id, 'type' => 'mega_menu']);
        $child = MenuItem::factory()->for($menu)->create(['parent_id' => $parent->id]);
        $this->assertTrue($parent->children->sole()->is($child));
        $this->assertTrue($child->parent->is($parent));
        $this->assertTrue($child->site->is($menu->site));
        $this->assertTrue($parent->page->is($page));
        $this->assertTrue($page->menuItems->sole()->is($parent));
        $this->assertSame([$parent->id], $menu->rootItems->modelKeys());
        $this->assertSame(2, $menu->items->count());
    }

    public function test_cross_site_page_reference_is_rejected_by_database(): void
    {
        $menu = Menu::factory()->create();
        $otherPage = Page::factory()->create();
        $this->expectException(QueryException::class);
        MenuItem::factory()->for($menu)->create(['page_id' => $otherPage->id]);
    }

    public function test_cross_menu_parent_reference_is_rejected_by_database_even_without_models(): void
    {
        $first = MenuItem::factory()->create();
        $other = Menu::factory()->create();
        $this->expectException(QueryException::class);
        DB::table('menu_items')->insert([
            'menu_id' => $other->id, 'site_id' => $other->site_id,
            'parent_id' => $first->id, 'label' => 'Invalid child',
        ]);
    }

    public function test_menu_cannot_become_its_own_descendant(): void
    {
        $parent = MenuItem::factory()->create();
        $child = MenuItem::factory()->for($parent->menu)->create(['parent_id' => $parent->id]);
        $this->expectException(ValidationException::class);
        $parent->update(['parent_id' => $child->id]);
    }

    public function test_cascade_deletes_site_content_without_affecting_other_sites_or_company(): void
    {
        $company = Company::factory()->create();
        $site = Site::factory()->for($company)->create();
        $page = Page::factory()->for($site)->create();
        $section = PageSection::factory()->for($page)->create();
        $item = SectionItem::factory()->for($section, 'section')->create();
        $menu = Menu::factory()->for($site)->create();
        $menuItem = MenuItem::factory()->for($menu)->create(['page_id' => $page->id]);
        $other = PageSection::factory()->create();
        $site->delete();
        foreach ([$site, $page, $section, $item, $menu, $menuItem] as $record) {
            $this->assertModelMissing($record);
        }
        $this->assertModelExists($other);
        $this->assertModelExists($company);
    }

    public function test_service_can_belong_to_many_companies_without_duplicate_links(): void
    {
        $service = Service::factory()->create();
        $companies = Company::factory()->count(2)->create();
        $service->companies()->syncWithoutDetaching($companies->modelKeys());
        $service->companies()->syncWithoutDetaching($companies->modelKeys());
        $this->assertSame(2, $service->companies()->count());
        $this->assertTrue($companies->first()->services->sole()->is($service));
        $this->assertDatabaseCount('company_service', 2);
        $service->delete();
        $this->assertDatabaseCount('company_service', 0);
        $this->assertDatabaseCount('companies', 2);
    }

    public function test_company_contact_remains_authoritative_and_group_has_own_contact(): void
    {
        $company = Company::factory()->create(['phone' => '021123456']);
        $site = Site::factory()->for($company)->create(['contact_details' => ['phone' => 'stale-number']]);
        $this->assertSame('021123456', $site->resolvedContactDetails()['phone']);
        $group = Site::factory()->group()->create(['contact_details' => ['email' => 'group@example.test']]);
        $this->assertSame('group@example.test', $group->resolvedContactDetails()['email']);
    }

    public function test_unverified_sections_start_hidden(): void
    {
        $this->seed();
        $this->assertSame(0, PageSection::whereIn('type', ['clients', 'capacity', 'csr', 'vision_mission', 'certifications'])->active()->count());
    }

    public function test_dynamic_seeder_supports_existing_companies_without_changing_legacy_data(): void
    {
        $company = Company::factory()->create(['name' => 'Company edited by admin', 'about' => 'Existing content']);
        $this->seed(DynamicContentSeeder::class);
        $this->assertSame('Existing content', $company->fresh()->about);
        $this->assertSame('Company edited by admin', $company->site->name);
        $this->assertDatabaseCount('companies', 1);
        $this->assertDatabaseCount('sites', 2);
    }
}
