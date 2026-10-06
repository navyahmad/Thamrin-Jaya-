<?php

namespace App\Http\Requests;

use App\Actions\Cms\MediaManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $company = $this->route('company');
        $relation = match ($this->route('type')) {
            'products' => $company->products(),
            'pillars' => $company->pillars(),
            'process-steps' => $company->processSteps(),
            default => abort(404),
        };
        if ($this->route('item') !== null) {
            return Gate::allows('update', $relation->findOrFail($this->route('item')));
        }

        return Gate::allows('create', $relation->getRelated()::class);
    }

    public function rules(): array
    {
        $rules = [
            'company_id' => ['missing'], 'image_path' => ['missing'],
            'description' => ['required', 'string', 'max:10000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'is_active' => ['sometimes', 'boolean'],
        ];
        if ($this->route('type') === 'products') {
            $rules += [
                'name' => ['required', 'string', 'max:150'],
                'category' => ['required', 'string', 'max:100'],
                'specifications' => ['nullable', 'string', 'max:5000'],
                'image' => [...MediaManager::imageRules(), Rule::prohibitedIf($this->boolean('remove_image'))],
                'remove_image' => ['sometimes', 'boolean'],
                'title' => ['missing'],
            ];
        } else {
            $rules += ['title' => ['required', 'string', 'max:150']];
            foreach (['image', 'remove_image', 'name', 'category', 'specifications'] as $field) {
                $rules[$field] = ['missing'];
            }
        }

        return $rules;
    }
}
