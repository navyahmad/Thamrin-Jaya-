<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Pillar;
use App\Models\ProcessStep;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContentManagementTest extends TestCase
{
    use DatabaseMigrations;

    private const MODULES = ['products' => Product::class, 'pillars' => Pillar::class, 'process-steps' => ProcessStep::class];

    private function data(string $type): array
    {
        return ['description' => 'Konten resmi unit bisnis', 'sort_order' => 1, 'is_active' => true, ...($type === 'products' ? ['name' => 'Produk khusus', 'category' => 'Kemasan'] : ['title' => 'Konten khusus'])];
    }

    private function admin(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    public function test_all_modules_support_status_order_and_public_visibility(): void
    {
        $company = Company::factory()->create(['is_active' => true]);
        $this->publishSite($company);
        $this->admin();
        foreach (self::MODULES as $type => $model) {
            $data = $this->data($type);
            $field = $type === 'products' ? 'name' : 'title';
            $this->post(route('admin.content.store', [$company, $type]), [...$data, $field => 'Unique '.$type])->assertSessionHasNoErrors();
            $item = $model::where('company_id', $company->id)->firstOrFail();
            $other = $model::factory()->create(['company_id' => $company->id, $field => 'Second '.$type, 'sort_order' => 2, 'is_active' => true]);
            $this->get(route('company.show', $company->slug))->assertSee('Unique '.$type);
            $this->get(route('admin.content.index', [$company, $type]))->assertOk()->assertSeeInOrder(['Unique '.$type, 'Second '.$type]);
            $this->put(route('admin.content.update', [$company, $type, $item->id]), [...$data, $field => 'Unique '.$type, 'is_active' => false, 'sort_order' => 3])->assertSessionHasNoErrors();
            $this->get(route('company.show', $company->slug))->assertDontSee('Unique '.$type)->assertSee('Second '.$type);
            $this->get(route('admin.content.index', [$company, $type]))->assertSeeInOrder(['Second '.$type, 'Unique '.$type]);
            $this->get(route('admin.content.edit', [$company, $type, $item->id]))->assertOk();
            $this->delete(route('admin.content.destroy', [$company, $type, $item->id]))->assertRedirect(route('admin.content.index', [$company, $type]));
            $this->assertModelMissing($item);
            $this->assertModelExists($other);
        }
    }

    public function test_search_status_and_pagination_are_scoped_to_company_for_each_module(): void
    {
        $company = Company::factory()->create();
        $other = Company::factory()->create();
        $this->admin();
        foreach (self::MODULES as $type => $model) {
            $field = $type === 'products' ? 'name' : 'title';
            $model::factory()->count(16)->create(['company_id' => $company->id, $field => 'Target '.$type, 'is_active' => false]);
            $model::factory()->create(['company_id' => $other->id, $field => 'Secret other company', 'is_active' => false]);
            $model::factory()->create(['company_id' => $company->id, $field => 'Active excluded', 'is_active' => true]);
            $url = route('admin.content.index', [$company, $type, 'q' => 'Target', 'status' => 'inactive']);
            $this->get($url)->assertOk()->assertDontSee('Secret other company')->assertDontSee('Active excluded')->assertViewHas('items', fn ($items): bool => $items->total() === 16 && $items->count() === 15 && str_contains($items->nextPageUrl(), 'status=inactive'));
            $this->get($url.'&page=2')->assertOk()->assertViewHas('items', fn ($items): bool => $items->count() === 1);
            $this->get(route('admin.content.index', [$company, $type, 'status' => 'invalid']))->assertSessionHasErrors('status');
        }
    }

    public function test_category_filter_only_exposes_categories_of_current_company(): void
    {
        $company = Company::factory()->create();
        Product::factory()->create(['company_id' => $company->id, 'name' => 'Selected box', 'category' => 'Box']);
        Product::factory()->create(['company_id' => $company->id, 'name' => 'Excluded pouch', 'category' => 'Pouch']);
        Product::factory()->create(['category' => 'Confidential category']);
        $this->admin();
        $this->get(route('admin.content.index', [$company, 'products', 'category' => 'Box']))->assertOk()->assertSee('Selected box')->assertDontSee('Excluded pouch')->assertDontSee('Confidential category');
    }

    public function test_all_modules_reject_cross_company_access_and_invalid_payloads(): void
    {
        $company = Company::factory()->create();
        $this->admin();
        foreach (self::MODULES as $type => $model) {
            $item = $model::factory()->create();
            $this->get(route('admin.content.edit', [$company, $type, $item->id]))->assertNotFound();
            $this->put(route('admin.content.update', [$company, $type, $item->id]), $this->data($type))->assertNotFound();
            $this->delete(route('admin.content.destroy', [$company, $type, $item->id]))->assertNotFound();
            foreach ([['company_id' => $company->id], ['image_path' => null], ['sort_order' => -1], ['is_active' => 'invalid'], ['description' => '']] as $invalid) {
                $this->put(route('admin.content.update', [$item->company, $type, $item->id]), [...$this->data($type), ...$invalid])->assertSessionHasErrors(array_key_first($invalid));
            }
            $this->assertModelExists($item);
        }
        $this->get(route('admin.content.index', [$company, 'transactions']))->assertNotFound();
    }

    public function test_guests_and_non_admins_cannot_list_or_mutate_any_module(): void
    {
        $company = Company::factory()->create();
        $this->get(route('admin.content.index', [$company, 'products']))->assertRedirect('/login');
        $this->actingAs(User::factory()->create());
        foreach (self::MODULES as $type => $model) {
            $item = $model::factory()->create(['company_id' => $company->id]);
            $this->get(route('admin.content.index', [$company, $type]))->assertForbidden();
            $this->post(route('admin.content.store', [$company, $type]), $this->data($type))->assertForbidden();
            $this->put(route('admin.content.update', [$company, $type, $item->id]), $this->data($type))->assertForbidden();
            $this->delete(route('admin.content.destroy', [$company, $type, $item->id]))->assertForbidden();
        }
    }

    public function test_product_image_removal_and_conflicting_upload_are_handled_safely(): void
    {
        Storage::fake('public');
        $company = Company::factory()->create();
        $this->admin();
        $data = $this->data('products');
        $this->post(route('admin.content.store', [$company, 'products']), [...$data, 'image' => UploadedFile::fake()->image('product.png')])->assertSessionHasNoErrors();
        $product = $company->products()->firstOrFail();
        $path = $product->image_path;
        $this->put(route('admin.content.update', [$company, 'products', $product->id]), [...$data, 'remove_image' => 1, 'image' => UploadedFile::fake()->image('new.png')])->assertSessionHasErrors('image');
        Storage::disk('public')->assertExists($path);
        unset($data['is_active']);
        $this->put(route('admin.content.update', [$company, 'products', $product->id]), [...$data, 'remove_image' => 1])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($path);
        $this->assertNull($product->fresh()->image_path);
        $this->assertFalse($product->fresh()->is_active);
    }
}
