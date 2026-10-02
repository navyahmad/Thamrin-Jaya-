<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_portal_and_all_six_profiles_render(): void
    {
        $this->seed();
        $this->assertDatabaseCount('companies', 6);
        $this->assertDatabaseCount('products', 18);
        $this->get('/')->assertOk()->assertSee('Globalindo')->assertSee('Top Printing');
        foreach (Company::all() as $company) {
            $this->get(route('company.show', $company->slug))->assertOk()->assertSee($company->name)->assertSee('Konten contoh');
        }
        $this->seed();
        $this->assertDatabaseCount('products', 18);
    }

    public function test_profiles_only_show_their_own_catalog(): void
    {
        $first = Company::factory()->create();
        $second = Company::factory()->create();
        Product::factory()->for($first)->create(['name' => 'UNIQUE-ALPHA-PRODUCT']);
        Product::factory()->for($second)->create(['name' => 'UNIQUE-BETA-PRODUCT']);
        $this->get(route('company.show', $first->slug))->assertOk()->assertSee('UNIQUE-ALPHA-PRODUCT')->assertDontSee('UNIQUE-BETA-PRODUCT');
    }

    public function test_draft_companies_are_hidden_and_cannot_receive_inquiries(): void
    {
        $company = Company::factory()->create(['is_active' => false, 'short_name' => 'HIDDEN UNIT']);
        $this->get('/')->assertDontSee('HIDDEN UNIT');
        $this->get(route('company.show', $company->slug))->assertNotFound();
        $this->post(route('company.inquiry', $company->slug), $this->inquiryData())->assertNotFound();
    }

    public function test_inquiry_destination_is_taken_from_route_not_user_input(): void
    {
        $company = Company::factory()->create();
        $other = Company::factory()->create();
        $this->post(route('company.inquiry', $company->slug), [...$this->inquiryData(), 'company_id' => $other->id, 'status' => 'resolved'])
            ->assertRedirect(route('company.show', $company->slug).'#contact')->assertSessionHas('success');
        $this->assertDatabaseHas('inquiries', ['company_id' => $company->id, 'status' => 'new', 'email' => 'visitor@example.com']);
        $this->assertDatabaseMissing('inquiries', ['company_id' => $other->id]);
    }

    public function test_inquiry_validation_honeypot_and_consent(): void
    {
        $company = Company::factory()->create();
        $this->post(route('company.inquiry', $company->slug), ['email' => 'bad', 'website' => 'spam'])
            ->assertSessionHasErrors(['name', 'email', 'subject', 'message', 'consent', 'website']);
        $this->assertDatabaseCount('inquiries', 0);
    }

    public function test_inquiries_are_rate_limited(): void
    {
        $company = Company::factory()->create();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('company.inquiry', $company->slug), $this->inquiryData())->assertRedirect();
        }
        $this->post(route('company.inquiry', $company->slug), $this->inquiryData())->assertStatus(429);
        $this->assertDatabaseCount('inquiries', 5);
    }

    public function test_unknown_slugs_return_404(): void
    {
        $this->get('/not-a-company')->assertNotFound();
    }

    public function test_public_content_is_escaped_and_optional_contacts_render_safely(): void
    {
        $company = Company::factory()->create(['about' => '<script>alert(1)</script>', 'whatsapp' => '6281234567890', 'map_query' => 'Jakarta & Bandung']);
        $this->get(route('company.show', $company->slug))->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('https://wa.me/6281234567890', false)
            ->assertSee('Jakarta%20%26%20Bandung', false);
    }

    private function inquiryData(): array
    {
        return ['name' => 'Visitor', 'email' => 'visitor@example.com', 'subject' => 'Packaging inquiry', 'message' => 'Please provide packaging details.', 'consent' => '1'];
    }
}
