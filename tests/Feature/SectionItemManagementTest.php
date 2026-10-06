<?php

namespace Tests\Feature;

use App\Models\PageSection;
use App\Models\SectionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SectionItemManagementTest extends TestCase
{
    use DatabaseMigrations;

    private function admin(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    private function section(string $type): PageSection
    {
        return PageSection::factory()->create(['type' => $type, 'settings' => ['source' => 'items']]);
    }

    private function url(string $action, PageSection $section, ?SectionItem $item = null): string
    {
        return route('admin.sites.pages.sections.items.'.$action, array_filter([$section->page->site, $section->page, $section, $item]));
    }

    private function data(): array
    {
        return ['key' => 'primary', 'title' => 'Konten resmi', 'sort_order' => 1];
    }

    public function test_all_item_types_have_correct_forms_and_support_crud_status(): void
    {
        $this->admin();
        foreach (['carousel' => ['body' => 'Slide'], 'clients' => ['link_url' => 'https://example.com'], 'capacity' => ['value' => '1000', 'unit' => 'lembar/hari', 'icon' => 'factory'], 'csr' => ['occurred_on' => '2026-10-04'], 'vision_mission' => ['key' => 'vision', 'body' => 'Visi resmi'], 'certifications' => ['issuer' => 'Penerbit resmi', 'occurred_on' => '2026-10-04']] as $type => $fields) {
            $section = $this->section($type);
            $data = [...$this->data(), ...$fields];
            $this->get($this->url('create', $section))->assertOk();
            $this->post($this->url('store', $section), $data)->assertSessionHasNoErrors();
            $item = $section->items()->firstOrFail();
            $this->assertFalse($item->is_active);
            $this->get($this->url('index', $section))->assertOk()->assertSee('Konten resmi');
            $this->get($this->url('edit', $section, $item))->assertOk();
            $this->put($this->url('update', $section, $item), [...$data, 'title' => 'Diperbarui', 'is_active' => true])->assertSessionHasNoErrors();
            $this->assertTrue($item->fresh()->is_active);
            if ($type === 'capacity') {
                $this->assertSame('factory', $item->fresh()->settings['icon']);
            }
            if ($type === 'certifications') {
                $this->assertSame('Penerbit resmi', $item->fresh()->settings['issuer']);
            }
            $this->delete($this->url('destroy', $section, $item))->assertSessionHasNoErrors();
            $this->assertModelMissing($item);
        }
    }

    public function test_manual_items_are_not_available_for_company_or_relation_sections(): void
    {
        $this->admin();
        foreach (['about' => 'company', 'products' => 'company', 'services' => 'relations', 'contact' => 'contact_details'] as $type => $source) {
            $section = PageSection::factory()->create(['type' => $type, 'settings' => ['source' => $source]]);
            $this->get($this->url('index', $section))->assertNotFound();
            $this->get($this->url('create', $section))->assertNotFound();
            $this->post($this->url('store', $section), $this->data())->assertNotFound();
            $this->put($this->url('reorder', $section), ['ids' => [1]])->assertNotFound();
        }
    }

    public function test_nested_scope_and_permissions(): void
    {
        $section = $this->section('carousel');
        $item = SectionItem::factory()->create(['page_section_id' => $section->id]);
        $other = $this->section('carousel');
        $this->get($this->url('index', $section))->assertRedirect('/login');
        $this->actingAs(User::factory()->create());
        $this->post($this->url('store', $section), $this->data())->assertForbidden();
        $this->put($this->url('update', $section, $item), $this->data())->assertForbidden();
        $this->delete($this->url('destroy', $section, $item))->assertForbidden();
        $this->put($this->url('reorder', $section), ['ids' => [$item->id]])->assertForbidden();
        $this->admin();
        $this->get($this->url('edit', $other, $item))->assertNotFound();
        $this->put($this->url('update', $other, $item), $this->data())->assertNotFound();
        $this->delete($this->url('destroy', $other, $item))->assertNotFound();
        $this->get(route('admin.sites.pages.sections.items.index', [$other->page->site, $section->page, $section]))->assertNotFound();
        $this->assertModelExists($item);
    }

    public function test_type_specific_fields_keys_and_links_are_validated(): void
    {
        $this->admin();
        $vision = $this->section('vision_mission');
        $data = [...$this->data(), 'key' => 'vision', 'body' => 'Visi'];
        $this->post($this->url('store', $vision), $data)->assertSessionHasNoErrors();
        $this->post($this->url('store', $vision), $data)->assertSessionHasErrors('key');
        $this->post($this->url('store', $vision), [...$data, 'key' => 'unapproved'])->assertSessionHasErrors('key');
        $carousel = $this->section('carousel');
        foreach ([['page_section_id' => null], ['settings' => []], ['file_path' => null], ['image_path' => 'bad'], ['value' => 'stock'], ['issuer' => 'Not allowed'], ['file' => UploadedFile::fake()->create('file.pdf', 1, 'application/pdf')]] as $invalid) {
            $this->post($this->url('store', $carousel), [...$this->data(), ...$invalid])->assertSessionHasErrors(array_key_first($invalid));
        }
        foreach (['javascript:alert(1)', '//example.com', '#missing', '/unresolved'] as $url) {
            $this->post($this->url('store', $carousel), [...$this->data(), 'link_label' => 'Lihat', 'link_url' => $url])->assertSessionHasErrors('link_url');
        }
        $this->post($this->url('store', $carousel), [...$this->data(), 'link_label' => 'Lihat', 'link_url' => '#'.$carousel->key])->assertSessionHasNoErrors();
        $capacity = $this->section('capacity');
        $this->post($this->url('store', $capacity), $this->data())->assertSessionHasErrors('value');
    }

    public function test_certificate_image_and_pdf_replacement_removal_and_mime_rejection(): void
    {
        Storage::fake('public');
        $this->admin();
        $section = $this->section('certifications');
        $pdf = fn (): UploadedFile => UploadedFile::fake()->createWithContent('certificate.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
        $this->post($this->url('store', $section), [...$this->data(), 'image' => UploadedFile::fake()->image('cert.png'), 'file' => $pdf()])->assertSessionHasNoErrors();
        $item = $section->items()->firstOrFail();
        $old = $item->file_path;
        $image = $item->image_path;
        Storage::disk('public')->assertExists($old);
        Storage::disk('public')->assertExists($image);
        $this->put($this->url('update', $section, $item), [...$this->data(), 'file' => $pdf()])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($old);
        $new = $item->fresh()->file_path;
        $fakePdf = UploadedFile::fake()->createWithContent('fake.pdf', '<script>alert(1)</script>');
        $disguised = new UploadedFile($fakePdf->getPathname(), 'fake.pdf', null, null, true);
        $this->put($this->url('update', $section, $item), [...$this->data(), 'file' => $disguised])->assertSessionHasErrors('file');
        $this->put($this->url('update', $section, $item), [...$this->data(), 'file' => UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf')])->assertSessionHasErrors('file');
        foreach ([true, 1, '1'] as $remove) {
            $this->put($this->url('update', $section, $item), [...$this->data(), 'file' => $pdf(), 'remove_file' => $remove])->assertSessionHasErrors('file');
            $this->put($this->url('update', $section, $item), [...$this->data(), 'image' => UploadedFile::fake()->image('conflict.png'), 'remove_image' => $remove])->assertSessionHasErrors('image');
            $this->assertSame($new, $item->fresh()->file_path);
            $this->assertSame($image, $item->fresh()->image_path);
        }
        $this->put($this->url('update', $section, $item), [...$this->data(), 'remove_file' => true])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($new);
        $this->assertNull($item->fresh()->file_path);
        $this->delete($this->url('destroy', $section, $item))->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($image);
    }

    public function test_item_reorder_requires_complete_unique_ids_from_the_same_section(): void
    {
        $section = $this->section('carousel');
        $first = SectionItem::factory()->create(['page_section_id' => $section->id, 'sort_order' => 4]);
        $second = SectionItem::factory()->create(['page_section_id' => $section->id, 'sort_order' => 5]);
        $foreign = SectionItem::factory()->create();
        $this->admin();
        foreach ([[$first->id], [$first->id, $first->id], [$first->id, $foreign->id], ['a' => $first->id, 'b' => $second->id]] as $ids) {
            $this->put($this->url('reorder', $section), ['ids' => $ids])->assertSessionHasErrors();
            $this->assertSame(4, $first->fresh()->sort_order);
        }
        $this->put($this->url('reorder', $section), ['ids' => [$second->id, $first->id]])->assertSessionHasNoErrors();
        $this->assertSame([$second->id, $first->id], $section->items()->pluck('id')->all());
    }
}
