<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Cms\MediaManager;
use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceRequest;
use App\Models\Company;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Service::class);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'company_id' => ['nullable', 'integer', Rule::exists('companies', 'id')],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);
        $query = Service::withCount(['companies', 'menuItems'])->orderBy('sort_order')->orderBy('id');
        if ($request->filled('q')) {
            $query->where(fn (Builder $query): Builder => $query->where('name', 'like', '%'.$filters['q'].'%')->orWhere('description', 'like', '%'.$filters['q'].'%'));
        }
        if ($request->filled('status')) {
            $query->where('is_active', $filters['status'] === 'active');
        }
        if ($request->filled('company_id')) {
            $query->whereHas('companies', fn (Builder $query): Builder => $query->whereKey($filters['company_id']));
        }

        return view('admin.services', ['services' => $query->paginate(15)->withQueryString(), 'companies' => Company::orderBy('name')->get(), 'filters' => $filters]);
    }

    public function create(): View
    {
        Gate::authorize('create', Service::class);

        return $this->form(new Service(['sort_order' => 0, 'is_active' => true]));
    }

    public function edit(Service $service): View
    {
        Gate::authorize('update', $service);

        return $this->form($service->load('companies')->loadCount('menuItems'));
    }

    private function form(Service $service): View
    {
        return view('admin.service', ['service' => $service, 'companies' => Company::orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function store(ServiceRequest $request, MediaManager $media): RedirectResponse
    {
        $service = new Service;
        $this->save($request, $service, $media);

        return to_route('admin.services.edit', $service)->with('success', 'Layanan dan relasi perusahaan berhasil dibuat.');
    }

    public function update(ServiceRequest $request, Service $service, MediaManager $media): RedirectResponse
    {
        $this->save($request, $service, $media);

        return to_route('admin.services.edit', $service)->with('success', 'Layanan dan relasi perusahaan berhasil disimpan.');
    }

    private function save(ServiceRequest $request, Service $service, MediaManager $media): void
    {
        $data = $request->validated();
        $links = [];
        foreach ($data['companies'] ?? [] as $company) {
            if ($company['selected'] ?? false) {
                $links[$company['id']] = ['sort_order' => $company['sort_order']];
            }
        }
        unset($data['companies'], $data['image'], $data['remove_image']);
        $data['is_active'] = $request->boolean('is_active');
        $uploads = [];
        if ($request->hasFile('image') || $request->boolean('remove_image')) {
            $uploads['image_path'] = $request->file('image');
        }
        $media->save($service, $data, $uploads, function (Service $record) use ($links): void {
            $removed = $record->companies()->pluck('companies.id')->diff(array_keys($links));
            if ($removed->isNotEmpty() && $record->menuItems()->whereHas('menu.site', fn (Builder $site): Builder => $site->whereIn('company_id', $removed))->exists()) {
                throw ValidationException::withMessages(['companies' => 'Relasi masih dipakai menu situs perusahaan terkait. Ubah target menu terlebih dahulu atau nonaktifkan layanan.']);
            }
            $record->companies()->sync($links);
        });
    }

    public function destroy(Service $service, MediaManager $media): RedirectResponse
    {
        Gate::authorize('delete', $service);
        $media->delete($service, function (Service $record): void {
            if ($record->companies()->exists() || $record->menuItems()->exists()) {
                throw ValidationException::withMessages(['service' => 'Layanan masih terhubung perusahaan atau menu. Gunakan status nonaktif atau lepaskan seluruh relasi sebelum menghapus.']);
            }
        });

        return to_route('admin.services.index')->with('success', 'Layanan berhasil dihapus.');
    }
}
