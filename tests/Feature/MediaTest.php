<?php

namespace Tests\Feature;

use App\Actions\Cms\MediaManager;
use App\Models\Company;
use App\Models\Inquiry;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Product;
use App\Models\SectionItem;
use App\Models\Site;
use Illuminate\Database\QueryException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use DatabaseMigrations;

    private function image(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('sample.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
    }

    public function test_url_resolver_supports_bundled_and_legacy_paths_without_unsafe_urls(): void
    {
        $this->assertSame(asset('images/globalindo.webp'), MediaManager::url('images/globalindo.webp'));
        foreach (['products/a.png', 'storage/products/a.png', '/storage/products/a.png'] as $path) {
            $this->assertSame(Storage::disk('public')->url('products/a.png'), MediaManager::url($path));
        }
        foreach (['../.env', 'media/../../.env', 'storage/media/../secret', 'https://evil.test/a.png', '//evil.test/a.png', 'media/%2e%2e/a.png', 'media/a\\b.png', 'images/../.env', 'javascript:alert(1)'] as $path) {
            $this->assertNull(MediaManager::url($path));
        }
    }

    public function test_replacing_shared_upload_preserves_other_site_reference_until_last_removal(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/shared.png', 'image');
        $product = Product::factory()->create(['image_path' => 'products/shared.png']);
        $site = Site::factory()->create(['logo_path' => 'storage/products/shared.png']);
        $manager = app(MediaManager::class);
        $manager->save($product, [], ['image_path' => $this->image()]);
        Storage::disk('public')->assertExists('products/shared.png');
        Storage::disk('public')->assertExists($product->fresh()->image_path);
        $manager->save($site, [], ['logo_path' => null]);
        Storage::disk('public')->assertMissing('products/shared.png');
    }

    public function test_failed_database_save_removes_new_file_and_preserves_old_file_and_data(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/original.png', 'image');
        $product = Product::factory()->create(['image_path' => 'products/original.png']);
        try {
            app(MediaManager::class)->save($product, ['name' => null], ['image_path' => $this->image()]);
            $this->fail('Database should reject null name.');
        } catch (QueryException) {
            $this->assertSame('products/original.png', $product->fresh()->image_path);
            $this->assertSame(['products/original.png'], Storage::disk('public')->allFiles());
        }
    }

    public function test_second_invalid_upload_cleans_up_first_upload(): void
    {
        Storage::fake('public');
        $company = Company::factory()->create();
        try {
            app(MediaManager::class)->save($company, [], [
                'banner_path' => $this->image(),
                'about_image_path' => UploadedFile::fake()->createWithContent('attack.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            ]);
            $this->fail('SVG should be rejected.');
        } catch (ValidationException) {
            $this->assertSame([], Storage::disk('public')->allFiles());
            $this->assertNull($company->fresh()->banner_path);
        }
    }

    public function test_cascade_cleanup_collects_descendant_media_and_preserves_shared_reference(): void
    {
        Storage::fake('public');
        foreach (['media/logo.png', 'media/section.png', 'media/shared.png'] as $path) {
            Storage::disk('public')->put($path, 'image');
        }
        $site = Site::factory()->create(['logo_path' => 'media/logo.png']);
        $page = Page::factory()->for($site)->create();
        $section = PageSection::factory()->for($page)->create(['image_path' => 'media/section.png']);
        SectionItem::factory()->for($section, 'section')->create(['image_path' => 'media/shared.png']);
        $product = Product::factory()->create(['image_path' => 'media/shared.png']);
        app(MediaManager::class)->delete($site);
        $this->assertModelMissing($section);
        Storage::disk('public')->assertMissing(['media/logo.png', 'media/section.png']);
        Storage::disk('public')->assertExists('media/shared.png');
        $this->assertModelExists($product);
    }

    public function test_foreign_key_restrict_failure_does_not_remove_media(): void
    {
        Storage::fake('public');
        $company = Company::factory()->create(['banner_path' => 'media/banner.png']);
        Storage::disk('public')->put('media/banner.png', 'image');
        Inquiry::factory()->for($company)->create();
        try {
            app(MediaManager::class)->delete($company);
            $this->fail('Inquiry should prevent company deletion.');
        } catch (QueryException) {
            $this->assertModelExists($company);
            Storage::disk('public')->assertExists('media/banner.png');
        }
    }

    public function test_bundled_assets_are_never_deleted_by_media_removal(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('images/globalindo.webp', 'copy');
        $site = Site::factory()->create(['logo_path' => 'images/globalindo.webp']);
        app(MediaManager::class)->save($site, [], ['logo_path' => null]);
        Storage::disk('public')->assertExists('images/globalindo.webp');
        $this->assertFileExists(public_path('images/globalindo.webp'));
    }

    public function test_pdf_is_accepted_only_for_certificate_document_field(): void
    {
        Storage::fake('public');
        $section = PageSection::factory()->create(['type' => 'certifications']);
        $item = SectionItem::factory()->for($section, 'section')->create();
        $pdf = UploadedFile::fake()->createWithContent('certificate.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
        app(MediaManager::class)->save($item, [], ['file_path' => $pdf]);
        Storage::disk('public')->assertExists($item->fresh()->file_path);
        $other = SectionItem::factory()->create();
        $this->expectException(LogicException::class);
        app(MediaManager::class)->save($other, [], ['file_path' => $pdf]);
    }

    public function test_nested_transaction_is_rejected_before_writing_any_file(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();
        try {
            DB::transaction(fn () => app(MediaManager::class)->save($product, [], ['image_path' => $this->image()]));
            $this->fail('Nested transaction must be rejected.');
        } catch (LogicException) {
            $this->assertSame([], Storage::disk('public')->allFiles());
        }
    }

    public function test_failed_cleanup_after_commit_preserves_file_and_reports_retry_without_undoing_data(): void
    {
        $disk = Storage::fake('public');
        $disk->put('products/old.png', 'image');
        $product = Product::factory()->create(['image_path' => 'products/old.png']);
        $failingDisk = \Mockery::mock(FilesystemAdapter::class);
        $failingDisk->shouldReceive('delete')->once()->with('products/old.png')->andReturn(false);
        Storage::shouldReceive('disk')->with('public')->andReturn($failingDisk);
        Log::shouldReceive('warning')->once()->with('Media cleanup gagal; file dipertahankan.', ['path' => 'products/old.png']);
        app(MediaManager::class)->delete($product);
        $this->assertModelMissing($product);
        $this->assertTrue($disk->exists('products/old.png'));
    }

    public function test_oversized_images_and_disguised_scripts_are_rejected_without_files(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();
        $script = UploadedFile::fake()->createWithContent('photo.png', '<?php echo "unsafe";');
        $disguised = new UploadedFile($script->getPathname(), 'photo.png', null, null, true);
        foreach ([$this->image()->size(5121), $disguised] as $file) {
            try {
                app(MediaManager::class)->save($product, [], ['image_path' => $file]);
                $this->fail('Invalid upload accepted.');
            } catch (ValidationException) {
                $this->assertSame([], Storage::disk('public')->allFiles());
            }
        }
    }

    public function test_selecting_bundled_logo_releases_previous_upload_without_copying_asset(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('media/old-logo.png', 'image');
        $site = Site::factory()->create(['logo_path' => 'media/old-logo.png']);
        app(MediaManager::class)->save($site, [], ['logo_path' => 'images/globalindo.webp']);
        $this->assertSame('images/globalindo.webp', $site->fresh()->logo_path);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->expectException(LogicException::class);
        app(MediaManager::class)->save($site, [], ['logo_path' => '../.env']);
    }

    public function test_cleanup_retry_command_is_read_only_by_default_and_protects_references(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('media/unused.png', 'image');
        $this->artisan('media:cleanup', ['path' => 'media/unused.png'])->assertSuccessful();
        Storage::disk('public')->assertExists('media/unused.png');
        $this->artisan('media:cleanup', ['path' => 'media/unused.png', '--delete' => true])->assertSuccessful();
        Storage::disk('public')->assertMissing('media/unused.png');
        Storage::disk('public')->put('media/used.png', 'image');
        Product::factory()->create(['image_path' => 'storage/media/used.png']);
        $this->artisan('media:cleanup', ['path' => 'media/used.png', '--delete' => true])->assertSuccessful();
        Storage::disk('public')->assertExists('media/used.png');
        $this->artisan('media:cleanup', ['path' => 'images/globalindo.webp', '--delete' => true])->assertFailed();
    }

    public function test_storage_write_failure_does_not_update_database_and_attempts_partial_file_cleanup(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();
        $file = $this->image();
        $path = 'media/images/'.$file->hashName();
        $disk = \Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn(false);
        $disk->shouldReceive('delete')->once()->with($path)->andReturn(true);
        Storage::shouldReceive('disk')->with('public')->andReturn($disk);
        try {
            app(MediaManager::class)->save($product, [], ['image_path' => $file]);
            $this->fail('Failed write must not be accepted.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Upload gagal disimpan.', $exception->getMessage());
            $this->assertNull($product->fresh()->image_path);
        }
    }
}
