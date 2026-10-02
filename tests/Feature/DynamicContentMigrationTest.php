<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DynamicContentMigrationTest extends TestCase
{
    public function test_new_migration_can_rollback_without_deleting_legacy_portal_data(): void
    {
        $original = DB::getDefaultConnection();
        config(['database.connections.migration_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('migration_test');
        try {
            (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
            (require database_path('migrations/2026_09_29_133758_create_portal_tables.php'))->up();
            $companyId = DB::table('companies')->insertGetId([
                'name' => 'Existing Company', 'short_name' => 'Existing', 'slug' => 'existing',
                'sector' => 'Packaging', 'summary' => 'Existing summary', 'tagline' => 'Original tagline',
            ]);
            DB::table('products')->insert([
                'company_id' => $companyId, 'name' => 'Existing Product', 'category' => 'Packaging', 'description' => 'Original description',
            ]);
            $migration = require glob(database_path('migrations/*create_dynamic_content_tables.php'))[0];
            $migration->up();
            $this->assertTrue(Schema::hasTable('sites'));
            $this->assertTrue(Schema::hasTable('menu_items'));
            $this->assertSame('Existing Product', DB::table('products')->sole()->name);
            $migration->down();
            foreach (['sites', 'pages', 'page_sections', 'section_items', 'menus', 'menu_items', 'services', 'company_service'] as $table) {
                $this->assertFalse(Schema::hasTable($table));
            }
            $this->assertSame('Existing Company', DB::table('companies')->sole()->name);
            $this->assertSame('Original description', DB::table('products')->sole()->description);
            $migration->up();
            $this->assertTrue(Schema::hasTable('sites'));
        } finally {
            DB::setDefaultConnection($original);
            DB::purge('migration_test');
        }
    }
}
