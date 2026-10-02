<?php

use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\InboxController;
use App\Http\Controllers\Admin\SiteController;
use App\Http\Controllers\PortalController;
use App\Http\Middleware\PrivateAdminResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', [PortalController::class, 'home'])->name('home');
Route::prefix('admin')->name('admin.')->middleware([PrivateAdminResponse::class, 'auth', 'auth.session', 'can:access-admin'])->group(function (): void {
    Route::get('/', [CompanyController::class, 'dashboard'])->name('dashboard');
    Route::get('/companies/create', [CompanyController::class, 'create'])->name('companies.create');
    Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
    Route::delete('/companies/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy');
    Route::resource('sites', SiteController::class)->only(['index', 'edit', 'update', 'destroy']);
    Route::get('/companies/{company}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
    Route::put('/companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
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
Route::get('/{company:slug}', [PortalController::class, 'company'])->name('company.show');
Route::post('/{company:slug}/inquiry', [PortalController::class, 'inquiry'])->middleware('throttle:5,1')->name('company.inquiry');
