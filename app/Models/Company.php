<?php

namespace App\Models;

use App\Actions\Cms\ContentRules;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Company extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'short_name', 'slug', 'sector', 'summary', 'about', 'history', 'tagline', 'banner_path', 'about_image_path', 'accent', 'illustration', 'email', 'phone', 'whatsapp', 'hours', 'address', 'map_query', 'sort_order', 'is_active', 'is_demo'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_demo' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (Company $company): void {
            ContentRules::validateSlug($company->slug, true);
        });
    }

    public function scopeVisible(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true)->where(function ($query): void {
            $query->whereDoesntHave('site')->orWhereHas('site', fn ($site) => $site->where('is_active', true)->where(function ($pages): void {
                $pages->whereDoesntHave('pages', fn ($page) => $page->where('slug', 'home'))->orWhereHas('pages', fn ($page) => $page->where('slug', 'home')->where('is_published', true));
            }));
        });
    }

    public function site(): HasOne
    {
        return $this->hasOne(Site::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)->withPivot('sort_order')->withTimestamps()->orderByPivot('sort_order');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class)->orderBy('sort_order')->orderBy('id');
    }

    public function pillars(): HasMany
    {
        return $this->hasMany(Pillar::class)->orderBy('sort_order')->orderBy('id');
    }

    public function processSteps(): HasMany
    {
        return $this->hasMany(ProcessStep::class)->orderBy('sort_order')->orderBy('id');
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }
}
