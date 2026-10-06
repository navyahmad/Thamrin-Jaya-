<?php

namespace App\Http\Controllers;

use App\Actions\Cms\ContentRegistry;
use App\Actions\Cms\PublicContent;
use App\Models\Company;
use App\Models\Page;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class DynamicPortalController extends Controller
{
    public function home(PublicContent $content): Response
    {
        $site = Site::whereNull('company_id')->firstOrFail();

        return $this->render($site, $site->pages()->where('slug', 'home')->firstOrFail(), $content);
    }

    public function groupPage(string $pageSlug, PublicContent $content): Response|RedirectResponse
    {
        $site = Site::whereNull('company_id')->firstOrFail();
        $page = $site->pages()->where('slug', $pageSlug)->firstOrFail();
        abort_unless($content->pageVisible($page), 404);
        if ($pageSlug === 'home' || (request()->routeIs('group.page') && in_array($pageSlug, ['about-us', 'services', 'contact'], true))) {
            return redirect()->to($content->pageUrl($page));
        }

        return $this->render($site, $page, $content);
    }

    public function company(Company $company, PublicContent $content): Response
    {
        $site = $company->site()->firstOrFail();

        return $this->render($site, $site->pages()->where('slug', 'home')->firstOrFail(), $content);
    }

    public function companyPage(Company $company, string $pageSlug, PublicContent $content): Response|RedirectResponse
    {
        $site = $company->site()->firstOrFail();
        $page = $site->pages()->where('slug', $pageSlug)->firstOrFail();
        abort_unless($content->pageVisible($page), 404);

        return $pageSlug === 'home' ? redirect()->to($content->pageUrl($page)) : $this->render($site, $page, $content);
    }

    public function preview(Site $site, Page $page, PublicContent $content): Response
    {
        Gate::authorize('view', $page);

        return $this->render($site, $page, $content, true);
    }

    private function render(Site $site, Page $page, PublicContent $content, bool $preview = false): Response
    {
        abort_unless($preview || $content->pageVisible($page), 404);
        $site->load('company');
        $page->setRelation('site', $site);
        $template = in_array($site->template_key, ContentRegistry::TEMPLATES, true) ? $site->template_key : 'default';

        return response()->view('portal.dynamic', [
            'site' => $site, 'page' => $page, 'company' => $site->company, 'content' => $content, 'preview' => $preview,
            'template' => $template, 'sections' => $content->sections($page, $preview),
            'headerMenu' => $content->menu($site, 'header', $preview), 'footerMenu' => $content->menu($site, 'footer', $preview),
            'contact' => $site->resolvedContactDetails(),
        ])->withHeaders($preview ? ['X-Robots-Tag' => 'noindex, nofollow', 'Cache-Control' => 'private, no-store'] : []);
    }
}
