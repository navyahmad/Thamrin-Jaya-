<?php

namespace Tests\Feature;

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

class SectionManagementTest extends TestCase
{
    use DatabaseMigrations;

    private function admin(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    private function data(string $type = 'about'): array
    {
        return ['key' => 'our-story', 'type' => $type, 'variant' => 'default', 'title' => 'Tentang kami', 'sort_order' => 1, 'is_active' => true];
    }

    private function url(string $action, Page $page, ?PageSection $section = null): string
    {
        return route('admin.sites.pages.sections.'.$action, array_filter([$page->site, $page, $section]));
    }

    public function test_holding_manual_content_media_and_safe_cta_can_be_managed(): void
    {
        Storage::fake('public');
        $page = Page::factory()->for(Site::factory()->group())->create();
        $this->admin();
        $this->get($this->url('create', $page))->assertOk();
        $data = [...$this->data(), 'body' => 'Profil holding', 'button_label' => 'Lihat', 'button_url' => '#our-story'];
        $this->post($this->url('store', $page), [...$data, 'image' => UploadedFile::fake()->image('about.png')])->assertSessionHasNoErrors();
        $section = $page->sections()->firstOrFail();
        $path = $section->image_path;
        $this->assertSame('manual', $section->settings['source']);
        Storage::disk('public')->assertExists($path);
        $this->get($this->url('index', $page))->assertOk()->assertSee('Tentang kami');
        $this->get($this->url('edit', $page, $section))->assertOk()->assertSee('Profil holding');
        $this->put($this->url('update', $page, $section), [...$data, 'remove_image' => true, 'is_active' => false, 'limit' => 10])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($path);
        $this->assertFalse($section->fresh()->is_active);
        $this->assertSame(10, $section->fresh()->settings['limit']);
        foreach (['javascript:alert(1)', '//example.com', '#missing', '/missing'] as $url) {
            $this->put($this->url('update', $page, $section), [...$data, 'button_url' => $url])->assertSessionHasErrors('button_url');
        }
    }

    public function test_sources_types_and_immutable_keys_are_enforced(): void
    {
        $page = Page::factory()->create();
        $holding = Page::factory()->for(Site::factory()->group())->create();
        $this->admin();
        $this->post($this->url('store', $page), $this->data())->assertSessionHasNoErrors();
        $section = $page->sections()->firstOrFail();
        $this->assertSame('company', $section->settings['source']);
        $this->get($this->url('edit', $page, $section))->assertOk()->assertDontSee('name="body"', false);
        foreach ([['body' => 'Tandingan'], ['settings' => ['source' => 'manual']], ['page_id' => null], ['key' => 'changed'], ['image_path' => null], ['variant' => '../evil'], ['limit' => 101]] as $invalid) {
            $this->put($this->url('update', $page, $section), [...$this->data(), ...$invalid])->assertSessionHasErrors(array_key_first($invalid));
        }
        $this->post($this->url('store', $page), $this->data('gateway'))->assertSessionHasErrors('type');
        foreach (['products', 'pillars', 'process'] as $type) {
            $this->post($this->url('store', $holding), $this->data($type))->assertSessionHasErrors('type');
        }
        $this->post($this->url('store', $page), $this->data())->assertSessionHasErrors('key');
        $this->post($this->url('store', $holding), $this->data())->assertSessionHasNoErrors();
    }

    public function test_type_switch_requires_empty_content_and_cannot_clear_and_switch_at_once(): void
    {
        $page = Page::factory()->for(Site::factory()->group())->create();
        $section = PageSection::factory()->for($page)->create(['key' => 'our-story', 'type' => 'about', 'settings' => ['source' => 'manual'], 'body' => 'Existing narrative']);
        $this->admin();
        $this->put($this->url('update', $page, $section), [...$this->data('history'), 'body' => null])->assertSessionHasErrors('type');
        $this->assertSame('Existing narrative', $section->fresh()->body);
        $empty = PageSection::factory()->for($page)->create(['body' => null]);
        $this->put($this->url('update', $page, $empty), [...$this->data('clients'), 'key' => $empty->key])->assertSessionHasNoErrors();
        $this->assertSame('clients', $empty->fresh()->type);
        SectionItem::factory()->create(['page_section_id' => $empty->id]);
        $this->put($this->url('update', $page, $empty), [...$this->data('csr'), 'key' => $empty->key])->assertSessionHasErrors('type');
    }

    public function test_nested_ownership_and_authorization(): void
    {
        $section = PageSection::factory()->create();
        $page = $section->page;
        $otherPage = Page::factory()->for($page->site)->create();
        $otherSite = Site::factory()->create();
        $this->get($this->url('index', $page))->assertRedirect('/login');
        $this->actingAs(User::factory()->create());
        $this->post($this->url('store', $page), $this->data())->assertForbidden();
        $this->put($this->url('update', $page, $section), $this->data())->assertForbidden();
        $this->delete($this->url('destroy', $page, $section))->assertForbidden();
        $this->put($this->url('reorder', $page), ['ids' => [$section->id]])->assertForbidden();
        $this->admin();
        $this->get(route('admin.sites.pages.sections.index', [$otherSite, $page]))->assertNotFound();
        $this->get($this->url('edit', $otherPage, $section))->assertNotFound();
        $this->put($this->url('update', $otherPage, $section), $this->data())->assertNotFound();
        $this->delete($this->url('destroy', $otherPage, $section))->assertNotFound();
        $this->assertModelExists($section);
    }

    public function test_reorder_is_complete_unique_scoped_and_atomic(): void
    {
        $page = Page::factory()->create();
        $a = PageSection::factory()->for($page)->create(['sort_order' => 5]);
        $b = PageSection::factory()->for($page)->create(['sort_order' => 6]);
        $foreign = PageSection::factory()->create();
        $this->admin();
        foreach ([[$a->id], [$a->id, $a->id], [$a->id, $foreign->id], ['unexpected' => $b->id, 'key' => $a->id]] as $ids) {
            $this->put($this->url('reorder', $page), ['ids' => $ids])->assertSessionHasErrors();
            $this->assertSame(5, $a->fresh()->sort_order);
            $this->assertSame(6, $b->fresh()->sort_order);
        }
        $this->put($this->url('reorder', $page), ['ids' => [$b->id, $a->id]])->assertSessionHasNoErrors();
        $this->assertSame([$b->id, $a->id], $page->sections()->pluck('id')->all());
    }

    public function test_delete_rejects_menu_anchor_then_cleans_descendants_and_preserves_shared_media(): void
    {
        Storage::fake('public');
        $section = PageSection::factory()->create(['image_path' => 'media/images/shared.png']);
        $page = $section->page;
        $other = PageSection::factory()->create(['image_path' => 'media/images/shared.png']);
        $item = SectionItem::factory()->create(['page_section_id' => $section->id, 'image_path' => 'media/images/item.png']);
        Storage::disk('public')->put('media/images/shared.png', 'shared');
        Storage::disk('public')->put('media/images/item.png', 'item');
        $menu = Menu::factory()->create(['site_id' => $page->site_id]);
        $link = MenuItem::factory()->for($menu)->create(['page_id' => $page->id, 'anchor' => $section->key, 'url' => null]);
        $this->admin();
        $this->delete($this->url('destroy', $page, $section))->assertSessionHasErrors('section');
        Storage::disk('public')->assertExists('media/images/item.png');
        $link->delete();
        $this->delete($this->url('destroy', $page, $section))->assertSessionHasNoErrors();
        $this->assertModelMissing($section);
        $this->assertModelMissing($item);
        $this->assertModelExists($other);
        Storage::disk('public')->assertMissing('media/images/item.png');
        Storage::disk('public')->assertExists('media/images/shared.png');
    }

    public function test_seeded_holding_placeholders_remain_inactive(): void
    {
        $this->seed();
        $this->admin();
        $site = Site::whereNull('company_id')->firstOrFail();
        foreach (['products', 'pillars', 'process'] as $type) {
            $section = PageSection::whereHas('page', fn ($query) => $query->where('site_id', $site->id))->where('type', $type)->firstOrFail();
            $this->get($this->url('edit', $section->page, $section))->assertOk();
            $this->put($this->url('update', $section->page, $section), [...$this->data($type), 'key' => $section->key])->assertSessionHasErrors('type');
            $this->assertFalse($section->fresh()->is_active);
        }
    }
}
