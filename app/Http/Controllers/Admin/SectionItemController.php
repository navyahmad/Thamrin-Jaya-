<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Cms\ContentRegistry;
use App\Actions\Cms\MediaManager;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderItemsRequest;
use App\Http\Requests\SectionItemRequest;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\SectionItem;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SectionItemController extends Controller
{
    public function index(Site $site, Page $page, PageSection $section): View
    {
        ContentRegistry::assertItemSection($section);
        Gate::authorize('viewAny', SectionItem::class);

        return view('admin.section-items', ['site' => $site, 'page' => $page, 'section' => $section, 'items' => $section->items()->get()]);
    }

    public function create(Site $site, Page $page, PageSection $section): View
    {
        Gate::authorize('create', SectionItem::class);

        return $this->form($site, $page, $section, new SectionItem(['sort_order' => 0, 'is_active' => false]));
    }

    public function edit(Site $site, Page $page, PageSection $section, SectionItem $item): View
    {
        Gate::authorize('update', $item);

        return $this->form($site, $page, $section, $item);
    }

    private function form(Site $site, Page $page, PageSection $section, SectionItem $item): View
    {
        ContentRegistry::assertItemSection($section);

        return view('admin.section-item', ['site' => $site, 'page' => $page, 'section' => $section, 'item' => $item, 'fields' => ContentRegistry::itemFields($section->type)]);
    }

    public function store(SectionItemRequest $request, Site $site, Page $page, PageSection $section, MediaManager $media): RedirectResponse
    {
        $item = $section->items()->make();
        $this->save($request, $item, $media);

        return to_route('admin.sites.pages.sections.items.edit', [$site, $page, $section, $item])->with('success', 'Item berhasil dibuat.');
    }

    public function update(SectionItemRequest $request, Site $site, Page $page, PageSection $section, SectionItem $item, MediaManager $media): RedirectResponse
    {
        $this->save($request, $item, $media);

        return to_route('admin.sites.pages.sections.items.edit', [$site, $page, $section, $item])->with('success', 'Item berhasil disimpan.');
    }

    private function save(SectionItemRequest $request, SectionItem $item, MediaManager $media): void
    {
        $data = $request->validated();
        $data['settings'] = array_filter(['icon' => $data['icon'] ?? null, 'issuer' => $data['issuer'] ?? null], fn (mixed $value): bool => $value !== null);
        unset($data['icon'], $data['issuer']);
        $data['is_active'] = $request->boolean('is_active');
        $uploads = [];
        foreach (['image' => 'image_path', 'file' => 'file_path'] as $field => $column) {
            if ($request->hasFile($field) || $request->boolean('remove_'.$field)) {
                $uploads[$column] = $request->file($field);
            }
            unset($data[$field], $data['remove_'.$field]);
        }
        $media->save($item, $data, $uploads);
    }

    public function reorder(ReorderItemsRequest $request, Site $site, Page $page, PageSection $section): RedirectResponse
    {
        DB::transaction(function () use ($request, $section): void {
            $section->newQuery()->whereKey($section->id)->lockForUpdate()->firstOrFail();
            $items = $section->items()->lockForUpdate()->get()->keyBy('id');
            $ids = array_map('intval', $request->validated('ids'));
            if ($items->count() !== count($ids) || array_diff($ids, $items->keys()->all())) {
                throw ValidationException::withMessages(['ids' => 'Kirim semua item section tepat satu kali. Muat ulang daftar jika berubah.']);
            }
            foreach ($ids as $order => $id) {
                $items[$id]->update(['sort_order' => $order]);
            }
        });

        return to_route('admin.sites.pages.sections.items.index', [$site, $page, $section])->with('success', 'Urutan item berhasil disimpan.');
    }

    public function destroy(Site $site, Page $page, PageSection $section, SectionItem $item, MediaManager $media): RedirectResponse
    {
        ContentRegistry::assertItemSection($section);
        Gate::authorize('delete', $item);
        $media->delete($item);

        return to_route('admin.sites.pages.sections.items.index', [$site, $page, $section])->with('success', 'Item berhasil dihapus.');
    }
}
