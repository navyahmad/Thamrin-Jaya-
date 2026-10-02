<?php

namespace App\Models;

use App\Actions\Cms\ContentRules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Page extends Model
{
    use HasFactory;

    protected $fillable = ['site_id', 'slug', 'title', 'meta_title', 'meta_description', 'og_image_path', 'is_published', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Page $page): void {
            ContentRules::immutable($page, 'site_id');
            ContentRules::validateSlug($page->slug);
            if ($page->exists && in_array($page->getOriginal('slug'), ContentRules::CORE_PAGES, true)) {
                ContentRules::immutable($page, 'slug');
            }
        });
        static::deleting(function (Page $page): void {
            if (in_array($page->slug, ContentRules::CORE_PAGES, true)) {
                throw ValidationException::withMessages(['page' => 'Halaman inti tidak dapat dihapus. Gunakan status publikasi.']);
            }
        });
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class)->orderBy('sort_order')->orderBy('id');
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
