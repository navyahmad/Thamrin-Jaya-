<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class MenuManagementTest extends TestCase
{
    use DatabaseMigrations;

    private function admin(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    private function data(): array
    {
        return ['label' => 'Menu baru', 'type' => 'link', 'target' => 'heading', 'sort_order' => 1, 'is_active' => true];
    }

    private function url(string $action, Menu $menu, ?MenuItem $item = null): string
    {
        return route('admin.sites.menus.items.'.$action, array_filter([$menu->site, $menu, $item]));
    }

    public function test_menu_slots_and_item_crud_with_all_targets(): void
    {
        $menu = Menu::factory()->create(['key' => 'header']);
        $page = Page::factory()->create(['site_id' => $menu->site_id]);
        $section = PageSection::factory()->for($page)->create();
        $service = Service::factory()->create();
        $service->companies()->attach($menu->site->company_id);
        $this->admin();
        $this->get(route('admin.sites.menus.index', $menu->site))->assertOk();
        $this->get($this->url('create', $menu))->assertOk();
        $this->put(route('admin.sites.menus.update', [$menu->site, $menu]), ['name' => 'Utama'])->assertSessionHasNoErrors();
        $this->assertFalse($menu->fresh()->is_active);
        $this->put(route('admin.sites.menus.update', [$menu->site, $menu]), ['name' => 'Utama', 'key' => 'other'])->assertSessionHasErrors('key');
        $this->delete(route('admin.sites.menus.update', [$menu->site, $menu]))->assertStatus(405);
        foreach ([[], ['target' => 'page', 'page_id' => $page->id, 'anchor' => $section->key], ['target' => 'service', 'service_id' => $service->id], ['target' => 'url', 'url' => 'https://example.com']] as $target) {
            $this->post($this->url('store', $menu), [...$this->data(), ...$target])->assertSessionHasNoErrors();
        }
        $this->get(route('admin.sites.menus.edit', [$menu->site, $menu]))->assertOk();
        $item = $menu->items()->whereNotNull('page_id')->firstOrFail();
        $this->get($this->url('edit', $menu, $item))->assertOk();
        $this->put($this->url('update', $menu, $item), $this->data())->assertSessionHasNoErrors();
        $this->assertNull($item->fresh()->page_id);
        $this->assertNull($item->fresh()->anchor);
        $this->delete($this->url('destroy', $menu, $item))->assertSessionHasNoErrors();
        $this->assertModelMissing($item);
    }

    public function test_invalid_targets_and_cross_context_are_rejected(): void
    {
        $menu = Menu::factory()->create();
        $other = Menu::factory()->create();
        $foreignItem = MenuItem::factory()->for($other)->create();
        $page = Page::factory()->create();
        $service = Service::factory()->create();
        $this->admin();
        foreach ([['target' => 'page', 'page_id' => $page->id], ['target' => 'service', 'service_id' => $service->id], ['target' => 'url', 'url' => 'javascript:alert(1)'], ['target' => 'url', 'url' => '//example.com'], ['target' => 'heading', 'url' => 'https://example.com'], ['parent_id' => $foreignItem->id], ['menu_id' => null], ['site_id' => $other->site_id]] as $invalid) {
            $this->post($this->url('store', $menu), [...$this->data(), ...$invalid])->assertSessionHasErrors();
        }
        $ownPage = Page::factory()->create(['site_id' => $menu->site_id]);
        $this->post($this->url('store', $menu), [...$this->data(), 'target' => 'page', 'page_id' => $ownPage->id, 'anchor' => 'missing'])->assertSessionHasErrors('anchor');
        $this->get($this->url('edit', $menu, $foreignItem))->assertNotFound();
        $this->put($this->url('update', $menu, $foreignItem), $this->data())->assertNotFound();
        $this->delete($this->url('destroy', $menu, $foreignItem))->assertNotFound();
        $this->get(route('admin.sites.menus.edit', [$other->site, $menu]))->assertNotFound();
    }

    public function test_cycles_depth_and_parent_deletion_are_protected(): void
    {
        $menu = Menu::factory()->create();
        $root = MenuItem::factory()->for($menu)->create();
        $child = MenuItem::factory()->for($menu)->create(['parent_id' => $root->id]);
        $leaf = MenuItem::factory()->for($menu)->create(['parent_id' => $child->id]);
        $otherRoot = MenuItem::factory()->for($menu)->create();
        $this->admin();
        $this->post($this->url('store', $menu), [...$this->data(), 'parent_id' => $leaf->id])->assertSessionHasErrors('parent_id');
        $this->put($this->url('update', $menu, $root), [...$this->data(), 'parent_id' => $leaf->id])->assertSessionHasErrors('parent_id');
        $this->put($this->url('update', $menu, $root), [...$this->data(), 'parent_id' => $otherRoot->id])->assertSessionHasErrors('parent_id');
        $this->delete($this->url('destroy', $menu, $root))->assertSessionHasErrors('item');
        $this->assertNull($root->fresh()->parent_id);
        $this->put($this->url('update', $menu, $child), [...$this->data(), 'parent_id' => $otherRoot->id])->assertSessionHasNoErrors();
        $this->delete($this->url('destroy', $menu, $root))->assertSessionHasNoErrors();
        $this->assertModelExists($leaf);
    }

    public function test_reorder_is_scoped_to_siblings_and_atomic(): void
    {
        $menu = Menu::factory()->create();
        $a = MenuItem::factory()->for($menu)->create(['sort_order' => 5]);
        $b = MenuItem::factory()->for($menu)->create(['sort_order' => 6]);
        $child = MenuItem::factory()->for($menu)->create(['parent_id' => $a->id]);
        $this->admin();
        $url = route('admin.sites.menus.reorder', [$menu->site, $menu]);
        foreach ([[$a->id], [$a->id, $a->id], [$a->id, $child->id]] as $ids) {
            $this->put($url, ['ids' => $ids])->assertSessionHasErrors();
            $this->assertSame(5, $a->fresh()->sort_order);
        }
        $this->put($url, ['ids' => [$b->id, $a->id]])->assertSessionHasNoErrors();
        $this->assertSame([$b->id, $a->id], $menu->rootItems()->pluck('id')->all());
    }

    public function test_access_requires_admin(): void
    {
        $menu = Menu::factory()->create();
        $item = MenuItem::factory()->for($menu)->create();
        $this->get(route('admin.sites.menus.index', $menu->site))->assertRedirect('/login');
        $this->actingAs(User::factory()->create());
        $this->get($this->url('create', $menu))->assertForbidden();
        $this->post($this->url('store', $menu), $this->data())->assertForbidden();
        $this->put($this->url('update', $menu, $item), $this->data())->assertForbidden();
        $this->delete($this->url('destroy', $menu, $item))->assertForbidden();
        $this->put(route('admin.sites.menus.update', [$menu->site, $menu]), ['name' => 'Denied'])->assertForbidden();
    }

    public function test_normalization_disables_only_invalid_active_service_targets_and_is_idempotent(): void
    {
        $menu = Menu::factory()->create();
        $service = Service::factory()->create();
        $bad = MenuItem::factory()->for($menu)->create(['service_id' => $service->id]);
        $goodService = Service::factory()->create();
        $goodService->companies()->attach($menu->site->company_id);
        $good = MenuItem::factory()->for($menu)->create(['service_id' => $goodService->id]);
        $this->artisan('cms:normalize-menu-services')->assertSuccessful();
        $this->assertTrue($bad->fresh()->is_active);
        $this->artisan('cms:normalize-menu-services', ['--apply' => true])->assertSuccessful();
        $this->assertFalse($bad->fresh()->is_active);
        $this->assertTrue($good->fresh()->is_active);
        $this->assertSame($service->id, $bad->fresh()->service_id);
        $this->artisan('cms:normalize-menu-services', ['--apply' => true])->expectsOutput('0 item layanan dinonaktifkan.')->assertSuccessful();
    }
}
