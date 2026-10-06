<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Cms\ContentRegistry;
use App\Actions\Cms\MediaManager;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderSectionsRequest;
use App\Http\Requests\SectionRequest;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SectionController extends Controller
{
    public function index(Site $site, Page $page): View
    {
        Gate::authorize('view', $page);
        Gate::authorize('viewAny', PageSection::class);

        return view('admin.sections', ['site' => $site, 'page' => $page, 'sections' => $page->sections()->withCount('items')->get()]);
    }

    public function create(Request $request, Site $site, Page $page): View
    {
        Gate::authorize('create', PageSection::class);

        return $this->form($request, $site, $page, new PageSection(['key' => '', 'type' => 'about', 'variant' => 'default', 'sort_order' => 0, 'is_active' => false]));
    }

    public function edit(Request $request, Site $site, Page $page, PageSection $section): View
    {
        Gate::authorize('update', $section);

        return $this->form($request, $site, $page, $section->loadCount('items'));
    }

    private function form(Request $request, Site $site, Page $page, PageSection $section): View
    {
        $types = ContentRegistry::availableSectionTypes($site->company_id !== null);
        if ($section->exists && ! in_array($section->type, $types, true)) {
            $types[] = $section->type;
        }
        $data = $request->validate(['type' => ['sometimes', Rule::in($types)]]);
        $type = $data['type'] ?? $section->type;

        return view('admin.section', [
            'site' => $site, 'page' => $page, 'section' => $section, 'types' => $types, 'type' => $type,
            'source' => ContentRegistry::source($type, $site->company_id !== null),
        ]);
    }

    public function store(SectionRequest $request, Site $site, Page $page, MediaManager $media): RedirectResponse
    {
        $section = $page->sections()->make();
        $this->save($request, $site, $section, $media);

        return to_route('admin.sites.pages.sections.edit', [$site, $page, $section])->with('success', 'Section berhasil dibuat.');
    }

    public function update(SectionRequest $request, Site $site, Page $page, PageSection $section, MediaManager $media): RedirectResponse
    {
        $this->save($request, $site, $section, $media);

        return to_route('admin.sites.pages.sections.edit', [$site, $page, $section])->with('success', 'Section berhasil disimpan.');
    }

    private function save(SectionRequest $request, Site $site, PageSection $section, MediaManager $media): void
    {
        $data = $request->validated();
        $data['settings'] = ['source' => ContentRegistry::source($data['type'], $site->company_id !== null)];
        if (isset($data['limit'])) {
            $data['settings']['limit'] = (int) $data['limit'];
        }
        unset($data['limit'], $data['image'], $data['remove_image']);
        $data['is_active'] = $request->boolean('is_active');
        $uploads = [];
        if ($request->hasFile('image') || $request->boolean('remove_image')) {
            $uploads['image_path'] = $request->file('image');
        }
        $media->save($section, $data, $uploads);
    }

    public function reorder(ReorderSectionsRequest $request, Site $site, Page $page): RedirectResponse
    {
        DB::transaction(function () use ($request, $page): void {
            $page->newQuery()->whereKey($page->id)->lockForUpdate()->firstOrFail();
            $sections = $page->sections()->lockForUpdate()->get()->keyBy('id');
            $ids = array_map('intval', $request->validated('ids'));
            if ($sections->count() !== count($ids) || array_diff($ids, $sections->keys()->all())) {
                throw ValidationException::withMessages(['ids' => 'Kirim seluruh section halaman ini tepat satu kali. Muat ulang daftar jika konten berubah.']);
            }
            foreach ($ids as $order => $id) {
                $sections[$id]->update(['sort_order' => $order]);
            }
        });

        return to_route('admin.sites.pages.sections.index', [$site, $page])->with('success', 'Urutan section berhasil disimpan.');
    }

    public function destroy(Site $site, Page $page, PageSection $section, MediaManager $media): RedirectResponse
    {
        Gate::authorize('delete', $section);
        $media->delete($section, function (PageSection $record) use ($page): void {
            if ($page->menuItems()->where('anchor', $record->key)->exists()) {
                throw ValidationException::withMessages(['section' => 'Anchor section masih dipakai menu. Lepaskan target menu atau nonaktifkan section.']);
            }
        });

        return to_route('admin.sites.pages.sections.index', [$site, $page])->with('success', 'Section dan item miliknya berhasil dihapus.');
    }
}
