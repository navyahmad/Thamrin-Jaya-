<?php

namespace App\Actions\Cms;

use App\Models\PageSection;
use App\Models\SectionItem;
use App\Models\Site;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ContentRegistry
{
    public const TEMPLATES = ['default', 'group-gateway', 'globalindo', 'multipack', 'hte-rotopack', 'maxtech', 'sinar-jaya', 'top-printing'];

    /** Brand skins whose logos are drawn in dark ink and need a light surface; every other public site is dark. */
    public const LIGHT_SURFACE_TEMPLATES = ['default', 'globalindo', 'top-printing'];

    public const TYPES = ['gateway', 'carousel', 'about', 'history', 'services', 'group', 'products', 'pillars', 'process', 'clients', 'capacity', 'csr', 'vision_mission', 'certifications', 'map', 'contact'];

    public static function validateSite(Site $site): void
    {
        $site->template_key ??= 'default';
        Validator::make($site->getAttributes(), [
            'template_key' => ['required', Rule::in(self::TEMPLATES)],
            'primary_color' => ['sometimes', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'secondary_color' => ['sometimes', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'font_family' => ['nullable', Rule::in(['Inter', 'Roboto', 'Poppins', 'Montserrat', 'Arial', 'sans-serif'])],
        ])->validate();
        if ($site->company_id !== null && $site->template_key === 'group-gateway') {
            throw ValidationException::withMessages(['template_key' => 'Template gateway hanya untuk holding.']);
        }
        if ($site->company_id === null && ! in_array($site->template_key, ['default', 'group-gateway'], true)) {
            throw ValidationException::withMessages(['template_key' => 'Template ini khusus anak perusahaan.']);
        }
        Validator::make(['theme_settings' => $site->theme_settings], [
            'theme_settings' => ['nullable', 'array:container_width,button_style'],
            'theme_settings.container_width' => ['sometimes', Rule::in(['normal', 'wide'])],
            'theme_settings.button_style' => ['sometimes', Rule::in(['rounded', 'square'])],
        ])->validate();
    }

    /** @return list<string> */
    public static function availableSectionTypes(bool $subsidiary): array
    {
        return array_values(array_diff(self::TYPES, $subsidiary ? ['gateway'] : ['products', 'pillars', 'process']));
    }

    public static function source(string $type, bool $subsidiary): string
    {
        return match ($type) {
            'about', 'history', 'products', 'pillars', 'process' => $subsidiary ? 'company' : 'manual',
            'gateway', 'services', 'group' => 'relations',
            'map', 'contact' => 'contact_details',
            default => 'items',
        };
    }

    /** @return array<string, array<int, mixed>> */
    public static function sectionRules(bool $subsidiary, string $type): array
    {
        return [
            'type' => ['required', Rule::in(self::TYPES)],
            'variant' => ['required', Rule::in(['default'])],
            'settings' => ['required', 'array:source,limit'],
            'settings.source' => ['required', Rule::in([self::source($type, $subsidiary)])],
            'settings.limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public static function validateSection(PageSection $section): void
    {
        $site = $section->page()->firstOrFail()->site()->firstOrFail();
        $subsidiary = $site->company_id !== null;
        $section->variant ??= 'default';
        $section->settings ??= ['source' => self::source($section->type, $subsidiary)];
        Validator::make([
            'type' => $section->type, 'variant' => $section->variant, 'settings' => $section->settings,
        ], self::sectionRules($subsidiary, $section->type))->validate();

        $legacyPlaceholder = ! $subsidiary && in_array($section->type, ['products', 'pillars', 'process'], true);
        if (($subsidiary && $section->type === 'gateway') || ($legacyPlaceholder && ($section->is_active ?? true))) {
            throw ValidationException::withMessages(['type' => 'Jenis section tidak tersedia untuk situs ini.']);
        }
        if ($section->exists && ($section->isDirty('type') || $section->isDirty('variant'))
            && ($section->items()->exists() || $section->body || $section->image_path || $section->button_url
                || $section->getOriginal('body') || $section->getOriginal('image_path') || $section->getOriginal('button_url'))) {
            throw ValidationException::withMessages(['type' => 'Section sudah berisi konten. Buat section baru untuk jenis yang berbeda.']);
        }
    }

    /** @return list<string> */
    public static function itemFields(string $type): array
    {
        return match ($type) {
            'carousel' => ['subtitle', 'body', 'image', 'image_alt', 'link_label', 'link_url'],
            'clients' => ['image', 'image_alt', 'link_url'],
            'capacity' => ['value', 'unit', 'icon'],
            'csr' => ['body', 'image', 'image_alt', 'occurred_on', 'link_label', 'link_url'],
            'vision_mission' => ['body'],
            'certifications' => ['body', 'image', 'image_alt', 'file', 'issuer', 'occurred_on'],
            default => [],
        };
    }

    public static function assertItemSection(PageSection $section): void
    {
        abort_unless(($section->settings['source'] ?? null) === 'items' && self::itemFields($section->type) !== [], 404);
    }

    public static function validateItem(SectionItem $item): void
    {
        $section = $item->section()->firstOrFail();
        if (($section->settings['source'] ?? null) !== 'items') {
            throw ValidationException::withMessages(['page_section_id' => 'Section ini mengambil data dari sumber utama, bukan item manual.']);
        }
        $keys = $section->type === 'certifications' ? 'icon,issuer' : 'icon';
        Validator::make(['settings' => $item->settings], [
            'settings' => ['nullable', 'array:'.$keys],
            'settings.icon' => ['sometimes', Rule::in(['factory', 'quality', 'clock', 'price', 'leaf', 'award'])],
            'settings.issuer' => ['sometimes', 'string', 'max:255'],
        ])->validate();
    }
}
