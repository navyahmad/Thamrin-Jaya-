<?php

namespace App\Http\Requests;

use App\Actions\Cms\ContentRules;
use App\Actions\Cms\MediaManager;
use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('company') ? Gate::allows('update', $this->route('company')) : Gate::allows('create', Company::class);
    }

    public function rules(): array
    {
        $company = $this->route('company');
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['required', 'string', 'max:80'],
            'slug' => [...ContentRules::slugRules(true), Rule::unique('companies')->ignore($company)],
            'sector' => ['required', 'string', 'max:120'],
            'summary' => ['required', 'string', 'max:600'],
            'tagline' => ['required', 'string', 'max:200'],
            'about' => ['nullable', 'string', 'max:10000'],
            'history' => ['nullable', 'string', 'max:5000'],
            'accent' => [$company?->site ? 'missing' : 'sometimes', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'illustration' => ['required', Rule::in(['boxes', 'carton', 'pouch', 'machine', 'rolls', 'print'])],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'regex:/^[+0-9() .-]+$/', 'max:40'],
            'whatsapp' => ['nullable', 'regex:/^[1-9][0-9]{7,14}$/'],
            'hours' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'map_query' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'is_active' => ['sometimes', 'boolean'],
            'is_demo' => ['sometimes', 'boolean'],
            'banner' => MediaManager::imageRules(),
            'about_image' => MediaManager::imageRules(),
            'remove_banner' => ['sometimes', 'boolean'],
            'remove_about_image' => ['sometimes', 'boolean'],
        ];
        if (! $company) {
            foreach (['banner', 'about_image', 'remove_banner', 'remove_about_image', 'is_active'] as $field) {
                $rules[$field] = ['missing'];
            }
        }

        return $rules;
    }
}
