<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Cms\MenuEditor;
use App\Http\Controllers\Controller;
use App\Http\Requests\MenuItemRequest;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Service;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MenuItemController extends Controller
{
    public function create(Site $site, Menu $menu): View
    {
        Gate::authorize('create', MenuItem::class);

        return $this->form($site, $menu, new MenuItem(['type' => 'link', 'sort_order' => 0, 'is_active' => false]));
    }

    public function edit(Site $site, Menu $menu, MenuItem $item): View
    {
        Gate::authorize('update', $item);

        return $this->form($site, $menu, $item->loadCount('children'));
    }

    private function form(Site $site, Menu $menu, MenuItem $item): View
    {
        return view('admin.menu-item', ['site' => $site, 'menu' => $menu, 'item' => $item,
            'parents' => $menu->items()->where('id', '!=', $item->id ?? 0)->get(),
            'pages' => $site->pages()->with('sections')->get(),
            'services' => $site->company_id ? $site->company->services()->get() : Service::orderBy('sort_order')->get(),
            'target' => $item->page_id ? 'page' : ($item->service_id ? 'service' : ($item->url ? 'url' : 'heading'))]);
    }

    public function store(MenuItemRequest $request, Site $site, Menu $menu, MenuEditor $editor): RedirectResponse
    {
        $item = $menu->items()->make();
        $editor->save($menu, $item, $this->attributes($request));

        return to_route('admin.sites.menus.edit', [$site, $menu])->with('success', 'Item menu berhasil dibuat.');
    }

    public function update(MenuItemRequest $request, Site $site, Menu $menu, MenuItem $item, MenuEditor $editor): RedirectResponse
    {
        $editor->save($menu, $item, $this->attributes($request));

        return to_route('admin.sites.menus.edit', [$site, $menu])->with('success', 'Item menu berhasil disimpan.');
    }

    private function attributes(MenuItemRequest $request): array
    {
        $data = $request->validated();
        $target = $data['target'];
        unset($data['target']);
        $data['page_id'] = $target === 'page' ? $data['page_id'] : null;
        $data['anchor'] = $target === 'page' ? ($data['anchor'] ?? null) : null;
        $data['service_id'] = $target === 'service' ? $data['service_id'] : null;
        $data['url'] = $target === 'url' ? $data['url'] : null;
        $data['parent_id'] ??= null;
        $data['is_active'] = $request->boolean('is_active');
        $data['open_in_new_tab'] = $target !== 'heading' && $request->boolean('open_in_new_tab');

        return $data;
    }

    public function destroy(Site $site, Menu $menu, MenuItem $item, MenuEditor $editor): RedirectResponse
    {
        Gate::authorize('delete', $item);
        $editor->delete($menu, $item);

        return to_route('admin.sites.menus.edit', [$site, $menu])->with('success', 'Item menu berhasil dihapus.');
    }
}
