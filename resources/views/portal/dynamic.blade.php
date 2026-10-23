@php
$subsidiaryLayout = $site->company_id !== null;
$primary = preg_match('/^#[a-fA-F0-9]{6}$/', $site->primary_color ?? '') ? $site->primary_color : '#2563eb';
$secondary = preg_match('/^#[a-fA-F0-9]{6}$/', $site->secondary_color ?? '') ? $site->secondary_color : '#101820';
$font = in_array($site->font_family, ['Inter','Roboto','Poppins','Montserrat','Arial'], true) ? $site->font_family.', sans-serif' : 'var(--font-sans)';
$title = $page->meta_title ?: ($site->seo_title ?: $site->resolvedName());
$description = $page->meta_description ?: ($site->seo_description ?: ($company?->summary ?? ''));
$canonicalUrl = $onePage ? route('company.show', $company->slug) : $content->pageUrl($page);
$heroSection = in_array($sections->first()?->type, ['gateway', 'group', 'carousel'], true) ? $sections->first() : null;
$luminance = function (string $hex): float {
    [$red, $green, $blue] = array_map(fn (string $channel): float => (hexdec($channel) / 255) <= .03928 ? (hexdec($channel) / 255) / 12.92 : ((hexdec($channel) / 255 + .055) / 1.055) ** 2.4, str_split(ltrim($hex, '#'), 2));

    return .2126 * $red + .7152 * $green + .0722 * $blue;
};
$onBrand = 1.05 / ($luminance($primary) + .05) >= ($luminance($primary) + .05) / .05 ? '#ffffff' : '#16130f';
$contactEmail = filter_var($contact['email'] ?? '', FILTER_VALIDATE_EMAIL) ? $contact['email'] : null;
$footerWhatsapp = preg_match('/^[1-9][0-9]{7,14}$/', $contact['whatsapp'] ?? '') ? $contact['whatsapp'] : null;
$socialLinks = collect($site->social_links ?? [])->filter(fn ($url): bool => $url && preg_match('#^https?://#i', $url) && filter_var($url, FILTER_VALIDATE_URL));
@endphp
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $title }}</title><meta name="description" content="{{ $description }}"><meta name="theme-color" content="{{ $primary }}"><link rel="canonical" href="{{ $canonicalUrl }}"><meta property="og:title" content="{{ $title }}"><meta property="og:description" content="{{ $description }}"><meta property="og:url" content="{{ $canonicalUrl }}"><meta property="og:type" content="website">
@if($preview)<meta name="robots" content="noindex,nofollow">@endif
@if($og = \App\Actions\Cms\MediaManager::url($page->og_image_path ?: $site->logo_path))<meta property="og:image" content="{{ $og }}">@endif
@if($favicon = \App\Actions\Cms\MediaManager::url($site->favicon_path))<link rel="icon" href="{{ $favicon }}">@endif
@fonts
@vite(['resources/css/app.css', 'resources/js/app.js'])
<style>
.cms-public{--red:{{ $primary }};--on-brand:{{ $onBrand }};--brand-secondary:{{ $secondary }};font-family:{{ $font }}}
@if(($site->theme_settings['container_width'] ?? null) === 'wide').cms-public{--cms-width:1500px}@endif
@if(($site->theme_settings['button_style'] ?? null) === 'square').cms-public .button{border-radius:0}@endif
</style></head><body class="cms-public is-{{ $site->appearance() }} theme-{{ $template }}{{ $subsidiaryLayout ? ' layout-subsidiary' : '' }}">
@if($preview)<div class="cms-preview" role="status">Preview admin — termasuk konten draft/nonaktif. Form pengiriman pesan tidak aktif. <a href="{{ route('admin.sites.pages.edit', [$site, $page]) }}">Kembali ke editor</a></div>@endif
<a class="skip-link" href="#main">Langsung ke konten</a>
<header class="cms-header"><div class="shell cms-header-bar"><a class="cms-brand" href="{{ $company ? route('company.show', $company->slug) : route('home') }}">@if($logo = \App\Actions\Cms\MediaManager::url($site->logo_path))<img class="brand-logo" src="{{ $logo }}" alt="{{ $site->logo_alt ?: $site->resolvedName() }}">@else<strong>{{ $site->resolvedName() }}</strong>@endif</a><button type="button" class="cms-menu-toggle" data-menu-toggle aria-controls="navigation" aria-expanded="false" aria-label="Menu"><span></span><span></span></button><nav id="navigation" class="cms-nav" aria-label="Navigasi utama">@if($subsidiaryLayout)@include('portal.dynamic-menu', ['nodes' => $headerMenu])@else<a href="{{ route('group.services') }}" @if(request()->routeIs('group.services')) aria-current="page" @endif>Service</a>@endif</nav></div></header>
<main id="main">
@if($company?->is_demo)<p class="cms-demo">Konten contoh · Perlu diverifikasi oleh perusahaan.</p>@endif
@unless($heroSection)<div class="shell cms-pagehead"><h1>{{ $page->title }}</h1></div>@endunless
<div class="shell cms-notices"><x-feedback/></div>
@if($onePage)
@foreach($clusters as $clusterKey => $entries)
<div class="cms-cluster cms-cluster-{{ $clusterKey }}" id="cluster-{{ $clusterKey }}">
@foreach($entries as $entry)
@php($page = $entry['page'])
@php($section = $entry['section'])
@php($sectionAnchor = $entry['anchor'])
@php($records = $content->records($section, $site, $preview))
@include('portal.dynamic-section', ['band' => $loop->even])
@endforeach
</div>
@endforeach
@else
@foreach($sections as $section)
@php($sectionAnchor = $section->key)
@php($records = $content->records($section, $site, $preview))
@include('portal.dynamic-section', ['band' => $loop->even])
@endforeach
@endif
</main>
<footer class="cms-footer"><div class="shell">
<div class="cms-footer-main">
<div class="cms-footer-about"><div class="cms-footer-brand">@if($footerLogo = \App\Actions\Cms\MediaManager::url($site->logo_path))<img class="cms-footer-logo" src="{{ $footerLogo }}" alt="{{ $site->logo_alt ?: $site->resolvedName() }}">@endif<strong>{{ $site->resolvedName() }}</strong></div>@if($site->footer_description)<p class="cms-copy">{{ $site->footer_description }}</p>@endif
@if($socialLinks->isNotEmpty())<div class="cms-footer-social">@foreach($socialLinks as $label => $url)<a href="{{ $url }}" target="_blank" rel="noopener noreferrer">{{ ucfirst($label) }} ↗</a>@endforeach</div>@endif</div>
@if($footerMenu)<nav class="cms-footer-links" aria-label="Navigasi footer"><span class="cms-footer-title">Jelajahi</span>@include('portal.dynamic-menu', ['nodes' => $footerMenu])</nav>@endif
@if($footerWhatsapp || !empty($contact['phone']) || $contactEmail || !empty($contact['address']))<div class="cms-footer-contact"><span class="cms-footer-title">Kontak</span>
@if($footerWhatsapp)<a href="https://wa.me/{{ $footerWhatsapp }}" target="_blank" rel="noopener noreferrer">{{ $contact['phone'] ?? ('+'.$footerWhatsapp) }}</a>@elseif(!empty($contact['phone']))<p class="cms-copy">{{ $contact['phone'] }}</p>@endif
@if($contactEmail)<a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>@endif
@if(!empty($contact['address']))<p class="cms-copy">{{ $contact['address'] }}</p>@endif
</div>@endif
</div>
<div class="cms-footer-legal"><span>© {{ date('Y') }} {{ $site->copyright_text ?: $site->resolvedName() }}</span></div>
</div></footer>
@if($footerWhatsapp)<a class="wa-float" href="https://wa.me/{{ $footerWhatsapp }}?text={{ urlencode('Halo '.$site->resolvedName().', saya ingin bertanya lebih lanjut mengenai layanan Anda.') }}" target="_blank" rel="noopener noreferrer" aria-label="Chat WhatsApp {{ $site->resolvedName() }}"><svg viewBox="0 0 32 32" aria-hidden="true"><path d="M16 3C9.1 3 3.5 8.6 3.5 15.5c0 2.5.7 4.8 1.9 6.8L3 29l6.9-2.3c1.9 1 4 1.6 6.1 1.6 6.9 0 12.5-5.6 12.5-12.5S22.9 3 16 3zm0 22.7c-2 0-3.9-.5-5.5-1.5l-.4-.2-4.1 1.4 1.3-4-.3-.4a10.2 10.2 0 0 1-1.6-5.5C5.4 9.7 10.2 5 16 5s10.6 4.7 10.6 10.5S21.8 25.7 16 25.7zm5.8-7.9c-.3-.2-1.9-.9-2.1-1-.3-.1-.5-.2-.7.2-.2.3-.8 1-1 1.2-.2.2-.4.2-.7.1-.3-.2-1.3-.5-2.5-1.6-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6.1-.1.3-.4.4-.5.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5 0-.2-.7-1.7-1-2.3-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.4s1 2.8 1.2 3c.1.2 2 3.1 4.9 4.3.7.3 1.2.5 1.6.6.7.2 1.3.2 1.8.1.5-.1 1.9-.8 2.1-1.5.3-.7.3-1.3.2-1.5-.1-.1-.3-.2-.6-.4z"/></svg></a>@endif
</body></html>
