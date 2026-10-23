@if($subsidiaryLayout && $section->type === 'group')
@include('portal.subsidiary-group')
@elseif(in_array($section->type, ['gateway', 'group'], true))
@include('portal.gateway-journey')
@elseif($section->type === 'carousel' && $heroSection?->is($section))
@include('portal.hero')
@else
<section @class(['cms-section', 'cms-section-'.$section->type, 'cms-band' => $band]) id="{{ $sectionAnchor }}">
<div class="shell">
@if($preview && !$section->is_active)<p class="demo-note">Section nonaktif</p>@endif
@if($section->title || $section->subtitle)<div class="cms-section-head">@if($section->title)<h2>{{ $section->title }}</h2>@endif @if($section->subtitle && !($section->type === 'map' && $section->subtitle === \App\Actions\Cms\GlobalindoPilot::DEMO_MAP_SUBTITLE && !$content->demoMapPlaceholder($section, $site)))<p>{{ $section->subtitle }}</p>@endif</div>@endif
@if($subsidiaryLayout && in_array($section->type, ['clients', 'capacity', 'csr', 'vision_mission', 'certifications'], true))
@include('portal.subsidiary-items')
@else
@switch($section->type)
@case('about') @case('history')
@php($narrative = $content->narrative($section, $site))
<div @class(['cms-about', 'has-image' => filled($narrative['image'])])>
@if($image = \App\Actions\Cms\MediaManager::url($narrative['image']))<img class="cms-about-image" src="{{ $image }}" alt="{{ $company?->name ?? $section->image_alt }}" loading="lazy">@endif
<div><p class="cms-statement cms-copy">{{ $narrative['body'] }}</p>
@if(!$company && ($link = $content->link($section->button_url, $page, $preview)))<a class="button" href="{{ $link }}">{{ $section->button_label }}</a>@endif</div></div>
@break
@case('products')
<div data-catalog><label class="filter-label">Kategori<select data-product-filter><option value="all">Semua kategori</option>@foreach($records->pluck('category')->unique() as $category)<option value="{{ $category }}">{{ $category }}</option>@endforeach</select></label>
<div class="cms-cards">@foreach($records as $product)<article class="cms-card" data-category="{{ $product->category }}">@php($image = \App\Actions\Cms\MediaManager::url($product->image_path)) @php($illustration = !$image && $company && in_array($company->illustration, ['boxes', 'carton', 'pouch', 'machine', 'rolls', 'print'], true) ? asset('images/'.$company->illustration.'.svg') : null) @if($image)<img src="{{ $image }}" alt="{{ $product->name }}" loading="lazy">@elseif($subsidiaryLayout)<div class="gp-placeholder gp-catalog-placeholder"><span aria-hidden="true">▧</span><span>Foto belum tersedia</span></div>@elseif($illustration)<div class="cms-media-fallback"><img src="{{ $illustration }}" alt="Ilustrasi bidang {{ $company->sector }}" loading="lazy"><span>Ilustrasi bidang</span></div>@endif<span class="cms-kicker">{{ $product->category }}</span><h3>{{ $product->name }}</h3><button type="button" class="cms-link" data-open-dialog="product-{{ $section->id }}-{{ $product->id }}">Lihat detail <span aria-hidden="true">›</span></button><dialog class="preview-dialog" id="product-{{ $section->id }}-{{ $product->id }}" aria-label="{{ $product->name }}"><button class="dialog-close" data-close-dialog aria-label="Tutup">×</button><div class="preview-content">@if($subsidiaryLayout && $image)<img class="gp-product-detail-image" src="{{ $image }}" alt="{{ $product->name }}" loading="lazy">@endif<span class="cms-kicker">{{ $product->category }}</span><h2>{{ $product->name }}</h2><p class="cms-copy">{{ $product->description }}</p><h3>Spesifikasi</h3><p class="cms-copy">{{ $product->specifications }}</p>@if($contactUrl = $content->link('#contact', $page, $preview))<a class="button" href="{{ $contactUrl }}">Tanyakan produk <span aria-hidden="true">→</span></a>@endif</div></dialog></article>@endforeach</div></div>
@break
@case('pillars') @case('process')
<ol class="cms-cards">@foreach($records as $record)<li class="cms-card">
@if($section->type === 'process' && $subsidiaryLayout)
@if($image = \App\Actions\Cms\MediaManager::url($record->image_path))<img src="{{ $image }}" alt="{{ $record->image_alt ?: $record->title }}" loading="lazy">@endif
@if($company?->is_demo || Str::contains(Str::lower($record->description), ['contoh', 'dapat disesuaikan']))<p class="demo-note">Contoh proses — belum diverifikasi sebagai proses resmi.</p>@endif
@endif
<span class="cms-number">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><h3>{{ $record->title }}</h3><p class="cms-copy">{{ $record->description }}</p></li>@endforeach</ol>
@break
@case('services')
@if($subsidiaryLayout)
<div class="cms-cards">@foreach($records as $service)<article class="cms-card" id="{{ $sectionAnchor }}-service-{{ $service->id }}">@php($image = \App\Actions\Cms\MediaManager::url($service->image_path)) @php($illustration = !$image && $company && in_array($company->illustration, ['boxes', 'carton', 'pouch', 'machine', 'rolls', 'print'], true) ? asset('images/'.$company->illustration.'.svg') : null) @if($image)<img src="{{ $image }}" alt="{{ $service->name }}" loading="lazy">@elseif($subsidiaryLayout)<div class="gp-placeholder gp-catalog-placeholder"><span aria-hidden="true">▧</span><span>Foto belum tersedia</span></div>@elseif($illustration)<div class="cms-media-fallback"><img src="{{ $illustration }}" alt="Ilustrasi bidang {{ $company->sector }}" loading="lazy"><span>Ilustrasi bidang</span></div>@endif<h3>{{ $service->name }}</h3>@if($service->description)<p class="cms-copy">{{ $service->description }}</p>@endif<div class="cms-links">@foreach($service->companies as $unit)@if(!$company || $company->id === $unit->id)<a class="cms-link" href="{{ route('company.show', $unit->slug) }}">{{ $unit->short_name }} <span aria-hidden="true">›</span></a>@endif @endforeach</div></article>@endforeach</div>
@else
<div class="cms-showcase-banner">
<div class="cms-showcase-copy">
<span class="cms-kicker">Katalog &amp; Layanan Grup</span>
<h3>Jelajahi produk dan layanan dari enam perusahaan kami</h3>
<p class="cms-copy">Temukan 30 produk dan layanan mulai dari material rokok, percetakan kemasan, mesin industri, hingga konversi karton dalam tampilan showcase interaktif.</p>
@if($records->isNotEmpty())<ul class="cms-showcase-tags" aria-label="Bidang layanan">@foreach($records as $service)<li id="{{ $sectionAnchor }}-service-{{ $service->id }}">{{ $service->name }}</li>@endforeach</ul>@endif
</div>
<div class="cms-showcase-action">
<a class="button" href="{{ route('group.services') }}">Lihat Showcase Layanan <span aria-hidden="true">→</span></a>
</div>
</div>
@endif
@break
@case('map')
@if(!empty($contact['map_query']))<div class="map-container"><iframe title="Lokasi {{ $site->resolvedName() }}" src="https://maps.google.com/maps?q={{ rawurlencode($contact['map_query']) }}&output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe></div>
@elseif($content->demoMapPlaceholder($section, $site))<div class="cms-card gp-map-placeholder"><p>Placeholder peta — bukan lokasi Globalindo.</p><p>Lokasi perusahaan belum ditentukan. Peta akan muncul setelah lokasi resmi diisi melalui CMS.</p></div>@endif
@break
@case('contact')
@include('portal.dynamic-contact')
@break
@default
<div class="{{ $section->type === 'carousel' ? 'cms-slides' : 'cms-cards' }}">@foreach($records as $item)<article class="cms-card">@if($preview && !$item->is_active)<span class="demo-note">Item nonaktif</span>@endif
@if($image = \App\Actions\Cms\MediaManager::url($item->image_path))<img src="{{ $image }}" alt="{{ $item->image_alt ?: $item->title }}" loading="lazy">@endif
@if($section->type === 'vision_mission')<span class="cms-kicker">{{ $item->key === 'vision' ? 'Visi' : 'Misi' }}</span>@endif
<h3>{{ $item->title }}</h3>@if($item->subtitle)<p>{{ $item->subtitle }}</p>@endif
@if($section->type === 'capacity')@php($capacityIcon = match($item->settings['icon'] ?? null) { 'factory' => '▦', 'quality' => '✓', 'clock' => '◷', 'price' => '¤', 'leaf' => '❧', 'award' => '★', default => null })<strong class="cms-figure">@if($capacityIcon)<span class="cms-capacity-icon" aria-hidden="true">{{ $capacityIcon }}</span>@endif{{ $item->value }} {{ $item->unit }}</strong>@endif
@if($item->body)<p class="cms-copy">{{ $item->body }}</p>@endif
@if($item->occurred_on)<time datetime="{{ $item->occurred_on->format('Y-m-d') }}">{{ $item->occurred_on->format('d M Y') }}</time>@endif
@if($section->type === 'certifications')<p>{{ $item->settings['issuer'] ?? '' }}</p>@if($pdf = \App\Actions\Cms\MediaManager::url($item->file_path))<a class="cms-link" href="{{ $pdf }}" target="_blank" rel="noopener noreferrer">Lihat sertifikat PDF ↗</a>@endif @endif
@if($link = $content->link($item->link_url, $page, $preview))<a class="cms-link" href="{{ $link }}">{{ $item->link_label ?: 'Selengkapnya' }} <span aria-hidden="true">›</span></a>@endif</article>@endforeach</div>
@endswitch
@endif
</div>
</section>
@endif
