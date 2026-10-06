<section class="shell section-space" id="{{ $section->key }}">
@if($preview && !$section->is_active)<p class="demo-note">Section nonaktif</p>@endif
<div class="section-heading"><div>@if($section->title)<h2>{{ $section->title }}</h2>@endif @if($section->subtitle)<p>{{ $section->subtitle }}</p>@endif</div></div>
@switch($section->type)
@case('gateway') @case('group')
@if($records->isEmpty())<p>Informasi unit bisnis sedang diperbarui.</p>@endif
<div class="cms-cards cms-gateway">@foreach($records as $unit)<article class="cms-card" id="company-{{ $unit->id }}">@if($banner = \App\Actions\Cms\MediaManager::url($unit->banner_path))<img src="{{ $banner }}" alt="{{ $unit->name }}" loading="lazy">@endif @if($logo = \App\Actions\Cms\MediaManager::url($unit->site->logo_path))<img class="brand-logo" src="{{ $logo }}" alt="{{ $unit->site->logo_alt ?: $unit->name }}" loading="lazy">@endif<h3>{{ $unit->short_name }}</h3><p>{{ $unit->sector }}</p><button class="button button-outline" data-open-dialog="unit-{{ $section->id }}-{{ $unit->id }}">Explore</button><dialog class="preview-dialog" id="unit-{{ $section->id }}-{{ $unit->id }}" aria-label="{{ $unit->name }}"><button class="dialog-close" data-close-dialog aria-label="Tutup">×</button><div class="preview-content"><h2>{{ $unit->name }}</h2><p>{{ $unit->summary }}</p><a class="button" href="{{ route('company.show', $unit->slug) }}">Read More →</a></div></dialog></article>@endforeach</div>
@break
@case('about') @case('history')
@php($body = $company ? ($section->type === 'about' ? $company->about : $company->history) : $section->body)
@php($picture = $company ? ($section->type === 'about' ? $company->about_image_path : null) : $section->image_path)
@if($image = \App\Actions\Cms\MediaManager::url($picture))<img class="upload-preview" src="{{ $image }}" alt="{{ $company?->name ?? $section->image_alt }}" loading="lazy">@endif<p class="cms-copy">{{ $body }}</p>
@if(!$company && ($link = $content->link($section->button_url, $page, $preview)))<a class="button" href="{{ $link }}">{{ $section->button_label }}</a>@endif
@break
@case('products')
<div data-catalog><label class="filter-label">Kategori<select data-product-filter><option value="all">Semua kategori</option>@foreach($records->pluck('category')->unique() as $category)<option value="{{ $category }}">{{ $category }}</option>@endforeach</select></label>
<div class="cms-cards">@foreach($records as $product)<article class="cms-card" data-category="{{ $product->category }}">@if($image = \App\Actions\Cms\MediaManager::url($product->image_path))<img src="{{ $image }}" alt="{{ $product->name }}" loading="lazy">@endif<h3>{{ $product->name }}</h3><p>{{ $product->category }}</p><button class="button button-outline" data-open-dialog="product-{{ $section->id }}-{{ $product->id }}">Lihat detail</button><dialog class="preview-dialog" id="product-{{ $section->id }}-{{ $product->id }}" aria-label="{{ $product->name }}"><button class="dialog-close" data-close-dialog aria-label="Tutup">×</button><div class="preview-content"><h2>{{ $product->name }}</h2><p class="cms-copy">{{ $product->description }}</p><h3>Spesifikasi</h3><p class="cms-copy">{{ $product->specifications }}</p>@if($contactUrl = $content->link('#contact', $page, $preview))<a class="button" href="{{ $contactUrl }}">Tanyakan produk →</a>@endif</div></dialog></article>@endforeach</div></div>
@break
@case('pillars') @case('process')
<ol class="cms-cards">@foreach($records as $record)<li class="cms-card"><span>{{ $loop->iteration }}</span><h3>{{ $record->title }}</h3><p class="cms-copy">{{ $record->description }}</p></li>@endforeach</ol>
@break
@case('services')
<div class="cms-cards">@foreach($records as $service)<article class="cms-card" id="{{ $section->key }}-service-{{ $service->id }}"><h3>{{ $service->name }}</h3>@if($image = \App\Actions\Cms\MediaManager::url($service->image_path))<img src="{{ $image }}" alt="{{ $service->name }}" loading="lazy">@endif<p class="cms-copy">{{ $service->description }}</p>@foreach($service->companies as $unit)@if(!$company || $company->id === $unit->id)<a class="text-link" href="{{ route('company.show', $unit->slug) }}">{{ $unit->short_name }} →</a>@endif @endforeach</article>@endforeach</div>
@break
@case('map')
@if(!empty($contact['map_query']))<div class="map-container"><iframe title="Lokasi {{ $site->resolvedName() }}" src="https://maps.google.com/maps?q={{ rawurlencode($contact['map_query']) }}&output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe></div>@endif
@break
@case('contact')
@include('portal.dynamic-contact')
@break
@default
<div class="{{ $section->type === 'carousel' ? 'cms-slides' : 'cms-cards' }}">@foreach($records as $item)<article class="cms-card">@if($preview && !$item->is_active)<span class="demo-note">Item nonaktif</span>@endif
@if($image = \App\Actions\Cms\MediaManager::url($item->image_path))<img src="{{ $image }}" alt="{{ $item->image_alt ?: $item->title }}" loading="lazy">@endif
<h3>{{ $item->title }}</h3>@if($item->subtitle)<p>{{ $item->subtitle }}</p>@endif
@if($section->type === 'capacity')<strong>{{ $item->value }} {{ $item->unit }}</strong>@endif
@if($item->body)<p class="cms-copy">{{ $item->body }}</p>@endif
@if($item->occurred_on)<time datetime="{{ $item->occurred_on->format('Y-m-d') }}">{{ $item->occurred_on->format('d M Y') }}</time>@endif
@if($section->type === 'certifications')<p>{{ $item->settings['issuer'] ?? '' }}</p>@if($pdf = \App\Actions\Cms\MediaManager::url($item->file_path))<a class="text-link" href="{{ $pdf }}" target="_blank" rel="noopener noreferrer">Lihat sertifikat PDF ↗</a>@endif @endif
@if($link = $content->link($item->link_url, $page, $preview))<a class="button button-outline" href="{{ $link }}">{{ $item->link_label ?: 'Selengkapnya' }}</a>@endif</article>@endforeach</div>
@endswitch
</section>
