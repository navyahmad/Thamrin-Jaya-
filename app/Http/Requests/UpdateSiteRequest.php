<?php

namespace App\Http\Requests;

use App\Actions\Cms\ContentRegistry;
use App\Actions\Cms\MediaManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('site'));
    }

    public function rules(): array
    {
        $holding = $this->route('site')->company_id === null;
        $rules = [
            'company_id' => ['missing'], 'slug' => ['missing'],
            'name' => [$holding ? 'required' : 'missing', 'string', 'max:255'],
            'logo_path' => ['missing'], 'favicon_path' => ['missing'],
            'logo' => MediaManager::imageRules(), 'favicon' => MediaManager::imageRules(),
            'remove_logo' => ['sometimes', 'boolean'], 'remove_favicon' => ['sometimes', 'boolean'],
            'logo_alt' => ['nullable', 'string', 'max:255'],
            'template_key' => ['required', Rule::in($holding ? ['default', 'group-gateway'] : array_diff(ContentRegistry::TEMPLATES, ['group-gateway']))],
            'primary_color' => ['required', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'secondary_color' => ['required', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'font_family' => ['required', Rule::in(['Inter', 'Roboto', 'Poppins', 'Montserrat', 'Arial', 'sans-serif'])],
            'theme_settings' => ['nullable', 'array:container_width,button_style'],
            'theme_settings.container_width' => ['sometimes', Rule::in(['normal', 'wide'])],
            'theme_settings.button_style' => ['sometimes', Rule::in(['rounded', 'square'])],
            'footer_description' => ['nullable', 'string', 'max:2000'],
            'copyright_text' => ['nullable', 'string', 'max:255'],
            'seo_title' => ['nullable', 'string', 'max:255'], 'seo_description' => ['nullable', 'string', 'max:500'],
            'social_links' => ['nullable', 'array:instagram,facebook,linkedin,youtube,tiktok,website'],
            'social_links.*' => ['nullable', 'url:http,https', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
            'contact_details' => [$holding ? 'nullable' : 'missing', 'array:email,phone,whatsapp,hours,address,map_query'],
        ];
        foreach (['email' => ['email', 'max:255'], 'phone' => ['regex:/^[+0-9() .-]+$/', 'max:40'], 'whatsapp' => ['regex:/^[1-9][0-9]{7,14}$/'], 'hours' => ['string', 'max:255'], 'address' => ['string', 'max:1000'], 'map_query' => ['string', 'max:255']] as $key => $constraints) {
            $rules['contact_details.'.$key] = ['nullable', ...$constraints];
        }

        return $rules;
    }
}
