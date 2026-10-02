<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'slug', 'name', 'logo_path', 'logo_alt', 'favicon_path', 'template_key', 'primary_color', 'secondary_color', 'font_family', 'theme_settings', 'footer_description', 'copyright_text', 'social_links', 'seo_title', 'seo_description', 'contact_details', 'is_active'];

    protected function casts(): array
    {
        return [
            'theme_settings' => 'array',
            'social_links' => 'array',
            'contact_details' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class)->orderBy('sort_order')->orderBy('id');
    }

    public function menus(): HasMany
    {
        return $this->hasMany(Menu::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Subsidiary contact information has a single source of truth in companies. */
    public function resolvedContactDetails(): array
    {
        return $this->company
            ? $this->company->only(['email', 'phone', 'whatsapp', 'hours', 'address', 'map_query'])
            : ($this->contact_details ?? []);
    }
}
