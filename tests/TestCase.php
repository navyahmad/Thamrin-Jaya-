<?php

namespace Tests;

use App\Actions\Cms\ContentRegistry;
use App\Models\Company;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Site;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();

        $socket = getenv('CMS_TEST_MYSQL_SOCKET');
        if ($socket !== false && $socket !== '') {
            if (! $app->environment('testing')
                || ! preg_match('~^/tmp/tj-cms-tests-[a-z0-9-]+/mysql\.sock$~D', $socket)
                || realpath($socket) !== $socket || filetype($socket) !== 'socket') {
                throw new RuntimeException('MySQL tests require a dedicated temporary CMS socket.');
            }
            $app['config']->set('database.default', 'mysql');
            $app['config']->set('database.connections.mysql', [
                'driver' => 'mysql', 'unix_socket' => $socket, 'database' => 'cms_testing',
                'username' => 'cms_test', 'password' => '', 'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci', 'prefix' => '', 'strict' => true,
            ]);
        } elseif (! $app->environment('testing')
            || $app['config']->get('database.default') !== 'sqlite'
            || $app['config']->get('database.connections.sqlite.database') !== ':memory:'
            || ! empty($app['config']->get('database.connections.sqlite.url'))) {
            throw new RuntimeException('Tests require SQLite :memory: or an explicitly isolated CMS MySQL socket. Clear configuration cache before testing.');
        }

        return $app;
    }

    protected function publishSite(?Company $company = null): Site
    {
        $site = $company ? $company->site()->first() : Site::whereNull('company_id')->first();
        $site ??= $company
            ? Site::factory()->create(['company_id' => $company->id])
            : Site::factory()->group()->create();
        $home = $site->pages()->where('slug', 'home')->first();
        if (! $home) {
            $home = Page::factory()->for($site)->create(['slug' => 'home', 'title' => 'Home', 'is_published' => true]);
            foreach ($company ? ['about', 'products', 'pillars', 'process', 'contact', 'map'] : ['gateway'] as $type) {
                PageSection::factory()->for($home)->create([
                    'key' => $type, 'type' => $type, 'body' => null,
                    'settings' => ['source' => ContentRegistry::source($type, $company !== null)],
                ]);
            }
        }

        return $site;
    }
}
