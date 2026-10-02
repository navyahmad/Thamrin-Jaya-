<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Inquiry;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function companyData(Company $company): array
    {
        return $company->only(['name', 'short_name', 'slug', 'sector', 'summary', 'tagline', 'about', 'history', 'accent', 'illustration', 'sort_order', 'is_active', 'is_demo']);
    }

    public function test_guests_and_non_admins_cannot_access_cms(): void
    {
        $company = Company::factory()->create();
        $this->get('/admin')->assertRedirect('/login');
        $this->put(route('admin.companies.update', $company), [])->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
        $this->put(route('admin.companies.update', $company), [])->assertForbidden();
    }

    public function test_admin_screens_render_with_seeded_content(): void
    {
        $this->seed();
        $this->actingAs($this->administrator());
        $this->get('/admin')->assertOk();
        $this->get('/admin/inbox')->assertOk();
        $this->get('/admin/account')->assertOk();
        $company = Company::first();
        $this->get(route('admin.companies.edit', $company))->assertOk();
        foreach (['products', 'pillars', 'process-steps'] as $type) {
            $this->get(route('admin.content.create', [$company, $type]))->assertOk();
        }
        $this->get(route('admin.content.edit', [$company, 'products', $company->products->first()->id]))->assertOk();
    }

    public function test_profile_and_gateway_order_changes_appear_publicly(): void
    {
        $first = Company::factory()->create(['sort_order' => 1]);
        $second = Company::factory()->create(['sort_order' => 2]);
        $this->actingAs($this->administrator())->put(route('admin.companies.update', $second), [...$this->companyData($second), 'sort_order' => 0, 'summary' => 'A newly updated company summary.'])->assertSessionHasNoErrors()->assertRedirect();
        $this->get('/')->assertSeeInOrder(['company-'.$second->id, 'company-'.$first->id])->assertSee('A newly updated company summary.');
        $this->get(route('company.show', $second->slug))->assertSee('A newly updated company summary.');
    }

    public function test_reserved_and_duplicate_slugs_and_bad_contact_data_are_rejected(): void
    {
        $company = Company::factory()->create();
        $other = Company::factory()->create();
        $this->actingAs($this->administrator());
        foreach (['admin', $other->slug] as $slug) {
            $this->put(route('admin.companies.update', $company), [...$this->companyData($company), 'slug' => $slug])->assertSessionHasErrors('slug');
        }
        $this->put(route('admin.companies.update', $company), [...$this->companyData($company), 'whatsapp' => 'javascript:alert(1)', 'accent' => 'red; color:blue'])
            ->assertSessionHasErrors(['whatsapp', 'accent']);
    }

    public function test_product_create_update_delete_and_image_cleanup(): void
    {
        Storage::fake('public');
        $company = Company::factory()->create();
        $this->actingAs($this->administrator());
        $data = ['name' => 'Custom Box', 'category' => 'Boxes', 'description' => 'Custom printed box.', 'sort_order' => 1, 'image' => $this->image()];
        $this->post(route('admin.content.store', [$company, 'products']), $data)->assertSessionHasNoErrors()->assertRedirect();
        $product = $company->products()->firstOrFail();
        Storage::disk('public')->assertExists($product->image_path);
        $oldImage = $product->image_path;
        $this->put(route('admin.content.update', [$company, 'products', $product->id]), [...$data, 'name' => 'Updated Box', 'image' => $this->image()])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($oldImage);
        $this->assertDatabaseHas('products', ['name' => 'Updated Box']);
        $newImage = $product->fresh()->image_path;
        $this->delete(route('admin.content.destroy', [$company, 'products', $product->id]))->assertRedirect();
        Storage::disk('public')->assertMissing($newImage);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_pillars_and_process_steps_can_be_managed_independently(): void
    {
        $company = Company::factory()->create();
        $this->actingAs($this->administrator());
        foreach (['pillars' => 'pillars', 'process-steps' => 'processSteps'] as $type => $relation) {
            $data = ['title' => 'Initial title', 'description' => 'Detailed content.', 'sort_order' => 3];
            $this->post(route('admin.content.store', [$company, $type]), $data)->assertSessionHasNoErrors();
            $item = $company->$relation()->firstOrFail();
            $this->put(route('admin.content.update', [$company, $type, $item->id]), [...$data, 'title' => 'Updated title'])->assertSessionHasNoErrors();
            $this->assertSame('Updated title', $item->fresh()->title);
            $this->delete(route('admin.content.destroy', [$company, $type, $item->id]))->assertRedirect();
            $this->assertNull($item->fresh());
        }
    }

    public function test_cross_company_content_edits_are_rejected(): void
    {
        $company = Company::factory()->create();
        $product = Product::factory()->create();
        $this->actingAs($this->administrator());
        $this->get(route('admin.content.edit', [$company, 'products', $product->id]))->assertNotFound();
        $this->put(route('admin.content.update', [$company, 'products', $product->id]), [])->assertNotFound();
        $this->delete(route('admin.content.destroy', [$company, 'products', $product->id]))->assertNotFound();
        $this->get(route('admin.content.create', [$company, 'users']))->assertNotFound();
        $this->assertModelExists($product);
    }

    public function test_invalid_image_uploads_are_rejected(): void
    {
        Storage::fake('public');
        $company = Company::factory()->create();
        $this->actingAs($this->administrator())->post(route('admin.content.store', [$company, 'products']), [
            'name' => 'Unsafe file', 'category' => 'Boxes', 'description' => 'Test product.', 'sort_order' => 1,
            'image' => UploadedFile::fake()->createWithContent('attack.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
        ])->assertSessionHasErrors('image');
        $this->assertDatabaseCount('products', 0);
    }

    public function test_banner_upload_and_removal(): void
    {
        Storage::fake('public');
        $company = Company::factory()->create();
        $this->actingAs($this->administrator())->put(route('admin.companies.update', $company), [...$this->companyData($company), 'banner' => $this->image()])->assertSessionHasNoErrors();
        $path = $company->fresh()->banner_path;
        Storage::disk('public')->assertExists($path);
        $this->put(route('admin.companies.update', $company), [...$this->companyData($company), 'remove_banner' => 1])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($path);
        $this->assertNull($company->fresh()->banner_path);
    }

    public function test_inbox_filter_read_status_and_deletion(): void
    {
        $inquiry = Inquiry::factory()->create(['subject' => 'Target inquiry', 'message' => '<script>alert(1)</script>']);
        Inquiry::factory()->create(['subject' => 'Different company inquiry']);
        $this->actingAs($this->administrator());
        $this->get(route('admin.inbox.index', ['company_id' => $inquiry->company_id, 'status' => 'new', 'q' => 'Target']))->assertOk()->assertSee('Target inquiry')->assertDontSee('Different company inquiry');
        $this->get(route('admin.inbox.show', $inquiry))->assertOk()->assertDontSee('<script>alert(1)</script>', false);
        $this->assertNotNull($inquiry->fresh()->read_at);
        $this->patch(route('admin.inbox.update', $inquiry), ['status' => 'in_progress'])->assertSessionHasNoErrors();
        $this->assertSame('in_progress', $inquiry->fresh()->status);
        $this->patch(route('admin.inbox.update', $inquiry), ['status' => 'invalid'])->assertSessionHasErrors('status');
        $this->delete(route('admin.inbox.destroy', $inquiry))->assertRedirect(route('admin.inbox.index'));
        $this->assertModelMissing($inquiry);
    }

    private function image(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('sample.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
    }
}
