<?php

use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\InboxController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\MenuItemController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\SectionItemController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SiteController;
use App\Http\Controllers\DynamicPortalController;
use App\Http\Controllers\PortalController;
use App\Http\Middleware\PrivateAdminResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', [DynamicPortalController::class, 'home'])->name('home');
Route::prefix('admin')->name('admin.')->middleware([PrivateAdminResponse::class, 'auth', 'auth.session', 'can:access-admin'])->group(function (): void {
    Route::get('/sites/{site}/pages/{page}/preview', [DynamicPortalController::class, 'preview'])->scopeBindings()->name('sites.pages.preview');
    Route::get('/', [CompanyController::class, 'dashboard'])->name('dashboard');
    Route::get('/companies/create', [CompanyController::class, 'create'])->name('companies.create');
    Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
    Route::delete('/companies/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy');
    Route::put('/sites/{site}/pages/{page}/sections/reorder', [SectionController::class, 'reorder'])->scopeBindings()->name('sites.pages.sections.reorder');
    Route::put('/sites/{site}/pages/{page}/sections/{section}/items/reorder', [SectionItemController::class, 'reorder'])->scopeBindings()->name('sites.pages.sections.items.reorder');
    Route::resource('sites.pages.sections.items', SectionItemController::class)->except(['show'])->scoped();
    Route::resource('sites.pages.sections', SectionController::class)->except(['show'])->scoped();
    Route::resource('sites.pages', PageController::class)->except(['show'])->scoped();
    Route::resource('services', ServiceController::class)->except(['show']);
    Route::put('/sites/{site}/menus/{menu}/reorder', [MenuController::class, 'reorder'])->scopeBindings()->name('sites.menus.reorder');
    Route::resource('sites.menus.items', MenuItemController::class)->except(['index', 'show'])->scoped();
    Route::resource('sites.menus', MenuController::class)->only(['index', 'edit', 'update'])->scoped();
    Route::resource('sites', SiteController::class)->only(['index', 'edit', 'update', 'destroy']);
    Route::get('/companies/{company}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
    Route::put('/companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
    Route::get('/companies/{company}/{type}', [ContentController::class, 'index'])->name('content.index');
    Route::get('/companies/{company}/{type}/create', [ContentController::class, 'create'])->name('content.create');
    Route::post('/companies/{company}/{type}', [ContentController::class, 'store'])->name('content.store');
    Route::get('/companies/{company}/{type}/{item}/edit', [ContentController::class, 'edit'])->name('content.edit');
    Route::put('/companies/{company}/{type}/{item}', [ContentController::class, 'update'])->name('content.update');
    Route::delete('/companies/{company}/{type}/{item}', [ContentController::class, 'destroy'])->name('content.destroy');
    Route::get('/inbox', [InboxController::class, 'index'])->name('inbox.index');
    Route::get('/inbox/{inquiry}', [InboxController::class, 'show'])->name('inbox.show');
    Route::patch('/inbox/{inquiry}', [InboxController::class, 'update'])->name('inbox.update');
    Route::delete('/inbox/{inquiry}', [InboxController::class, 'destroy'])->name('inbox.destroy');
    Route::view('/account/two-factor', 'admin.two-factor')->middleware('password.confirm')->name('account.two-factor');
    Route::view('/account', 'admin.account')->name('account');
});
foreach (['about-us', 'services', 'contact'] as $slug) {
    Route::get('/'.$slug, [DynamicPortalController::class, 'groupPage'])->defaults('pageSlug', $slug)->name('group.'.$slug);
}
Route::get('/pages/{pageSlug}', [DynamicPortalController::class, 'groupPage'])->name('group.page');
Route::get('/home', [DynamicPortalController::class, 'groupPage'])->defaults('pageSlug', 'home')->name('group.home-alias');
Route::post('/inquiries', [PortalController::class, 'groupInquiry'])->middleware('throttle:inquiries')->name('group.inquiry');
Route::get('/{company:slug}', [DynamicPortalController::class, 'company'])->name('company.show');
Route::post('/{company:slug}/inquiry', [PortalController::class, 'inquiry'])->middleware('throttle:inquiries')->name('company.inquiry');

Route::get('/{company:slug}/{pageSlug}', [DynamicPortalController::class, 'companyPage'])->name('company.page');
