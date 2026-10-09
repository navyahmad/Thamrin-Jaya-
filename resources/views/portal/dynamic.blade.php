@php
$primary = preg_match('/^#[a-fA-F0-9]{6}$/', $site->primary_color ?? '') ? $site->primary_color : '#2563eb';
$secondary = preg_match('/^#[a-fA-F0-9]{6}$/', $site->secondary_color ?? '') ? $site->secondary_color : '#101820';
$font = in_array($site->font_family, ['Inter','Roboto','Poppins','Montserrat','Arial'], true) ? $site->font_family.', sans-serif' : 'var(--font-sans)';
$title = $page->meta_title ?: ($site->seo_title ?: $site->resolvedName());
$description = $page->meta_description ?: ($site->seo_description ?: ($company?->summary ?? ''));
$heroSection = in_array($sections->first()?->type, ['gateway', 'group', 'carousel'], true) ? $sections->first() : null;
$luminance = function (string $hex): float {
    [$red, $green, $blue] = array_map(fn (string $channel): float => (hexdec($channel) / 255) <= .03928 ? (hexdec($channel) / 255) / 12.92 : ((hexdec($channel) / 255 + .055) / 1.055) ** 2.4, str_split(ltrim($hex, '#'), 2));

    return .2126 * $red + .7152 * $green + .0722 * $blue;
};
$onBrand = 1.05 / ($luminance($primary) + .05) >= ($luminance($primary) + .05) / .05 ? '#ffffff' : '#16130f';
$contactEmail = filter_var($contact['email'] ?? '', FILTER_VALIDATE_EMAIL) ? $contact['email'] : null;
$socialLinks = collect($site->social_links ?? [])->filter(fn ($url): bool => $url && preg_match('#^https?://#i', $url) && filter_var($url, FILTER_VALIDATE_URL));
@endphp
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $title }}</title><meta name="description" content="{{ $description }}"><meta name="theme-color" content="{{ $primary }}"><link rel="canonical" href="{{ $content->pageUrl($page) }}"><meta property="og:title" content="{{ $title }}"><meta property="og:description" content="{{ $description }}"><meta property="og:url" content="{{ $content->pageUrl($page) }}"><meta property="og:type" content="website">
@if($preview)<meta name="robots" content="noindex,nofollow">@endif
@if($og = \App\Actions\Cms\MediaManager::url($page->og_image_path ?: $site->logo_path))<meta property="og:image" content="{{ $og }}">@endif
@if($favicon = \App\Actions\Cms\MediaManager::url($site->favicon_path))<link rel="icon" href="{{ $favicon }}">@endif
@fonts
@vite(['resources/css/app.css', 'resources/js/app.js'])
<style>
.cms-public{--red:{{ $primary }};--on-brand:{{ $onBrand }};--brand-secondary:{{ $secondary }};font-family:{{ $font }}}
@if(($site->theme_settings['container_width'] ?? null) === 'wide').cms-public{--cms-width:1500px}@endif
@if(($site->theme_settings['button_style'] ?? null) === 'square').cms-public .button{border-radius:0}@endif
</style></head><body class="cms-public is-{{ $site->appearance() }} theme-{{ $template }}">
@if($preview)<div class="cms-preview" role="status">Preview admin — termasuk konten draft/nonaktif. Form pengiriman pesan tidak aktif. <a href="{{ route('admin.sites.pages.edit', [$site, $page]) }}">Kembali ke editor</a></div>@endif
<a class="skip-link" href="#main">Langsung ke konten</a>
<header class="cms-header"><div class="shell cms-header-bar"><a class="cms-brand" href="{{ $company ? route('company.show', $company->slug) : route('home') }}">@if($logo = \App\Actions\Cms\MediaManager::url($site->logo_path))<img class="brand-logo" src="{{ $logo }}" alt="{{ $site->logo_alt ?: $site->resolvedName() }}">@else<strong>{{ $site->resolvedName() }}</strong>@endif</a><button type="button" class="cms-menu-toggle" data-menu-toggle aria-controls="navigation" aria-expanded="false" aria-label="Menu"><span></span><span></span></button><nav id="navigation" class="cms-nav" aria-label="Navigasi utama">@include('portal.dynamic-menu', ['nodes' => $headerMenu])</nav></div></header>
<main id="main">
@if($company?->is_demo)<p class="cms-demo">Konten contoh · Perlu diverifikasi oleh perusahaan.</p>@endif
@unless($heroSection)<div class="shell cms-pagehead"><h1>{{ $page->title }}</h1></div>@endunless
<div class="shell cms-notices"><x-feedback/></div>
@foreach($sections as $section)
@php($records = $content->records($section, $site, $preview))
@include('portal.dynamic-section', ['band' => $loop->even])
@endforeach</main>
<footer class="cms-footer"><div class="shell">
<div class="cms-footer-main">
<div class="cms-footer-about"><strong>{{ $site->resolvedName() }}</strong>@if($site->footer_description)<p class="cms-copy">{{ $site->footer_description }}</p>@endif
@if($socialLinks->isNotEmpty())<div class="cms-footer-social">@foreach($socialLinks as $label => $url)<a href="{{ $url }}" target="_blank" rel="noopener noreferrer">{{ ucfirst($label) }} ↗</a>@endforeach</div>@endif</div>
@if($footerMenu)<nav class="cms-footer-links" aria-label="Navigasi footer"><span class="cms-footer-title">Jelajahi</span>@include('portal.dynamic-menu', ['nodes' => $footerMenu])</nav>@endif
@if(array_filter([$contact['address'] ?? null, $contact['phone'] ?? null, $contactEmail, $contact['hours'] ?? null]))<div class="cms-footer-contact"><span class="cms-footer-title">Kontak</span>@foreach(['address', 'hours', 'phone'] as $key)@if(!empty($contact[$key]))<p class="cms-copy">{{ $contact[$key] }}</p>@endif @endforeach @if($contactEmail)<a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>@endif</div>@endif
</div>
<div class="cms-footer-legal"><span>© {{ date('Y') }} {{ $site->copyright_text ?: $site->resolvedName() }}</span></div>
</div></footer></body></html>
