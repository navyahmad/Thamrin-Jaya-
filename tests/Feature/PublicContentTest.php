<?php

namespace Tests\Feature;

use App\Actions\Cms\PublicContent;
use App\Models\Company;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Product;
use App\Models\SectionItem;
use App\Models\Service;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_seeded_pages_render_with_canonical_routes_and_correct_sources(): void
    {
        $this->seed();
        foreach (Site::all() as $site) {
            foreach ($site->pages as $page) {
                $url = app(PublicContent::class)->pageUrl($page);
                $this->get($url)->assertOk()->assertSee($site->resolvedName());
            }
        }
        $company = Company::firstOrFail();
        $product = $company->products()->firstOrFail();
        $this->get(route('company.show', $company->slug))->assertDontSee($product->name)->assertSee(route('company.page', [$company->slug, 'services']).'#products', false);
        $this->get(route('company.page', [$company->slug, 'services']))->assertSee($product->name);
        $this->get(route('company.page', [$company->slug, 'home']))->assertRedirect(route('company.show', $company->slug));
        $this->get('/home')->assertRedirect('/');
        $this->get('/pages/about-us')->assertRedirect('/about-us');
    }

    public function test_publication_is_strict_for_company_site_page_sections_and_items(): void
    {
        $company = Company::factory()->create(['is_active' => true]);
        $this->get(route('company.show', $company->slug))->assertNotFound();
        $site = $this->publishSite($company);
        $page = Page::factory()->for($site)->create(['slug' => 'news', 'is_published' => true]);
        $active = PageSection::factory()->for($page)->create(['key' => 'news', 'type' => 'csr', 'settings' => ['source' => 'items']]);
        SectionItem::factory()->create(['page_section_id' => $active->id, 'title' => 'Visible narrative']);
        SectionItem::factory()->create(['page_section_id' => $active->id, 'title' => 'Hidden item', 'is_active' => false]);
        $inactive = PageSection::factory()->for($page)->create(['title' => 'Hidden section', 'is_active' => false]);
        SectionItem::factory()->create(['page_section_id' => $inactive->id, 'title' => 'Hidden ancestor child']);
        $url = route('company.page', [$company->slug, 'news']);
        $this->get($url)->assertOk()->assertSee('Visible narrative')->assertDontSee('Hidden item')->assertDontSee('Hidden section')->assertDontSee('Hidden ancestor child');
        $page->update(['is_published' => false]);
        $this->get($url)->assertNotFound();
        $page->update(['is_published' => true]);
        $site->update(['is_active' => false]);
        $this->get($url)->assertNotFound();
        $site->update(['is_active' => true]);
        $company->update(['is_active' => false]);
        $this->get($url)->assertNotFound();
    }

    public function test_preview_is_private_scoped_admin_only_and_shows_draft_without_inquiry_form(): void
    {
        $site = Site::factory()->create(['is_active' => false]);
        $page = Page::factory()->for($site)->create(['is_published' => false]);
        $section = PageSection::factory()->for($page)->create(['is_active' => false]);
        SectionItem::factory()->create(['page_section_id' => $section->id, 'title' => 'Preview only item', 'is_active' => false]);
        PageSection::factory()->for($page)->create(['type' => 'contact', 'settings' => ['source' => 'contact_details']]);
        $url = route('admin.sites.pages.preview', [$site, $page]);
        $this->get($url)->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $response = $this->get($url)->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow')->assertSee('Preview only item')->assertSee('Form inquiry dinonaktifkan')->assertDontSee('class="inquiry-form"', false);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->get(route('company.page', [$site->company->slug, $page->slug]))->assertNotFound();
        $other = Site::factory()->create();
        $this->get(route('admin.sites.pages.preview', [$other, $page]))->assertNotFound();
    }

    public function test_menu_filters_invalid_targets_hidden_parents_empty_headings_and_services(): void
    {
        $site = $this->publishSite(Company::factory()->create(['is_active' => true]));
        $home = $site->pages()->where('slug', 'home')->firstOrFail();
        $menu = Menu::factory()->for($site)->create(['key' => 'header']);
        MenuItem::factory()->for($menu)->create(['label' => 'Visible home', 'page_id' => $home->id, 'url' => null]);
        $draft = Page::factory()->for($site)->create();
        MenuItem::factory()->for($menu)->create(['label' => 'Draft link', 'page_id' => $draft->id, 'url' => null]);
        $parent = MenuItem::factory()->for($menu)->create(['label' => 'Hidden parent', 'is_active' => false, 'url' => null]);
        MenuItem::factory()->for($menu)->create(['label' => 'Hidden nested link', 'parent_id' => $parent->id, 'url' => 'https://example.com']);
        MenuItem::factory()->for($menu)->create(['label' => 'Empty heading', 'url' => null]);
        MenuItem::factory()->for($menu)->create(['label' => 'Unsafe link', 'url' => 'javascript:alert(1)']);
        MenuItem::factory()->for($menu)->create(['label' => 'Bad anchor', 'page_id' => $home->id, 'anchor' => 'missing', 'url' => null]);
        $service = Service::factory()->create();
        MenuItem::factory()->for($menu)->create(['label' => 'Unrelated service', 'service_id' => $service->id, 'url' => null]);
        $this->get(route('company.show', $site->company->slug))->assertOk()->assertSee('Visible home')->assertDontSee('Draft link')->assertDontSee('Hidden nested link')->assertDontSee('Empty heading')->assertDontSee('Unsafe link')->assertDontSee('Bad anchor')->assertDontSee('Unrelated service');
    }

    public function test_branding_seo_escaping_and_company_catalog_isolation(): void
    {
        $company = Company::factory()->create(['is_active' => true]);
        $site = $this->publishSite($company);
        $site->update(['seo_title' => 'SITE SEO', 'seo_description' => 'SITE DESCRIPTION', 'template_key' => 'maxtech', 'logo_path' => 'images/maxtech.webp', 'primary_color' => '#123abc', 'footer_description' => 'Official footer']);
        $home = $site->pages()->where('slug', 'home')->firstOrFail();
        Product::factory()->for($company)->create(['name' => 'Owned product']);
        Product::factory()->create(['name' => 'Other company product']);
        $this->get(route('company.show', $company->slug))->assertOk()->assertSee('<title>SITE SEO</title>', false)->assertSee('Official footer')->assertSee('theme-maxtech')->assertSee('#123abc')->assertSee('images/maxtech.webp')->assertSee('Owned product')->assertDontSee('Other company product');
        $home->update(['meta_title' => '<script>PAGE SEO</script>', 'meta_description' => 'PAGE DESCRIPTION']);
        $this->get(route('company.show', $company->slug))->assertSee('&lt;script&gt;PAGE SEO&lt;/script&gt;', false)->assertDontSee('<script>PAGE SEO</script>', false)->assertSee('PAGE DESCRIPTION');
    }

    public function test_gateway_excludes_missing_site_draft_home_and_inactive_units(): void
    {
        $this->publishSite();
        $visible = Company::factory()->create(['is_active' => true, 'short_name' => 'Public subsidiary']);
        $this->publishSite($visible);
        Company::factory()->create(['is_active' => true, 'short_name' => 'Missing site subsidiary']);
        $hidden = Company::factory()->create(['is_active' => true, 'short_name' => 'Draft home subsidiary']);
        $this->publishSite($hidden)->pages()->where('slug', 'home')->firstOrFail()->update(['is_published' => false]);
        $this->get('/')->assertOk()->assertSee('Public subsidiary')->assertDontSee('Missing site subsidiary')->assertDontSee('Draft home subsidiary');
    }

    public function test_valid_service_menu_resolves_rendered_anchor_and_disappears_when_target_inactive(): void
    {
        $this->seed();
        $company = Company::firstOrFail();
        $site = $company->site;
        $service = $company->services()->firstOrFail();
        $menu = $site->menus()->where('key', 'header')->firstOrFail();
        $item = MenuItem::factory()->for($menu)->create(['label' => 'Dedicated service target', 'service_id' => $service->id, 'url' => null]);
        $url = route('company.page', [$company->slug, 'services']).'#services-service-'.$service->id;
        $this->get(route('company.show', $company->slug))->assertOk()->assertSee('Dedicated service target')->assertSee($url, false);
        $this->get(route('company.page', [$company->slug, 'services']))->assertSee('id="services-service-'.$service->id.'"', false);
        $service->update(['is_active' => false]);
        $this->get(route('company.show', $company->slug))->assertDontSee('Dedicated service target');
        $this->assertModelExists($item);
    }

    public function test_additional_pages_are_scoped_and_holding_inquiry_uses_a_public_destination(): void
    {
        $this->seed();
        $group = Site::whereNull('company_id')->firstOrFail();
        $page = Page::factory()->for($group)->create(['slug' => 'careers', 'is_published' => true, 'title' => 'Holding careers']);
        $this->get('/pages/careers')->assertOk()->assertSee('Holding careers');
        $company = Company::firstOrFail();
        $this->get(route('company.page', [$company->slug, 'careers']))->assertNotFound();
        $data = ['company_id' => $company->id, 'name' => 'Visitor', 'email' => 'visitor@example.com', 'subject' => 'Official inquiry', 'message' => 'Please send more information.', 'consent' => 1];
        $this->post(route('group.inquiry'), $data)->assertSessionHasNoErrors()->assertRedirect(route('group.contact').'#contact');
        $this->assertDatabaseHas('inquiries', ['company_id' => $company->id, 'subject' => 'Official inquiry']);
        $company->update(['is_active' => false]);
        $this->post(route('group.inquiry'), $data)->assertNotFound();
        $this->assertDatabaseCount('inquiries', 1);
    }

    public function test_items_are_ordered_limited_and_unsafe_urls_never_render_as_links(): void
    {
        $site = $this->publishSite(Company::factory()->create(['is_active' => true]));
        $home = $site->pages()->where('slug', 'home')->firstOrFail();
        $section = PageSection::factory()->for($home)->create(['type' => 'clients', 'settings' => ['source' => 'items', 'limit' => 2]]);
        SectionItem::factory()->create(['page_section_id' => $section->id, 'title' => 'Second client', 'sort_order' => 2, 'link_url' => 'javascript:alert(1)']);
        SectionItem::factory()->create(['page_section_id' => $section->id, 'title' => 'First client', 'sort_order' => 1]);
        SectionItem::factory()->create(['page_section_id' => $section->id, 'title' => 'Limited client', 'sort_order' => 3]);
        $this->get(route('company.show', $site->company->slug))->assertOk()->assertSeeInOrder(['First client', 'Second client'])->assertDontSee('Limited client')->assertDontSee('javascript:alert(1)', false);
    }
}
