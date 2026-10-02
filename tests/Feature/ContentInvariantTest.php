<?php

namespace Tests\Feature;

use App\Actions\Cms\ContentRegistry;
use App\Actions\Cms\SeedOnce;
use App\Models\Company;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Product;
use App\Models\SectionItem;
use App\Models\Service;
use App\Models\Site;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class ContentInvariantTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_rejects_second_holding_even_without_eloquent(): void
    {
        Site::factory()->group()->create();
        $this->expectException(QueryException::class);
        DB::table('sites')->insert(['company_id' => null, 'slug' => 'other', 'name' => 'Other']);
    }

    public function test_holding_identity_and_ownership_are_protected(): void
    {
        $group = Site::factory()->group()->create();
        $site = Site::factory()->create();
        $other = Company::factory()->create();
        foreach ([fn () => $group->delete(), fn () => $site->update(['company_id' => $other->id]), fn () => $group->update(['slug' => 'other'])] as $change) {
            try {
                $change();
                $this->fail('Protected change was accepted.');
            } catch (ValidationException) {
                $this->assertModelExists($group);
            }
        }
    }

    public function test_reserved_company_slugs_are_rejected_through_admin_form(): void
    {
        $company = Company::factory()->create();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $data = $company->only(['name', 'short_name', 'sector', 'summary', 'tagline', 'accent', 'illustration', 'sort_order']);
        foreach (['about-us', 'services', 'contact', 'pages', 'inquiries', 'user', 'group', 'api', '../admin'] as $slug) {
            $this->put(route('admin.companies.update', $company), [...$data, 'slug' => $slug])->assertSessionHasErrors('slug');
        }
        $this->assertSame($company->slug, $company->fresh()->slug);
    }

    public function test_core_pages_are_locked_but_custom_page_slugs_can_change(): void
    {
        $site = Site::factory()->create();
        $home = Page::factory()->for($site)->create(['slug' => 'home']);
        $custom = Page::factory()->for($site)->create(['slug' => 'news']);
        $custom->update(['slug' => 'articles']);
        $this->assertSame('articles', $custom->fresh()->slug);
        foreach ([fn () => $home->update(['slug' => 'old-home']), fn () => $home->fresh()->delete(), fn () => $custom->update(['site_id' => Site::factory()->create()->id])] as $change) {
            try {
                $change();
                $this->fail('Protected change was accepted.');
            } catch (ValidationException) {
                $this->assertModelExists($home);
            }
        }
    }

    public function test_template_and_theme_payloads_cannot_select_arbitrary_code(): void
    {
        foreach ([['template_key' => '../admin'], ['theme_settings' => ['view' => 'admin.account']], ['font_family' => 'url(javascript:alert(1))']] as $input) {
            try {
                Site::factory()->create($input);
                $this->fail('Unsafe theme was accepted.');
            } catch (ValidationException) {
                $this->assertDatabaseCount('sites', 0);
            }
        }
        $this->assertCount(8, ContentRegistry::TEMPLATES);
    }

    public function test_registry_rejects_unknown_types_sources_variants_and_settings(): void
    {
        $page = Page::factory()->create();
        foreach ([['type' => 'unknown'], ['variant' => '../template'], ['settings' => ['source' => 'company']], ['settings' => ['source' => 'items', 'class' => 'App\\Models\\User']]] as $input) {
            try {
                PageSection::factory()->for($page)->create($input);
                $this->fail('Invalid section was accepted.');
            } catch (ValidationException) {
                $this->assertDatabaseCount('page_sections', 0);
            }
        }
    }

    public function test_holding_cannot_activate_legacy_company_section_and_subsidiary_cannot_use_gateway(): void
    {
        $group = Site::factory()->group()->create();
        $page = Page::factory()->for($group)->create();
        $section = PageSection::factory()->for($page)->create(['type' => 'products', 'settings' => ['source' => 'manual'], 'is_active' => false]);
        try {
            $section->update(['is_active' => true]);
            $this->fail('Holding catalog was activated.');
        } catch (ValidationException) {
            $this->assertFalse($section->fresh()->is_active);
        }
        $this->expectException(ValidationException::class);
        PageSection::factory()->create(['type' => 'gateway', 'settings' => ['source' => 'relations']]);
    }

    public function test_items_cannot_be_attached_to_company_sources_or_change_parent(): void
    {
        $section = PageSection::factory()->create(['type' => 'products', 'settings' => ['source' => 'company']]);
        try {
            SectionItem::factory()->for($section, 'section')->create();
            $this->fail('Duplicate catalog source accepted.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('section_items', 0);
        }
        $item = SectionItem::factory()->create();
        $this->expectException(ValidationException::class);
        $item->update(['page_section_id' => PageSection::factory()->create()->id]);
    }

    public function test_existing_content_blocks_destructive_section_type_change(): void
    {
        $item = SectionItem::factory()->create();
        $this->expectException(ValidationException::class);
        $item->section->update(['type' => 'clients']);
    }

    public function test_reseed_does_not_resurrect_deleted_or_renamed_records(): void
    {
        $this->seed();
        $company = Company::where('slug', 'globalindo')->firstOrFail();
        $company->update(['slug' => 'globalindo-baru']);
        $service = Service::firstOrFail();
        $service->update(['slug' => 'layanan-baru']);
        $company->services()->detach();
        $company->site->pages()->where('slug', 'home')->firstOrFail()->sections()->where('key', 'clients')->firstOrFail()->delete();
        SectionItem::firstOrFail()->delete();
        Company::where('slug', 'multipack')->firstOrFail()->delete();
        $counts = [];
        foreach (['companies', 'sites', 'pages', 'page_sections', 'section_items', 'menus', 'menu_items', 'services', 'company_service'] as $table) {
            $counts[$table] = DB::table($table)->count();
        }
        $this->seed();
        foreach ($counts as $table => $count) {
            $this->assertDatabaseCount($table, $count);
        }
        $this->assertDatabaseMissing('companies', ['slug' => 'globalindo']);
        $this->assertDatabaseMissing('companies', ['slug' => 'multipack']);
        $this->assertSame('globalindo-baru', $company->site->resolvedSlug());
        $this->assertDatabaseCount('content_initializations', 2);
    }

    public function test_failed_initialization_rolls_back_and_can_be_retried(): void
    {
        try {
            SeedOnce::run('test-init', function (): void {
                Company::factory()->create();
                throw new RuntimeException('Interrupted');
            });
        } catch (RuntimeException) {
            $this->assertDatabaseCount('companies', 0);
            $this->assertDatabaseMissing('content_initializations', ['key' => 'test-init']);
        }
        SeedOnce::run('test-init', function (): void {
            Company::factory()->create();
        });
        SeedOnce::run('test-init', function (): void {
            Company::factory()->create();
        });
        $this->assertDatabaseCount('companies', 1);
    }

    public function test_inactive_catalog_items_are_hidden_publicly_but_retained_for_admin(): void
    {
        $product = Product::factory()->create(['name' => 'Hidden unique product', 'is_active' => false]);
        $this->get(route('company.show', $product->company->slug))->assertOk()->assertDontSee('Hidden unique product');
        $this->assertSame(0, $product->company->products()->active()->count());
        $this->assertSame(1, $product->company->products()->count());
    }

    public function test_additive_migration_adopts_existing_data_and_rolls_back_without_losing_content(): void
    {
        $original = DB::getDefaultConnection();
        config(['database.connections.invariant_migration_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true]]);
        DB::setDefaultConnection('invariant_migration_test');
        try {
            foreach (['0001_01_01_000000_create_users_table.php', '2026_09_29_133758_create_portal_tables.php', '2026_10_01_144643_create_dynamic_content_tables.php'] as $file) {
                (require database_path('migrations/'.$file))->up();
            }
            $company = DB::table('companies')->insertGetId(['slug' => 'existing', 'name' => 'Existing', 'short_name' => 'Existing', 'sector' => 'Packaging', 'summary' => 'Unchanged', 'tagline' => 'Original']);
            DB::table('sites')->insert(['slug' => 'group', 'name' => 'Existing holding']);
            DB::table('products')->insert(['company_id' => $company, 'name' => 'Original product', 'category' => 'Boxes', 'description' => 'Original description']);
            $migration = require database_path('migrations/2026_10_02_141126_enforce_cms_data_invariants.php');
            $migration->up();
            $this->assertSame(2, DB::table('content_initializations')->whereNotNull('completed_at')->count());
            $this->assertEquals(1, DB::table('products')->sole()->is_active);
            $this->assertSame('Unchanged', DB::table('companies')->sole()->summary);
            $migration->down();
            $this->assertSame('Original product', DB::table('products')->sole()->name);
            $this->assertSame('Existing holding', DB::table('sites')->sole()->name);
            $migration->up();
            $this->assertSame(1, DB::table('sites')->count());
        } finally {
            DB::setDefaultConnection($original);
            DB::purge('invariant_migration_test');
        }
    }

    public function test_demo_seeder_is_not_available_in_production(): void
    {
        $this->app['env'] = 'production';
        try {
            app(DatabaseSeeder::class)->run();
            $this->fail('Demo seeding was allowed in production.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('local/testing', $exception->getMessage());
            $this->assertDatabaseCount('companies', 0);
        } finally {
            $this->app['env'] = 'testing';
        }
    }
}
