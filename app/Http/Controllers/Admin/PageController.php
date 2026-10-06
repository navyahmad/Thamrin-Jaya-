<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Cms\ContentRules;
use App\Actions\Cms\MediaManager;
use App\Http\Controllers\Controller;
use App\Http\Requests\PageRequest;
use App\Models\Page;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PageController extends Controller
{
    public function index(Site $site): View
    {
        Gate::authorize('view', $site);
        Gate::authorize('viewAny', Page::class);

        return view('admin.pages', ['site' => $site->load('company'), 'pages' => $site->pages()->withCount(['sections', 'menuItems'])->get()]);
    }

    public function create(Site $site): View
    {
        Gate::authorize('update', $site);
        Gate::authorize('create', Page::class);

        return view('admin.page', ['site' => $site, 'page' => new Page(['sort_order' => 0, 'is_published' => false]), 'isCore' => false]);
    }

    public function store(PageRequest $request, Site $site, MediaManager $media): RedirectResponse
    {
        $page = $site->pages()->make();
        $this->save($request, $page, $media);

        return to_route('admin.sites.pages.edit', [$site, $page])->with('success', 'Halaman berhasil dibuat.');
    }

    public function edit(Site $site, Page $page): View
    {
        Gate::authorize('view', $site);
        Gate::authorize('update', $page);

        return view('admin.page', ['site' => $site, 'page' => $page->loadCount(['sections', 'menuItems']), 'isCore' => in_array($page->slug, ContentRules::CORE_PAGES, true)]);
    }

    public function update(PageRequest $request, Site $site, Page $page, MediaManager $media): RedirectResponse
    {
        $this->save($request, $page, $media);

        return to_route('admin.sites.pages.edit', [$site, $page])->with('success', 'Halaman berhasil disimpan. Tautan menu berbasis halaman tetap mengikuti halaman ini.');
    }

    public function destroy(Site $site, Page $page, MediaManager $media): RedirectResponse
    {
        Gate::authorize('update', $site);
        Gate::authorize('delete', $page);
        $media->delete($page, function (Page $record): void {
            if ($record->menuItems()->exists()) {
                throw ValidationException::withMessages(['page' => 'Halaman masih ditarget menu. Lepaskan target menu sebelum menghapus halaman.']);
            }
        });

        return to_route('admin.sites.pages.index', $site)->with('success', 'Halaman, section, item, dan tautan menu terkait telah dihapus.');
    }

    private function save(PageRequest $request, Page $page, MediaManager $media): void
    {
        $data = $request->validated();
        unset($data['og_image'], $data['remove_og_image']);
        $data['is_published'] = $request->boolean('is_published');
        $uploads = [];
        if ($request->hasFile('og_image') || $request->boolean('remove_og_image')) {
            $uploads['og_image_path'] = $request->hasFile('og_image') ? $request->file('og_image') : null;
        }
        $media->save($page, $data, $uploads);
    }
}
