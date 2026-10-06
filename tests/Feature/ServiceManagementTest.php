<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Service;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ServiceManagementTest extends TestCase
{
    use DatabaseMigrations;

    private function admin(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    private function data(): array
    {
        return ['name' => 'Kemasan Khusus', 'slug' => 'kemasan-khusus', 'description' => 'Layanan kemasan', 'sort_order' => 2, 'is_active' => true];
    }

    public function test_create_edit_status_and_company_relationship_order(): void
    {
        $first = Company::factory()->create();
        $second = Company::factory()->create();
        $this->admin();
        $this->get(route('admin.services.create'))->assertOk();
        $links = [['id' => $first->id, 'selected' => true, 'sort_order' => 2], ['id' => $second->id, 'selected' => true, 'sort_order' => 1]];
        $this->post(route('admin.services.store'), [...$this->data(), 'companies' => $links])->assertSessionHasNoErrors();
        $service = Service::firstOrFail();
        $this->assertSame([$second->id, $first->id], $service->companies()->pluck('companies.id')->all());
        $this->get(route('admin.services.edit', $service))->assertOk();
        $this->put(route('admin.services.update', $service), [...$this->data(), 'slug' => 'updated-service', 'is_active' => false, 'companies' => [$links[0]]])->assertSessionHasNoErrors();
        $this->assertFalse($service->fresh()->is_active);
        $this->assertSame('updated-service', $service->fresh()->slug);
        $this->assertSame([$first->id], $service->companies()->pluck('companies.id')->all());
    }

    public function test_filters_pagination_and_order(): void
    {
        $company = Company::factory()->create();
        $services = Service::factory()->count(16)->create(['name' => 'Target service', 'is_active' => false]);
        foreach ($services as $service) {
            $service->companies()->attach($company);
        }
        Service::factory()->create(['name' => 'Excluded service', 'is_active' => true]);
        $this->admin();
        $url = route('admin.services.index', ['q' => 'Target', 'status' => 'inactive', 'company_id' => $company->id]);
        $this->get($url)->assertOk()->assertDontSee('Excluded service')->assertViewHas('services', fn ($items): bool => $items->total() === 16 && $items->count() === 15 && str_contains($items->nextPageUrl(), 'status=inactive'));
        $this->get($url.'&page=2')->assertViewHas('services', fn ($items): bool => $items->count() === 1);
    }

    public function test_delete_guards_and_menu_target_survives_slug_change(): void
    {
        $service = Service::factory()->create();
        $company = Company::factory()->create();
        $service->companies()->attach($company);
        $menu = Menu::factory()->create();
        $link = MenuItem::factory()->for($menu)->create(['service_id' => $service->id, 'url' => null]);
        $this->admin();
        $this->delete(route('admin.services.destroy', $service))->assertSessionHasErrors('service');
        $this->put(route('admin.services.update', $service), $this->data())->assertSessionHasNoErrors();
        $this->assertSame($service->id, $link->fresh()->service_id);
        $this->delete(route('admin.services.destroy', $service))->assertSessionHasErrors('service');
        $link->delete();
        $this->delete(route('admin.services.destroy', $service))->assertSessionHasNoErrors()->assertRedirect(route('admin.services.index'));
        $this->assertModelMissing($service);
    }

    public function test_menu_dependency_failure_rolls_back_attributes_relations_and_new_upload(): void
    {
        Storage::fake('public');
        $site = Site::factory()->create();
        $service = Service::factory()->create(['image_path' => 'media/images/original.png']);
        Storage::disk('public')->put('media/images/original.png', 'old');
        $service->companies()->attach($site->company_id, ['sort_order' => 7]);
        $menu = Menu::factory()->for($site)->create();
        MenuItem::factory()->for($menu)->create(['service_id' => $service->id, 'url' => null]);
        $this->admin();
        $this->put(route('admin.services.update', $service), [...$this->data(), 'image' => UploadedFile::fake()->image('replacement.png')])->assertSessionHasErrors('companies');
        $this->assertSame($service->name, $service->fresh()->name);
        $this->assertSame('media/images/original.png', $service->fresh()->image_path);
        $this->assertDatabaseHas('company_service', ['service_id' => $service->id, 'company_id' => $site->company_id, 'sort_order' => 7]);
        $this->assertSame(['media/images/original.png'], Storage::disk('public')->allFiles('media/images'));
    }

    public function test_validation_and_authorization(): void
    {
        $service = Service::factory()->create();
        $this->get(route('admin.services.index'))->assertRedirect('/login');
        $this->actingAs(User::factory()->create());
        $this->get(route('admin.services.create'))->assertForbidden();
        $this->get(route('admin.services.edit', $service))->assertForbidden();
        $this->post(route('admin.services.store'), $this->data())->assertForbidden();
        $this->put(route('admin.services.update', $service), $this->data())->assertForbidden();
        $this->delete(route('admin.services.destroy', $service))->assertForbidden();
        $this->admin();
        foreach ([['slug' => $service->slug], ['slug' => 'inquiry'], ['image_path' => null], ['sort_order' => -1], ['companies' => [['id' => 999999, 'selected' => true, 'sort_order' => 0]]], ['companies' => [['id' => 1, 'sort_order' => -1]]], ['image' => UploadedFile::fake()->create('bad.svg', 1, 'image/svg+xml')]] as $invalid) {
            $this->post(route('admin.services.store'), [...$this->data(), ...$invalid])->assertSessionHasErrors();
        }
        $this->assertDatabaseCount('services', 1);
    }

    public function test_image_replacement_removal_and_unlinked_deletion(): void
    {
        Storage::fake('public');
        $this->admin();
        $this->post(route('admin.services.store'), [...$this->data(), 'image' => UploadedFile::fake()->image('first.png')])->assertSessionHasNoErrors();
        $service = Service::firstOrFail();
        $old = $service->image_path;
        Storage::disk('public')->assertExists($old);
        $this->put(route('admin.services.update', $service), [...$this->data(), 'image' => UploadedFile::fake()->image('second.png')])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($old);
        $second = $service->fresh()->image_path;
        $this->put(route('admin.services.update', $service), [...$this->data(), 'remove_image' => true])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($second);
        $this->assertNull($service->fresh()->image_path);
        $this->delete(route('admin.services.destroy', $service))->assertSessionHasNoErrors();
        $this->assertModelMissing($service);
    }
}
