<?php

namespace App\Providers;

use App\Models\Company;
use App\Models\Inquiry;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Pillar;
use App\Models\ProcessStep;
use App\Models\Product;
use App\Models\SectionItem;
use App\Models\Service;
use App\Models\Site;
use App\Models\User;
use App\Policies\CmsContentPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        foreach ([Company::class, Product::class, Pillar::class, ProcessStep::class, Inquiry::class, Site::class, Page::class, PageSection::class, SectionItem::class, Menu::class, MenuItem::class, Service::class] as $model) {
            Gate::policy($model, CmsContentPolicy::class);
        }

        Gate::define('access-admin', fn (User $user): bool => $user->is_admin === true);
        View::composer('layouts.public', function ($view): void {
            $view->with('navigationCompanies', Company::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get());
        });
    }
}
