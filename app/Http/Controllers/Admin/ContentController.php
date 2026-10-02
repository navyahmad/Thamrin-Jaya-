<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Cms\MediaManager;
use App\Http\Controllers\Controller;
use App\Models\Company;
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

    private function validated(Request $request, string $type): array
    {
        $rules = ['description' => ['required', 'string', 'max:10000'], 'sort_order' => ['required', 'integer', 'min:0', 'max:999']];
        if ($type === 'products') {
            $rules += [
                'name' => ['required', 'string', 'max:150'],
                'category' => ['required', 'string', 'max:100'],
                'specifications' => ['nullable', 'string', 'max:5000'],
                'image' => MediaManager::imageRules(),
                'remove_image' => ['sometimes', 'boolean'],
            ];
        } else {
            $rules['title'] = ['required', 'string', 'max:150'];
        }
        $data = $request->validate($rules);
        unset($data['image'], $data['remove_image']);

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

    public function store(Request $request, Company $company, string $type): RedirectResponse
    {
        $relation = $this->relation($company, $type);
        Gate::authorize('create', $relation->getRelated()::class);
        app(MediaManager::class)->save($relation->make(), $this->validated($request, $type), $this->uploads($request, $type));

        return redirect()->to(route('admin.companies.edit', $company).'#'.$type)->with('success', 'Konten berhasil ditambahkan.');
    }

    public function update(Request $request, Company $company, string $type, int $item): RedirectResponse
    {
        $record = $this->relation($company, $type)->findOrFail($item);
        Gate::authorize('update', $record);
        app(MediaManager::class)->save($record, $this->validated($request, $type), $this->uploads($request, $type));

        return redirect()->to(route('admin.companies.edit', $company).'#'.$type)->with('success', 'Konten berhasil diperbarui.');
    }

    public function destroy(Company $company, string $type, int $item): RedirectResponse
    {
        $record = $this->relation($company, $type)->findOrFail($item);
        Gate::authorize('delete', $record);
        app(MediaManager::class)->delete($record);

        return redirect()->to(route('admin.companies.edit', $company).'#'.$type)->with('success', 'Konten berhasil dihapus.');
    }
}
