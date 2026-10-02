<?php

namespace App\Models;

use App\Actions\Cms\ContentRegistry;
use App\Actions\Cms\ContentRules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PageSection extends Model
{
    use HasFactory;

    protected $fillable = ['page_id', 'key', 'type', 'variant', 'title', 'subtitle', 'body', 'image_path', 'image_alt', 'button_label', 'button_url', 'settings', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (PageSection $section): void {
            ContentRules::immutable($section, 'page_id');
            ContentRules::immutable($section, 'key');
            ContentRegistry::validateSection($section);
        });
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SectionItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
