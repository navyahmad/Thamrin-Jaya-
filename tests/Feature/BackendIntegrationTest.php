<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackendIntegrationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_login_upload_publication_inquiry_and_inbox_work_with_csrf_enforced(): void
    {
        $this->app->bind(PreventRequestForgery::class, fn ($app) => new class($app, $app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        });
        Storage::fake('public');
        $admin = User::factory()->create(['is_admin' => true, 'password' => 'StrongPassword123']);
        $company = Company::factory()->create();
        $site = $this->publishSite($company);
        $page = $site->pages()->where('slug', 'home')->firstOrFail();
        $this->get('/login')->assertOk();
        $this->post('/login', ['email' => $admin->email, 'password' => 'StrongPassword123'])->assertStatus(419);
        $token = session()->token();
        $this->post('/login', ['_token' => $token, 'email' => $admin->email, 'password' => 'StrongPassword123'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin);
        $this->get('/admin')->assertOk();
        $token = session()->token();
        $productData = ['_token' => $token, 'name' => 'Integration catalog', 'category' => 'Packaging', 'description' => 'Verified through CMS', 'sort_order' => 0, 'is_active' => 1];
        $this->post(route('admin.content.store', [$company, 'products']), [...$productData, 'image' => UploadedFile::fake()->image('catalog.png')])->assertRedirect()->assertSessionHasNoErrors();
        $product = $company->products()->sole();
        Storage::disk('public')->assertExists($product->image_path);
        $pageData = ['_token' => $token, 'title' => $page->title, 'slug' => 'home', 'sort_order' => 0];
        $this->put(route('admin.sites.pages.update', [$site, $page]), $pageData)->assertSessionHasNoErrors();
        $this->get(route('company.show', $company->slug))->assertNotFound();
        $this->get(route('admin.sites.pages.preview', [$site, $page]))->assertOk()->assertSee('Integration catalog');
        $this->put(route('admin.sites.pages.update', [$site, $page]), [...$pageData, 'is_published' => 1])->assertSessionHasNoErrors();
        $this->post('/logout', ['_token' => $token])->assertRedirect('/login');
        $this->get(route('company.show', $company->slug))->assertOk()->assertSee('Integration catalog');
        $payload = ['name' => 'Customer', 'email' => 'visitor@example.com', 'subject' => 'Catalog request', 'message' => 'Please provide further details.', 'consent' => 1];
        $this->post(route('company.inquiry', $company->slug), $payload)->assertStatus(419);
        $this->post(route('company.inquiry', $company->slug), [...$payload, '_token' => session()->token()])->assertSessionHasNoErrors();
        $inquiry = Inquiry::sole();
        $this->assertSame($company->id, $inquiry->company_id);
        $this->post('/login', ['_token' => session()->token(), 'email' => $admin->email, 'password' => 'StrongPassword123'])->assertRedirect('/admin');
        $this->get(route('admin.inbox.show', $inquiry))->assertOk()->assertSee('Catalog request');
        $this->assertNull($inquiry->fresh()->read_at);
        $this->patch(route('admin.inbox.update', $inquiry), ['status' => 'resolved'])->assertStatus(419);
        $this->patch(route('admin.inbox.update', $inquiry), ['_token' => session()->token(), 'read' => 'read', 'status' => 'resolved'])->assertSessionHasNoErrors();
        $this->assertNotNull($inquiry->fresh()->read_at);
        $this->assertSame('resolved', $inquiry->fresh()->status);
    }
}
