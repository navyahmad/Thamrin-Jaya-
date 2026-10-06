<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SiteManagementTest extends TestCase
{
    use DatabaseMigrations;

    private function admin(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    private function settings(Site $site): array
    {
        $site->refresh();

        return [...$site->only(['template_key', 'primary_color', 'secondary_color']), 'font_family' => 'Inter', 'is_active' => true];
    }

    private function companyData(): array
    {
        return ['name' => 'PT Unit Baru', 'short_name' => 'Unit Baru', 'slug' => 'unit-baru', 'sector' => 'Printing', 'tagline' => 'Headline', 'summary' => 'Profil resmi', 'illustration' => 'print', 'sort_order' => 7];
    }

    public function test_company_creation_initializes_an_inactive_unit_with_draft_pages_and_scoped_menus(): void
    {
        $this->admin();
        $this->get(route('admin.companies.create'))->assertOk();
        $this->post(route('admin.companies.store'), $this->companyData())->assertSessionHasNoErrors()->assertRedirect();
        $company = Company::where('slug', 'unit-baru')->firstOrFail();
        $this->assertFalse($company->is_active);
        $this->assertFalse($company->is_demo);
        $this->assertSame(4, $company->site->pages()->where('is_published', false)->count());
        $this->assertSame(2, $company->site->menus()->count());
        $this->assertDatabaseCount('menu_items', 8);
        $this->assertSame($company->id, $company->site->company_id);
        $this->get('/')->assertDontSee('PT Unit Baru');
        $this->post(route('admin.companies.store'), $this->companyData())->assertSessionHasErrors('slug');
        $this->assertDatabaseCount('companies', 1);
    }

    public function test_company_creation_rolls_back_when_site_initialization_fails(): void
    {
        $this->admin();
        Site::creating(function (): void {
            throw ValidationException::withMessages(['site' => 'Simulated failure']);
        });
        try {
            $this->post(route('admin.companies.store'), $this->companyData())->assertSessionHasErrors('site');
            $this->assertDatabaseCount('companies', 0);
            $this->assertDatabaseCount('sites', 0);
            $this->assertDatabaseCount('pages', 0);
        } finally {
            Site::flushEventListeners();
            Site::clearBootedModels();
        }
    }

    public function test_admin_can_manage_holding_settings_and_replace_and_remove_logo(): void
    {
        Storage::fake('public');
        $site = Site::factory()->group()->create();
        $this->admin();
        $this->get(route('admin.sites.index'))->assertOk();
        $this->get(route('admin.sites.edit', $site))->assertOk();
        $data = [...$this->settings($site), 'name' => 'Holding Baru', 'primary_color' => '#123abc', 'contact_details' => ['email' => 'info@example.com', 'whatsapp' => '628123456789'], 'social_links' => ['instagram' => 'https://instagram.com/example'], 'theme_settings' => ['container_width' => 'wide', 'button_style' => 'square'], 'seo_title' => 'Profil Holding', 'footer_description' => 'Footer baru'];
        $this->put(route('admin.sites.update', $site), [...$data, 'logo' => UploadedFile::fake()->image('logo.png'), 'favicon' => UploadedFile::fake()->image('favicon.png')])->assertSessionHasNoErrors();
        $site->refresh();
        $old = $site->logo_path;
        Storage::disk('public')->assertExists($old);
        $this->assertSame('Holding Baru', $site->name);
        $this->assertSame('info@example.com', $site->resolvedContactDetails()['email']);
        $this->assertSame('wide', $site->theme_settings['container_width']);
        $this->put(route('admin.sites.update', $site), [...$data, 'logo' => UploadedFile::fake()->image('new.png')])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($old);
        $replacement = $site->fresh()->logo_path;
        $this->put(route('admin.sites.update', $site), [...$data, 'remove_logo' => true])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($replacement);
        $this->assertNull($site->fresh()->logo_path);
    }

    public function test_site_cannot_change_owner_or_duplicate_company_identity_or_accept_unsafe_settings(): void
    {
        $site = Site::factory()->create()->refresh();
        $other = Company::factory()->create();
        $this->admin();
        $this->get(route('admin.sites.edit', $site))->assertOk();
        foreach ([['company_id' => $other->id], ['slug' => 'other'], ['name' => 'Duplicate'], ['contact_details' => ['email' => 'bad@example.com']], ['template_key' => 'group-gateway'], ['primary_color' => 'red'], ['social_links' => ['instagram' => 'javascript:alert(1)']], ['theme_settings' => ['script' => 'alert(1)']], ['logo_path' => '/etc/passwd'], ['logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml')]] as $invalid) {
            $this->put(route('admin.sites.update', $site), [...$this->settings($site), ...$invalid])->assertSessionHasErrors();
        }
        $this->assertSame($site->company_id, $site->fresh()->company_id);
        $this->assertSame($site->primary_color, $site->fresh()->primary_color);
    }

    public function test_guests_and_non_admins_cannot_manage_companies_or_sites(): void
    {
        $site = Site::factory()->create()->refresh();
        $this->get(route('admin.sites.index'))->assertRedirect('/login');
        $this->put(route('admin.sites.update', $site), [])->assertRedirect('/login');
        $this->actingAs(User::factory()->create());
        $this->get(route('admin.companies.create'))->assertForbidden();
        $this->post(route('admin.companies.store'), $this->companyData())->assertForbidden();
        $this->get(route('admin.sites.edit', $site))->assertForbidden();
        $this->put(route('admin.sites.update', $site), [])->assertForbidden();
        $this->delete(route('admin.sites.destroy', $site))->assertForbidden();
        $this->delete(route('admin.companies.destroy', $site->company))->assertForbidden();
    }

    public function test_delete_protects_holding_subsidiary_sites_and_companies_with_dependencies(): void
    {
        $site = Site::factory()->create()->refresh();
        $holding = Site::factory()->group()->create();
        $this->admin();
        foreach ([$site, $holding] as $target) {
            $this->delete(route('admin.sites.destroy', $target))->assertSessionHasErrors('site');
        }
        $this->delete(route('admin.companies.destroy', $site->company))->assertSessionHasErrors('company');
        $orphan = Company::factory()->create(['is_active' => false]);
        $this->delete(route('admin.companies.destroy', $orphan))->assertSessionHasNoErrors()->assertRedirect(route('admin.dashboard'));
        $this->assertModelMissing($orphan);
        $this->assertModelExists($site);
        $this->assertModelExists($holding);
    }

    public function test_site_deactivation_hides_gateway_profile_and_inquiry_and_brand_uses_site_color(): void
    {
        $site = Site::factory()->create(['is_active' => true, 'primary_color' => '#123abc']);
        $site->company->update(['is_active' => true]);
        $this->publishSite($site->company);
        $this->get(route('company.show', $site->company->slug))->assertOk()->assertSee('#123abc');
        $this->admin();
        $data = $this->settings($site);
        unset($data['is_active']);
        $this->put(route('admin.sites.update', $site), $data)->assertSessionHasNoErrors();
        $this->get('/')->assertDontSee('company-'.$site->company_id);
        $this->get(route('company.show', $site->company->slug))->assertNotFound();
        $this->post(route('company.inquiry', $site->company->slug), [])->assertNotFound();
    }

    public function test_empty_immutable_fields_are_rejected_without_changing_the_site(): void
    {
        $site = Site::factory()->create()->refresh();
        $this->admin();
        foreach (['company_id', 'slug', 'name', 'contact_details', 'logo_path', 'favicon_path'] as $field) {
            $this->put(route('admin.sites.update', $site), [...$this->settings($site), $field => null])->assertSessionHasErrors($field);
        }
        $this->assertSame($site->company_id, $site->fresh()->company_id);
    }

    public function test_company_edit_keeps_site_identity_stable_and_rejects_a_second_color_editor(): void
    {
        $site = Site::factory()->create()->refresh();
        $company = $site->company;
        $originalSlug = $site->slug;
        $this->admin();
        $data = [...$this->companyData(), 'is_active' => true];
        $this->put(route('admin.companies.update', $company), [...$data, 'accent' => '#ffffff'])->assertSessionHasErrors('accent');
        $this->put(route('admin.companies.update', $company), $data)->assertSessionHasNoErrors();
        $this->assertSame('unit-baru', $site->fresh()->resolvedSlug());
        $this->assertSame($originalSlug, $site->fresh()->slug);
        $this->get(route('admin.companies.edit', $company))->assertOk()->assertDontSee('name="accent"', false);
    }

    public function test_draft_home_stays_hidden_after_company_activation_and_holding_can_be_deactivated(): void
    {
        $this->admin();
        $this->post(route('admin.companies.store'), $this->companyData())->assertSessionHasNoErrors();
        $company = Company::where('slug', 'unit-baru')->firstOrFail();
        $company->update(['is_active' => true]);
        $this->get(route('company.show', $company->slug))->assertNotFound();
        $this->get('/')->assertDontSee('company-'.$company->id);
        $company->site->pages()->where('slug', 'home')->firstOrFail()->update(['is_published' => true]);
        $this->get(route('company.show', $company->slug))->assertOk();
        $holding = Site::factory()->group()->create();
        $this->put(route('admin.sites.update', $holding), [...$this->settings($holding), 'name' => $holding->name, 'is_active' => false])->assertSessionHasNoErrors();
        $this->get('/')->assertNotFound();
        $this->get(route('admin.sites.edit', $holding))->assertOk();
    }
}
