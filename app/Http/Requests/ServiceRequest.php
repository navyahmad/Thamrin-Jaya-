<?php

namespace App\Http\Requests;

use App\Actions\Cms\ContentRules;
use App\Actions\Cms\MediaManager;
use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('service') ? Gate::allows('update', $this->route('service')) : Gate::allows('create', Service::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [...ContentRules::slugRules(), Rule::unique('services')->ignore($this->route('service'))],
            'description' => ['nullable', 'string', 'max:10000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'is_active' => ['sometimes', 'boolean'],
            'image_path' => ['missing'],
            'image' => [...MediaManager::imageRules(), Rule::prohibitedIf($this->boolean('remove_image'))],
            'remove_image' => ['sometimes', 'boolean'],
            'companies' => ['sometimes', 'array', 'max:1000'],
            'companies.*' => ['required', 'array:id,selected,sort_order'],
            'companies.*.id' => ['required', 'integer', 'distinct', Rule::exists('companies', 'id')],
            'companies.*.selected' => ['sometimes', 'boolean'],
            'companies.*.sort_order' => ['required', 'integer', 'min:0', 'max:999'],
        ];
    }
}
