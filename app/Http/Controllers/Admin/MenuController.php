<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MenuRequest;
use App\Models\Menu;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function index(Site $site): View
    {
        Gate::authorize('viewAny', Menu::class);

        return view('admin.menus', ['site' => $site, 'menus' => $site->menus()->withCount('items')->get()]);
    }

    public function edit(Site $site, Menu $menu): View
    {
        Gate::authorize('update', $menu);

        return view('admin.menu', ['site' => $site, 'menu' => $menu, 'items' => $menu->items()->with(['parent', 'page', 'service'])->withCount('children')->get()]);
    }

    public function update(MenuRequest $request, Site $site, Menu $menu): RedirectResponse
    {
        $menu->update([...$request->validated(), 'is_active' => $request->boolean('is_active')]);

        return to_route('admin.sites.menus.edit', [$site, $menu])->with('success', 'Menu berhasil disimpan.');
    }

    public function reorder(Request $request, Site $site, Menu $menu): RedirectResponse
    {
        Gate::authorize('update', $menu);
        $data = $request->validate([
            'parent_id' => ['nullable', 'integer', Rule::exists('menu_items', 'id')->where('menu_id', $menu->id)],
            'ids' => ['required', 'array', 'list', 'min:1', 'max:1000'],
            'ids.*' => ['required', 'integer', 'distinct', Rule::exists('menu_items', 'id')->where('menu_id', $menu->id)],
        ]);
        DB::transaction(function () use ($menu, $data): void {
            $menu->newQuery()->whereKey($menu->id)->lockForUpdate()->firstOrFail();
            $items = $menu->items()->where('parent_id', $data['parent_id'] ?? null)->lockForUpdate()->get()->keyBy('id');
            $ids = array_map('intval', $data['ids']);
            if ($items->count() !== count($ids) || array_diff($ids, $items->keys()->all())) {
                throw ValidationException::withMessages(['ids' => 'Kirim seluruh item dari induk yang sama tepat satu kali.']);
            }
            foreach ($ids as $order => $id) {
                $items[$id]->update(['sort_order' => $order]);
            }
        });

        return to_route('admin.sites.menus.edit', [$site, $menu])->with('success', 'Urutan menu berhasil disimpan.');
    }
}
