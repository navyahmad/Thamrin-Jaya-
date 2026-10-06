<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Cms\MediaManager;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContentFilterRequest;
use App\Http\Requests\ContentRequest;
use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ContentController extends Controller
{
    private function relation(Company $company, string $type): HasMany
    {
        return match ($type) {
            'products' => $company->products(),
            'pillars' => $company->pillars(),
            'process-steps' => $company->processSteps(),
            default => abort(404),
        };
    }

    public function create(Company $company, string $type): View
    {
        $relation = $this->relation($company, $type);
        Gate::authorize('create', $relation->getRelated()::class);

        return view('admin.content', ['company' => $company, 'type' => $type, 'item' => $relation->make()]);
    }

    public function edit(Company $company, string $type, int $item): View
    {
        $record = $this->relation($company, $type)->findOrFail($item);
        Gate::authorize('update', $record);

        return view('admin.content', ['company' => $company, 'type' => $type, 'item' => $record]);
    }

    public function index(ContentFilterRequest $request, Company $company, string $type): View
    {
        $query = $this->relation($company, $type);
        Gate::authorize('viewAny', $query->getRelated()::class);
        $filters = $request->validated();
        if ($request->filled('q')) {
            $query->where(function (Builder $query) use ($filters, $type): void {
                $query->where($type === 'products' ? 'name' : 'title', 'like', '%'.$filters['q'].'%')
                    ->orWhere('description', 'like', '%'.$filters['q'].'%');
            });
        }
        if (! empty($filters['status'])) {
            $query->where('is_active', $filters['status'] === 'active');
        }
        if ($type === 'products' && $request->filled('category')) {
            $query->where('category', $filters['category']);
        }
        $categories = $type === 'products'
            ? $company->products()->reorder()->select('category')->distinct()->orderBy('category')->pluck('category')
            : collect();

        return view('admin.content-index', [
            'company' => $company, 'type' => $type, 'filters' => $filters,
            'items' => $query->paginate(15)->withQueryString(), 'categories' => $categories,
        ]);
    }

    private function attributes(ContentRequest $request): array
    {
        $data = $request->validated();
        unset($data['image'], $data['remove_image']);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    /** @return array<string, UploadedFile|null> */
    private function uploads(Request $request, string $type): array
    {
        if ($type === 'products' && ($request->hasFile('image') || $request->boolean('remove_image'))) {
            return ['image_path' => $request->file('image')];
        }

        return [];
    }

    public function store(ContentRequest $request, Company $company, string $type): RedirectResponse
    {
        $relation = $this->relation($company, $type);
        Gate::authorize('create', $relation->getRelated()::class);
        app(MediaManager::class)->save($relation->make(), $this->attributes($request), $this->uploads($request, $type));

        return to_route('admin.content.index', [$company, $type])->with('success', 'Konten berhasil ditambahkan.');
    }

    public function update(ContentRequest $request, Company $company, string $type, int $item): RedirectResponse
    {
        $record = $this->relation($company, $type)->findOrFail($item);
        Gate::authorize('update', $record);
        app(MediaManager::class)->save($record, $this->attributes($request), $this->uploads($request, $type));

        return to_route('admin.content.index', [$company, $type])->with('success', 'Konten berhasil diperbarui.');
    }

    public function destroy(Company $company, string $type, int $item): RedirectResponse
    {
        $record = $this->relation($company, $type)->findOrFail($item);
        Gate::authorize('delete', $record);
        app(MediaManager::class)->delete($record);

        return to_route('admin.content.index', [$company, $type])->with('success', 'Konten berhasil dihapus.');
    }
}
