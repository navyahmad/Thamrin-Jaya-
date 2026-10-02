<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function home(): View
    {
        abort_if(\App\Models\Site::whereNull('company_id')->where('is_active', false)->exists(), 404);

        return view('portal.home', ['companies' => Company::visible()->with('site')->orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function company(Company $company): View
    {
        abort_unless(Company::visible()->whereKey($company->id)->exists(), 404);

        return view('portal.company', ['company' => $company->load(['products' => fn ($query) => $query->active(), 'pillars' => fn ($query) => $query->active(), 'processSteps' => fn ($query) => $query->active()])]);
    }

    public function inquiry(Request $request, Company $company): RedirectResponse
    {
        abort_unless(Company::visible()->whereKey($company->id)->exists(), 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'website' => ['nullable', 'max:0'],
            'consent' => ['accepted'],
        ]);
        unset($data['website'], $data['consent']);
        $company->inquiries()->create($data);

        return redirect()->to(route('company.show', $company->slug).'#contact')->with('success', 'Pesan Anda telah diterima oleh '.$company->short_name.'. Terima kasih telah menghubungi kami.');
    }
}
