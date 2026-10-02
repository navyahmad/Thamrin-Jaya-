<?php

namespace App\Models;

use App\Actions\Cms\ContentRegistry;
use App\Actions\Cms\ContentRules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SectionItem extends Model
{
    use HasFactory;

    protected $fillable = ['page_section_id', 'key', 'title', 'subtitle', 'body', 'image_path', 'image_alt', 'file_path', 'link_label', 'link_url', 'value', 'unit', 'occurred_on', 'settings', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'occurred_on' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (SectionItem $item): void {
            ContentRules::immutable($item, 'page_section_id');
            ContentRegistry::validateItem($item);
        });
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(PageSection::class, 'page_section_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
