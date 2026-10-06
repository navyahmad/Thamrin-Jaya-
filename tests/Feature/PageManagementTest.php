<?php

namespace Tests\Feature;

use App\Actions\Cms\ContentRules;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\SectionItem;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PageManagementTest extends TestCase
{
    use DatabaseMigrations;

    private function admin(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    private function data(string $slug = 'karier'): array
    {
        return ['title' => 'Karier', 'slug' => $slug, 'sort_order' => 5, 'meta_title' => 'Bergabung bersama kami', 'meta_description' => 'Kesempatan kerja'];
    }

    public function test_admin_can_create_edit_order_and_publish_pages_with_site_scoped_slugs(): void
    {
        $site = Site::factory()->create();
        $other = Site::factory()->create();
        $this->admin();
        $this->get(route('admin.sites.pages.create', $site))->assertOk();
        foreach ([$site, $other] as $target) {
            $this->post(route('admin.sites.pages.store', $target), $this->data())->assertSessionHasNoErrors();
        }
        $page = $site->pages()->firstOrFail();
        $this->assertFalse($page->is_published);
        $this->assertSame('Bergabung bersama kami', $page->meta_title);
        $this->post(route('admin.sites.pages.store', $site), $this->data())->assertSessionHasErrors('slug');
        $second = Page::factory()->for($site)->create(['title' => 'Z Kontak tambahan', 'sort_order' => 10]);
        $this->put(route('admin.sites.pages.update', [$site, $page]), [...$this->data('kesempatan'), 'title' => 'A Lowongan', 'sort_order' => 1, 'is_published' => true])->assertSessionHasNoErrors();
        $this->assertTrue($page->fresh()->is_published);
        $this->assertSame('kesempatan', $page->fresh()->slug);
        $this->get(route('admin.sites.pages.index', $site))->assertOk()->assertSeeInOrder(['A Lowongan', $second->title]);
        $this->get(route('admin.sites.pages.edit', [$site, $page]))->assertOk();
        $this->put(route('admin.sites.pages.update', [$site, $page]), $this->data('kesempatan'))->assertSessionHasNoErrors();
        $this->assertFalse($page->fresh()->is_published);
    }

    public function test_pages_cannot_be_read_updated_or_deleted_through_another_site(): void
    {
        $page = Page::factory()->create();
        $other = Site::factory()->create();
        $this->admin();
        $this->get(route('admin.sites.pages.edit', [$other, $page]))->assertNotFound();
        $this->put(route('admin.sites.pages.update', [$other, $page]), $this->data())->assertNotFound();
        $this->delete(route('admin.sites.pages.destroy', [$other, $page]))->assertNotFound();
        $this->put(route('admin.sites.pages.update', [$page->site, $page]), [...$this->data(), 'site_id' => $other->id])->assertSessionHasErrors('site_id');
        $this->assertModelExists($page);
        $this->assertSame($page->site_id, $page->fresh()->site_id);
    }

    public function test_all_core_pages_reject_rename_and_delete_but_allow_titles_and_publication_changes(): void
    {
        $site = Site::factory()->create();
        $this->admin();
        foreach (ContentRules::CORE_PAGES as $slug) {
            $page = Page::factory()->for($site)->create(['slug' => $slug, 'is_published' => true]);
            $this->put(route('admin.sites.pages.update', [$site, $page]), $this->data('renamed'))->assertSessionHasErrors('slug');
            $this->delete(route('admin.sites.pages.destroy', [$site, $page]))->assertSessionHasErrors('page');
            $this->put(route('admin.sites.pages.update', [$site, $page]), $this->data($slug))->assertSessionHasNoErrors();
            $this->assertSame($slug, $page->fresh()->slug);
            $this->assertSame('Karier', $page->fresh()->title);
            $this->assertFalse($page->fresh()->is_published);
        }
    }

    public function test_invalid_slugs_metadata_and_direct_media_paths_are_rejected(): void
    {
        $site = Site::factory()->create();
        $this->admin();
        foreach (['inquiry', 'home', 'about-us', 'contact', 'services', '../escape', 'Upper Case', str_repeat('a', 101)] as $slug) {
            $this->post(route('admin.sites.pages.store', $site), $this->data($slug))->assertSessionHasErrors('slug');
        }
        foreach ([['site_id' => null], ['og_image_path' => null], ['sort_order' => -1], ['meta_title' => str_repeat('x', 256)], ['meta_description' => str_repeat('x', 501)], ['og_image' => UploadedFile::fake()->create('payload.svg', 2, 'image/svg+xml')]] as $invalid) {
            $this->post(route('admin.sites.pages.store', $site), [...$this->data(), ...$invalid])->assertSessionHasErrors(array_key_first($invalid));
        }
        $this->assertDatabaseCount('pages', 0);
    }

    public function test_guests_and_non_admins_cannot_manage_pages(): void
    {
        $page = Page::factory()->create();
        $site = $page->site;
        $this->get(route('admin.sites.pages.index', $site))->assertRedirect('/login');
        $this->post(route('admin.sites.pages.store', $site), $this->data())->assertRedirect('/login');
        $this->actingAs(User::factory()->create());
        $this->get(route('admin.sites.pages.index', $site))->assertForbidden();
        $this->get(route('admin.sites.pages.create', $site))->assertForbidden();
        $this->get(route('admin.sites.pages.edit', [$site, $page]))->assertForbidden();
        $this->post(route('admin.sites.pages.store', $site), $this->data())->assertForbidden();
        $this->put(route('admin.sites.pages.update', [$site, $page]), $this->data())->assertForbidden();
        $this->delete(route('admin.sites.pages.destroy', [$site, $page]))->assertForbidden();
    }

    public function test_og_image_can_be_uploaded_replaced_and_removed(): void
    {
        Storage::fake('public');
        $site = Site::factory()->create();
        $this->admin();
        $this->post(route('admin.sites.pages.store', $site), [...$this->data(), 'og_image' => UploadedFile::fake()->image('og.png')])->assertSessionHasNoErrors();
        $page = $site->pages()->firstOrFail();
        $original = $page->og_image_path;
        Storage::disk('public')->assertExists($original);
        $this->put(route('admin.sites.pages.update', [$site, $page]), [...$this->data(), 'og_image' => UploadedFile::fake()->image('replacement.webp')])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($original);
        $replacement = $page->fresh()->og_image_path;
        Storage::disk('public')->assertExists($replacement);
        $this->put(route('admin.sites.pages.update', [$site, $page]), [...$this->data(), 'remove_og_image' => 1, 'og_image' => UploadedFile::fake()->image('conflict.png')])->assertSessionHasErrors('og_image');
        $this->put(route('admin.sites.pages.update', [$site, $page]), [...$this->data(), 'remove_og_image' => 1])->assertSessionHasNoErrors();
        $this->assertNull($page->fresh()->og_image_path);
        Storage::disk('public')->assertMissing($replacement);
    }

    public function test_rename_preserves_menu_target_and_delete_cascades_content_but_preserves_shared_media(): void
    {
        Storage::fake('public');
        $site = Site::factory()->create();
        $page = Page::factory()->for($site)->create(['og_image_path' => 'media/images/shared.png']);
        $survivor = Page::factory()->create(['og_image_path' => 'media/images/shared.png']);
        Storage::disk('public')->put('media/images/shared.png', 'shared');
        Storage::disk('public')->put('media/images/section.png', 'exclusive');
        $section = PageSection::factory()->for($page)->create(['image_path' => 'media/images/section.png']);
        $item = SectionItem::factory()->create(['page_section_id' => $section->id]);
        $menu = Menu::factory()->for($site)->create();
        $link = MenuItem::factory()->for($menu)->create(['page_id' => $page->id, 'url' => null]);
        $child = MenuItem::factory()->for($menu)->create(['parent_id' => $link->id]);
        $this->admin();
        $this->put(route('admin.sites.pages.update', [$site, $page]), $this->data('renamed'))->assertSessionHasNoErrors();
        $this->assertSame($page->id, $link->fresh()->page_id);
        $this->get(route('admin.sites.pages.edit', [$site, $page]))->assertOk()->assertSee('1 section');
        $this->delete(route('admin.sites.pages.destroy', [$site, $page]))->assertSessionHasErrors('page');
        $child->delete();
        $link->delete();
        $this->delete(route('admin.sites.pages.destroy', [$site, $page]))->assertSessionHasNoErrors()->assertRedirect(route('admin.sites.pages.index', $site));
        foreach ([$page, $section, $item, $link, $child] as $deleted) {
            $this->assertModelMissing($deleted);
        }
        $this->assertModelExists($survivor);
        Storage::disk('public')->assertExists('media/images/shared.png');
        Storage::disk('public')->assertMissing('media/images/section.png');
    }

    public function test_home_publication_controls_holding_and_subsidiary_visibility(): void
    {
        $holding = Site::factory()->group()->create(['is_active' => true]);
        $subsidiary = Site::factory()->create(['is_active' => true]);
        $subsidiary->company->update(['is_active' => true]);
        $this->admin();
        foreach ([$holding, $subsidiary] as $site) {
            $home = Page::factory()->for($site)->create(['slug' => 'home', 'is_published' => true]);
            $url = $site->company_id ? route('company.show', $site->company->slug) : route('home');
            $this->get($url)->assertOk();
            $this->put(route('admin.sites.pages.update', [$site, $home]), $this->data('home'))->assertSessionHasNoErrors();
            $this->get($url)->assertNotFound();
            $this->get(route('admin.sites.pages.edit', [$site, $home]))->assertOk();
            $this->put(route('admin.sites.pages.update', [$site, $home]), [...$this->data('home'), 'is_published' => true])->assertSessionHasNoErrors();
            $this->get($url)->assertOk();
        }
    }
}
