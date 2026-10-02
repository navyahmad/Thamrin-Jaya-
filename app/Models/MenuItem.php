<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = ['menu_id', 'parent_id', 'page_id', 'service_id', 'key', 'label', 'anchor', 'url', 'type', 'open_in_new_tab', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'menu_id' => 'integer',
            'site_id' => 'integer',
            'parent_id' => 'integer',
            'is_active' => 'boolean',
            'open_in_new_tab' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected static function booted(): void
    {
        static::saving(function (MenuItem $item): void {
            $menu = Menu::findOrFail($item->menu_id);
            $item->site_id = $menu->site_id;
            $visited = [];
            $parentId = $item->parent_id;

            while ($parentId !== null) {
                if ($parentId == $item->id || isset($visited[$parentId])) {
                    throw ValidationException::withMessages(['parent_id' => 'Menu tidak boleh menjadi anak dari dirinya sendiri atau turunannya.']);
                }
                $visited[$parentId] = true;
                $parent = static::findOrFail($parentId);
                if ($parent->menu_id !== $item->menu_id) {
                    throw ValidationException::withMessages(['parent_id' => 'Induk harus berasal dari menu yang sama.']);
                }
                $parentId = $parent->parent_id;
            }
        });
    }
}
