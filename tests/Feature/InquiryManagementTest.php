<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Inquiry;
use App\Models\PageSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InquiryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_read_state_changes_explicitly_and_independently_of_workflow_status(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $inquiry = Inquiry::factory()->create();
        $url = route('admin.inbox.update', $inquiry);
        $this->get(route('admin.inbox.show', $inquiry))->assertOk();
        $this->assertNull($inquiry->fresh()->read_at);
        $this->patch($url, ['read' => 'read'])->assertSessionHasNoErrors();
        $firstRead = $inquiry->fresh()->read_at;
        $this->assertNotNull($firstRead);
        $this->travel(2)->minutes();
        $this->patch($url, ['read' => 'read'])->assertSessionHasNoErrors();
        $this->assertTrue($firstRead->equalTo($inquiry->fresh()->read_at));
        $this->patch($url, ['status' => 'resolved'])->assertSessionHasNoErrors();
        $this->assertTrue($firstRead->equalTo($inquiry->fresh()->read_at));
        $this->patch($url, ['read' => 'unread'])->assertSessionHasNoErrors();
        $this->assertNull($inquiry->fresh()->read_at);
        $this->assertSame('resolved', $inquiry->fresh()->status);
        $this->patch($url, [])->assertSessionHasErrors(['status', 'read']);
        $this->patch($url, ['read' => 'invalid'])->assertSessionHasErrors('read');
        $this->patch($url, ['status' => 'new', 'message' => 'tampered', 'read_at' => now()->toDateTimeString()])->assertSessionHasErrors(['message', 'read_at']);
        $this->assertSame('resolved', $inquiry->fresh()->status);
    }

    public function test_inbox_filters_unread_and_retains_filters_during_pagination(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $company = Company::factory()->create();
        Inquiry::factory()->count(16)->for($company)->create(['subject' => 'Matching message']);
        Inquiry::factory()->for($company)->create(['subject' => 'Already read', 'read_at' => now()]);
        $this->get(route('admin.inbox.index', ['company_id' => $company->id, 'read' => 'unread', 'status' => 'new', 'q' => 'Matching']))
            ->assertOk()->assertDontSee('Already read')->assertViewHas('inquiries', fn ($items): bool => $items->total() === 16 && str_contains($items->nextPageUrl(), 'read=unread'));
        $this->get(route('admin.inbox.index', ['read' => 'read']))->assertOk()->assertSee('Already read')->assertDontSee('Matching message');
        $this->get(route('admin.inbox.index', ['read' => 'invalid']))->assertSessionHasErrors('read');
    }

    public function test_guests_and_non_admins_cannot_read_mutate_or_delete_messages(): void
    {
        $inquiry = Inquiry::factory()->create();
        $this->get(route('admin.inbox.show', $inquiry))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['is_admin' => false]));
        $this->get(route('admin.inbox.index'))->assertForbidden();
        $this->get(route('admin.inbox.show', $inquiry))->assertForbidden();
        $this->patch(route('admin.inbox.update', $inquiry), ['read' => 'read'])->assertForbidden();
        $this->delete(route('admin.inbox.destroy', $inquiry))->assertForbidden();
        $this->assertNull($inquiry->fresh()->read_at);
    }

    public function test_rate_limit_is_shared_between_holding_and_company_forms(): void
    {
        $this->seed();
        $companies = Company::visible()->take(2)->get();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('company.inquiry', $companies[$attempt % 2]->slug), $this->payload())->assertRedirect();
        }
        $this->post(route('group.inquiry'), [...$this->payload(), 'company_id' => $companies[0]->id])->assertStatus(429)->assertHeader('Retry-After');
        $this->assertDatabaseCount('inquiries', 5);
    }

    public function test_success_uses_dynamic_contact_anchor_and_ignores_privileged_fields(): void
    {
        $company = Company::factory()->create();
        $site = $this->publishSite($company);
        $section = $site->pages()->where('slug', 'home')->firstOrFail()->sections()->where('type', 'contact')->firstOrFail();
        $attributes = $section->getAttributes();
        $section->delete();
        PageSection::factory()->create([
            'page_id' => $attributes['page_id'], 'key' => 'contact-sales', 'type' => 'contact',
            'body' => null, 'settings' => ['source' => 'contact_details'],
        ]);
        $this->post(route('company.inquiry', $company->slug), [...$this->payload(), 'status' => 'resolved', 'read_at' => '2026-01-01'])
            ->assertRedirect(route('company.show', $company->slug).'#contact-sales');
        $this->assertDatabaseHas('inquiries', ['company_id' => $company->id, 'status' => 'new', 'read_at' => null]);
    }

    public function test_malformed_honeypot_and_oversized_message_are_rejected(): void
    {
        $company = Company::factory()->create();
        $this->publishSite($company);
        $this->post(route('company.inquiry', $company->slug), [...$this->payload(), 'website' => ['spam'], 'message' => str_repeat('a', 5001)])
            ->assertSessionHasErrors(['website', 'message']);
        $this->assertDatabaseCount('inquiries', 0);
    }

    private function payload(): array
    {
        return ['name' => 'Visitor', 'email' => 'visitor@example.com', 'subject' => 'Request information', 'message' => 'Please send company information.', 'consent' => '1'];
    }
}
