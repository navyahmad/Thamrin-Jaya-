<?php

namespace App\Http\Requests;

use App\Actions\Cms\ContentRegistry;
use App\Actions\Cms\MediaManager;
use App\Models\PageSection;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('page'))
            && ($this->route('section') ? Gate::allows('update', $this->route('section')) : Gate::allows('create', PageSection::class));
    }

    public function rules(): array
    {
        $section = $this->route('section');
        $subsidiary = $this->route('site')->company_id !== null;
        $type = is_string($this->input('type')) ? $this->input('type') : '';
        $manual = in_array($type, ['about', 'history'], true) && ! $subsidiary;
        $types = ContentRegistry::availableSectionTypes($subsidiary);
        if ($section && ! $subsidiary && in_array($section->type, ['products', 'pillars', 'process'], true)) {
            $types[] = $section->type;
        }

        return [
            'page_id' => ['missing'], 'settings' => ['missing'], 'image_path' => ['missing'],
            'key' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('page_sections')->where('page_id', $this->route('page')->id)->ignore($section), ...($section ? [Rule::in([$section->key])] : [])],
            'type' => ['required', Rule::in($types)],
            'variant' => ['required', Rule::in(['default'])],
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'is_active' => ['sometimes', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'body' => [$manual ? 'nullable' : 'missing', 'string', 'max:20000'],
            'image' => $manual ? [...MediaManager::imageRules(), Rule::prohibitedIf($this->boolean('remove_image'))] : ['missing'],
            'remove_image' => [$manual ? 'sometimes' : 'missing', 'boolean'],
            'image_alt' => [$manual ? 'nullable' : 'missing', 'string', 'max:255'],
            'button_label' => [$manual ? 'nullable' : 'missing', 'required_with:button_url', 'string', 'max:100'],
            'button_url' => [$manual ? 'nullable' : 'missing', 'required_with:button_label', 'string', 'max:2048', function (string $attribute, mixed $value, Closure $fail): void {
                if (! is_string($value)) {
                    return;
                }
                if (str_starts_with($value, '#')) {
                    $key = substr($value, 1);
                    if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $key) || ($key !== $this->input('key') && ! $this->route('page')->sections()->where('key', $key)->exists())) {
                        $fail('Anchor harus menunjuk section pada halaman yang sama.');
                    }
                } elseif (Validator::make(['url' => $value], ['url' => ['url:http,https']])->fails()) {
                    $fail('Gunakan URL HTTP/HTTPS atau anchor section pada halaman ini.');
                }
            }],
        ];
    }
}
