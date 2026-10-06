<?php

namespace App\Http\Requests;

use App\Models\MenuItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class MenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('item') ? Gate::allows('update', $this->route('item')) : Gate::allows('create', MenuItem::class);
    }

    public function rules(): array
    {
        $menu = $this->route('menu');
        $site = $this->route('site');
        $serviceIds = $site->company_id ? $site->company->services()->pluck('services.id')->all() : null;

        return [
            'menu_id' => ['missing'], 'site_id' => ['missing'],
            'key' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('menu_items')->where('menu_id', $menu->id)->ignore($this->route('item'))],
            'label' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['link', 'mega_menu'])],
            'target' => ['required', Rule::in(['page', 'service', 'url', 'heading'])],
            'parent_id' => ['nullable', 'integer', Rule::exists('menu_items', 'id')->where('menu_id', $menu->id)],
            'page_id' => ['nullable', 'required_if:target,page', 'prohibited_unless:target,page', 'integer', Rule::exists('pages', 'id')->where('site_id', $site->id)],
            'anchor' => ['nullable', 'prohibited_unless:target,page', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::exists('page_sections', 'key')->where('page_id', is_scalar($this->input('page_id')) ? $this->input('page_id') : null)],
            'service_id' => ['nullable', 'required_if:target,service', 'prohibited_unless:target,service', 'integer', Rule::exists('services', 'id'), ...($serviceIds !== null ? [Rule::in($serviceIds)] : [])],
            'url' => ['nullable', 'required_if:target,url', 'prohibited_unless:target,url', 'url:http,https', 'max:2048'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'is_active' => ['sometimes', 'boolean'], 'open_in_new_tab' => ['sometimes', 'boolean'],
        ];
    }
}
