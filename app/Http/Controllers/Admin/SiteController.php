<?php
namespace App\Http\Controllers\Admin;
use App\Actions\Cms\ContentRegistry;
use App\Actions\Cms\MediaManager;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSiteRequest;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
class SiteController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Site::class);
        return view('admin.sites', ['sites' => Site::with('company')->orderBy('id')->get()]);
    }
    public function edit(Site $site): View
    {
        Gate::authorize('update', $site);
        return view('admin.site', ['site' => $site->load('company'), 'sites' => Site::with('company')->orderBy('id')->get(), 'templates' => $site->company_id ? array_diff(ContentRegistry::TEMPLATES, ['group-gateway']) : ['default', 'group-gateway']]);
    }
    public function update(UpdateSiteRequest $request, Site $site, MediaManager $media): RedirectResponse
    {
        $data = $request->validated();
        $uploads = [];
        foreach (['logo', 'favicon'] as $key) {
            if ($request->hasFile($key) || $request->boolean('remove_'.$key)) {
                $uploads[$key.'_path'] = $request->hasFile($key) ? $request->file($key) : null;
            }
            unset($data[$key], $data['remove_'.$key]);
        }
        $data['is_active'] = $request->boolean('is_active');
        $media->save($site, $data, $uploads);
        return back()->with('success', 'Branding dan pengaturan situs berhasil disimpan.');
    }
    public function destroy(Site $site): RedirectResponse
    {
        Gate::authorize('delete', $site);
        throw ValidationException::withMessages(['site' => 'Situs holding dan situs anak perusahaan tidak dapat dihapus terpisah. Gunakan status nonaktif.']);
    }
}
