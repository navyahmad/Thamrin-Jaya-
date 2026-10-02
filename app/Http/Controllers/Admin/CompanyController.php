<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inquiry;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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

    public function edit(Company $company): View
    {
        Gate::authorize('update', $company);

        return view('admin.company', ['company' => $company->load(['products', 'pillars', 'processSteps'])]);
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        Gate::authorize('update', $company);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['required', 'string', 'max:80'],
            'slug' => ['required', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'max:100', Rule::unique('companies')->ignore($company), Rule::notIn(['admin', 'login', 'logout', 'forgot-password', 'reset-password', 'register', 'user', 'two-factor-challenge', 'email', 'confirm-password', 'passkeys', 'up', 'storage', 'build', 'images'])],
            'sector' => ['required', 'string', 'max:120'],
            'summary' => ['required', 'string', 'max:600'],
            'tagline' => ['required', 'string', 'max:200'],
            'about' => ['nullable', 'string', 'max:10000'],
            'history' => ['nullable', 'string', 'max:5000'],
            'accent' => ['required', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'illustration' => ['required', Rule::in(['boxes', 'carton', 'pouch', 'machine', 'rolls', 'print'])],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'regex:/^[+0-9() .-]+$/', 'max:40'],
            'whatsapp' => ['nullable', 'regex:/^[1-9][0-9]{7,14}$/'],
            'hours' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'map_query' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'is_active' => ['sometimes', 'boolean'],
            'is_demo' => ['sometimes', 'boolean'],
            'banner' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'about_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_banner' => ['sometimes', 'boolean'],
            'remove_about_image' => ['sometimes', 'boolean'],
        ]);
        $obsolete = [];
        foreach (['banner' => 'banner_path', 'about_image' => 'about_image_path'] as $input => $column) {
            if ($request->hasFile($input) || $request->boolean('remove_'.$input)) {
                $obsolete[] = $company->$column;
                $data[$column] = $request->hasFile($input) ? $request->file($input)->store('companies', 'public') : null;
            }
            unset($data[$input], $data['remove_'.$input]);
        }
        $data['is_active'] = $request->boolean('is_active');
        $data['is_demo'] = $request->boolean('is_demo');
        $company->update($data);
        foreach (array_filter($obsolete) as $path) {
            Storage::disk('public')->delete($path);
        }

        return back()->with('success', 'Profil perusahaan berhasil disimpan.');
    }
}
