<?php

namespace App\Http\Requests;

use App\Actions\Cms\ContentRules;
use App\Actions\Cms\MediaManager;
use App\Models\Page;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('site'))
            && ($this->route('page') ? Gate::allows('update', $this->route('page')) : Gate::allows('create', Page::class));
    }

    public function rules(): array
    {
        $page = $this->route('page');
        $slugRules = [...ContentRules::slugRules(), Rule::unique('pages', 'slug')->where('site_id', $this->route('site')->id)->ignore($page)];
        if ($page && in_array($page->slug, ContentRules::CORE_PAGES, true)) {
            $slugRules[] = Rule::in([$page->slug]);
        } else {
            $slugRules[] = Rule::notIn(ContentRules::CORE_PAGES);
        }

        return [
            'site_id' => ['missing'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => $slugRules,
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'og_image_path' => ['missing'],
            'og_image' => [...MediaManager::imageRules(), Rule::prohibitedIf($this->boolean('remove_og_image'))],
            'remove_og_image' => ['sometimes', 'boolean'],
            'is_published' => ['sometimes', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
        ];
    }
}
