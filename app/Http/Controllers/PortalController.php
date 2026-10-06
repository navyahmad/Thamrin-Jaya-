<?php

namespace App\Http\Controllers;

use App\Actions\Cms\PublicContent;
use App\Http\Requests\StoreInquiryRequest;
use App\Models\Company;
use App\Models\Site;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;

class PortalController extends Controller
{
    public function groupInquiry(StoreInquiryRequest $request): RedirectResponse
    {
        $site = Site::whereNull('company_id')->where('is_active', true)->firstOrFail();
        abort_unless($site->pages()->where('slug', 'contact')->published()->whereHas('sections', fn (Builder $section): Builder => $section->where('type', 'contact')->active())->exists(), 404);
        $company = Company::visible()->findOrFail($request->integer('company_id'));

        return $this->store($request, $company, $site);
    }

    public function inquiry(StoreInquiryRequest $request, Company $company): RedirectResponse
    {
        abort_unless(Company::visible()->whereKey($company->id)->exists(), 404);

        return $this->store($request, $company, $company->site);
    }

    private function store(StoreInquiryRequest $request, Company $company, Site $site): RedirectResponse
    {
        $data = $request->safe()->only(['name', 'email', 'phone', 'subject', 'message']);
        $company->inquiries()->create($data);

        $content = app(PublicContent::class);
        $page = $site->pages()->published()->where('slug', 'contact')->first()
            ?? $site->pages()->published()->where('slug', 'home')->firstOrFail();
        $section = $content->sections($page, false)->firstWhere('type', 'contact');
        $url = $content->pageUrl($page).($section ? '#'.$section->key : '');

        return redirect()->to($url)->with('success', 'Pesan Anda telah diterima oleh '.$company->short_name.'. Terima kasih telah menghubungi kami.');
    }
}
