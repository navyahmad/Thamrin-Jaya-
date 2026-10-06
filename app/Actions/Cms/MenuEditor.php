<?php

namespace App\Actions\Cms;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MenuEditor
{
    public function save(Menu $menu, MenuItem $item, array $attributes): void
    {
        DB::transaction(function () use ($menu, $item, $attributes): void {
            $menu->newQuery()->whereKey($menu->id)->lockForUpdate()->firstOrFail();
            $items = $menu->items()->lockForUpdate()->get()->keyBy('id');
            if ($item->exists) {
                $item->setRawAttributes($items->get($item->id)?->getAttributes() ?? abort(404), true);
            }
            $item->fill($attributes);
            $item->menu_id = $menu->id;
            $candidateId = $item->id ?? 0;
            $parents = $items->mapWithKeys(fn (MenuItem $row): array => [$row->id => $row->parent_id])->all();
            $parents[$candidateId] = $item->parent_id;
            foreach (array_keys($parents) as $id) {
                $visited = [];
                $current = $id;
                while ($current !== null) {
                    if (isset($visited[$current]) || ! array_key_exists($current, $parents)) {
                        throw ValidationException::withMessages(['parent_id' => 'Induk tidak valid atau membentuk siklus.']);
                    }
                    $visited[$current] = true;
                    if (count($visited) > 3) {
                        throw ValidationException::withMessages(['parent_id' => 'Menu dibatasi tiga tingkat, termasuk seluruh turunannya.']);
                    }
                    $current = $parents[$current];
                }
            }
            if ($item->service_id && $menu->site->company_id && ! $menu->site->company->services()->where('services.id', $item->service_id)->exists()) {
                throw ValidationException::withMessages(['service_id' => 'Layanan tidak terhubung perusahaan pemilik situs.']);
            }
            $item->saveOrFail();
        });
    }

    public function delete(Menu $menu, MenuItem $item): void
    {
        DB::transaction(function () use ($menu, $item): void {
            $menu->newQuery()->whereKey($menu->id)->lockForUpdate()->firstOrFail();
            $item = $menu->items()->lockForUpdate()->findOrFail($item->id);
            if ($item->children()->exists()) {
                throw ValidationException::withMessages(['item' => 'Pindahkan atau hapus submenu terlebih dahulu.']);
            }
            $item->delete();
        });
    }
}
