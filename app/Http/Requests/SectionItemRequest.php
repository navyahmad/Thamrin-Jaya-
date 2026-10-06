<?php

namespace App\Http\Requests;

use App\Actions\Cms\ContentRegistry;
use App\Actions\Cms\MediaManager;
use App\Models\SectionItem;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SectionItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        ContentRegistry::assertItemSection($this->route('section'));

        return Gate::allows('update', $this->route('section'))
            && ($this->route('item') ? Gate::allows('update', $this->route('item')) : Gate::allows('create', SectionItem::class));
    }

    public function rules(): array
    {
        $section = $this->route('section');
        $fields = ContentRegistry::itemFields($section->type);
        $rules = [
            'page_section_id' => ['missing'], 'settings' => ['missing'], 'image_path' => ['missing'], 'file_path' => ['missing'],
            'key' => [$section->type === 'vision_mission' ? 'required' : 'nullable', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('section_items')->where('page_section_id', $section->id)->ignore($this->route('item'))],
            'title' => ['required', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'is_active' => ['sometimes', 'boolean'],
        ];
        if ($section->type === 'vision_mission') {
            $rules['key'][] = Rule::in(['vision', 'mission']);
        }
        $optional = [
            'subtitle' => ['nullable', 'string', 'max:1000'],
            'body' => [$section->type === 'vision_mission' ? 'required' : 'nullable', 'string', 'max:20000'],
            'value' => ['required', 'string', 'max:100'], 'unit' => ['nullable', 'string', 'max:100'],
            'image_alt' => ['nullable', 'string', 'max:255'],
            'occurred_on' => ['nullable', 'date_format:Y-m-d'],
            'issuer' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', Rule::in(['factory', 'quality', 'clock', 'price', 'leaf', 'award'])],
            'image' => [...MediaManager::imageRules(), Rule::prohibitedIf($this->boolean('remove_image'))],
            'file' => ['nullable', 'file', 'mimes:pdf', 'max:10240', Rule::prohibitedIf($this->boolean('remove_file'))],
            'link_label' => ['nullable', 'required_with:link_url', 'string', 'max:100'],
            'link_url' => ['nullable', ...($section->type !== 'clients' ? ['required_with:link_label'] : []), 'string', 'max:2048', function (string $attribute, mixed $value, Closure $fail): void {
                if (! is_string($value)) {
                    return;
                }
                if (str_starts_with($value, '#')) {
                    if (! $this->route('page')->sections()->where('key', substr($value, 1))->exists()) {
                        $fail('Anchor harus merujuk section pada halaman yang sama.');
                    }
                } elseif (Validator::make(['url' => $value], ['url' => ['url:http,https']])->fails()) {
                    $fail('Gunakan URL HTTP/HTTPS atau #anchor section pada halaman ini.');
                }
            }],
        ];
        foreach ($optional as $field => $constraints) {
            $rules[$field] = in_array($field, $fields, true) ? $constraints : ['missing'];
        }
        foreach (['image', 'file'] as $field) {
            $rules['remove_'.$field] = in_array($field, $fields, true) ? ['sometimes', 'boolean'] : ['missing'];
        }

        return $rules;
    }
}
