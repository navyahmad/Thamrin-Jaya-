<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Cms\CreateCompanyWithSite;
use App\Actions\Cms\MediaManager;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inquiry;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\CompanyRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Gate;

use Illuminate\View\View;

class CompanyController extends Controller
{
    public function dashboard(): View
    {
        Gate::authorize('viewAny', Company::class);

        return view('admin.dashboard', [
            'companies' => Company::withCount(['products', 'inquiries'])->orderBy('sort_order')->orderBy('id')->get(),
            'productCount' => Product::count(), 'inquiryCount' => Inquiry::count(),
            'unreadCount' => Inquiry::whereNull('read_at')->count(),
            'recentInquiries' => Inquiry::with('company')->latest()->take(5)->get(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Company::class);
        return view('admin.company-create');
    }

    public function store(CompanyRequest $request, CreateCompanyWithSite $creator): RedirectResponse
    {
        $company = $creator->create($request->validated());
        return to_route('admin.companies.edit', $company)->with('success', 'Perusahaan, situs, empat halaman draft, dan navigasi berhasil dibuat. Lengkapi profil dan branding sebelum mengaktifkan.');
    }

    public function destroy(Company $company, MediaManager $media): RedirectResponse
    {
        Gate::authorize('delete', $company);
        $media->delete($company, function (Company $locked): void {
            foreach (['site', 'products', 'pillars', 'processSteps', 'inquiries', 'services'] as $relation) {
                if ($locked->$relation()->exists()) {
                    throw ValidationException::withMessages(['company' => 'Perusahaan masih memiliki situs atau konten. Nonaktifkan perusahaan untuk menyembunyikannya.']);
                }
            }
            if ($locked->is_active) {
                throw ValidationException::withMessages(['company' => 'Nonaktifkan perusahaan sebelum menghapus.']);
            }
        });
        return to_route('admin.dashboard')->with('success', 'Perusahaan kosong berhasil dihapus.');
    }

    public function edit(Company $company): View
    {
        Gate::authorize('update', $company);

        return view('admin.company', ['company' => $company->load(['site', 'products', 'pillars', 'processSteps'])]);
    }

    public function update(CompanyRequest $request, Company $company): RedirectResponse
    {
        Gate::authorize('update', $company);

        $data = $request->validated();
        $uploads = [];
        foreach (['banner' => 'banner_path', 'about_image' => 'about_image_path'] as $input => $column) {
            if ($request->hasFile($input) || $request->boolean('remove_'.$input)) {
                $uploads[$column] = $request->hasFile($input) ? $request->file($input) : null;
            }
            unset($data[$input], $data['remove_'.$input]);
        }
        $data['is_active'] = $request->boolean('is_active');
        $data['is_demo'] = $request->boolean('is_demo');
        app(MediaManager::class)->save($company, $data, $uploads);

        return back()->with('success', 'Profil perusahaan berhasil disimpan.');
    }
}
