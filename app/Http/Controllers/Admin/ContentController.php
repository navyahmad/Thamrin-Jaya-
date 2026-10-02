<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
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
                'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
                'remove_image' => ['sometimes', 'boolean'],
            ];
        } else {
            $rules['title'] = ['required', 'string', 'max:150'];
        }
        $data = $request->validate($rules);
        unset($data['image'], $data['remove_image']);
        if ($type === 'products' && $request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        } elseif ($type === 'products' && $request->boolean('remove_image')) {
            $data['image_path'] = null;
        }

        return $data;
    }

    public function store(Request $request, Company $company, string $type): RedirectResponse
    {
        $relation = $this->relation($company, $type);
        Gate::authorize('create', $relation->getRelated()::class);
        $relation->create($this->validated($request, $type));

        return redirect()->to(route('admin.companies.edit', $company).'#'.$type)->with('success', 'Konten berhasil ditambahkan.');
    }

    public function update(Request $request, Company $company, string $type, int $item): RedirectResponse
    {
        $record = $this->relation($company, $type)->findOrFail($item);
        Gate::authorize('update', $record);
        $oldImage = $record->image_path;
        $data = $this->validated($request, $type);
        $record->update($data);
        if ($oldImage && array_key_exists('image_path', $data)) {
            Storage::disk('public')->delete($oldImage);
        }

        return redirect()->to(route('admin.companies.edit', $company).'#'.$type)->with('success', 'Konten berhasil diperbarui.');
    }

    public function destroy(Company $company, string $type, int $item): RedirectResponse
    {
        $record = $this->relation($company, $type)->findOrFail($item);
        Gate::authorize('delete', $record);
        $image = $record->image_path;
        $record->delete();
        if ($image) {
            Storage::disk('public')->delete($image);
        }

        return redirect()->to(route('admin.companies.edit', $company).'#'.$type)->with('success', 'Konten berhasil dihapus.');
    }
}
