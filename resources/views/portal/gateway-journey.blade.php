@php
$total = $records->count();
$pad = fn (int $number): string => str_pad((string) $number, 2, '0', STR_PAD_LEFT);
$headingTag = $heroSection?->is($section) ? 'h1' : 'h2';
$unitTag = $headingTag === 'h1' ? 'h2' : 'h3';
$words = preg_split('/\s+/', trim((string) ($section->title ?: $site->resolvedName())));
$lastWord = count($words) > 1 ? array_pop($words) : null;
@endphp
<div class="journey-section" id="{{ $section->key }}">
@if($preview && !$section->is_active)<p class="demo-note shell">Section nonaktif</p>@endif
<div class="journey-intro"><div>
<div class="eyebrow"><span class="journey-cmyk" aria-hidden="true"></span> {{ $site->resolvedName() }}</div>
<{{ $headingTag }}>{{ implode(' ', $words) }}@if($lastWord) <em>{{ $lastWord }}</em>@endif</{{ $headingTag }}>
@if($section->subtitle)<p>{{ $section->subtitle }}</p>@endif
@if($total)<ul class="journey-chips" aria-label="Unit bisnis">@foreach($records as $unit)<li>{{ $unit->short_name }}</li>@endforeach</ul>@endif
</div>
@if($total)<div class="journey-cue" aria-hidden="true"><i></i>Gulir</div>@endif</div>
@if($records->isEmpty())
<p class="empty-state">Informasi unit bisnis sedang diperbarui.</p>
@else
<div class="journey" data-journey style="--count: {{ $total }}">
<div class="journey-stage"><div class="journey-floor"></div><div class="journey-world">
@foreach($records as $unit)
@php
$accent = preg_match('/^#[a-fA-F0-9]{6}$/', $unit->site?->primary_color ?? '') ? $unit->site->primary_color : 'var(--red)';
$logo = \App\Actions\Cms\MediaManager::url($unit->site?->logo_path);
$photo = \App\Actions\Cms\MediaManager::url($unit->banner_path) ?: asset('images/'.$unit->illustration.'.svg');
@endphp
<article class="journey-layer {{ $loop->even ? 'is-alt' : '' }}" id="company-{{ $unit->id }}" style="--c: {{ $accent }}" aria-label="{{ $unit->name }}">
<div class="journey-ghost" aria-hidden="true">{{ $pad($loop->iteration) }}</div>
<div class="journey-card">
<div class="journey-text">
<div class="eyebrow">{{ $pad($loop->iteration) }} / {{ $pad($total) }}</div>
<{{ $unitTag }}>{{ $unit->short_name }}</{{ $unitTag }}>
<p class="journey-field"><strong>{{ $unit->name }}</strong> · {{ $unit->sector }}</p>
<p class="journey-desc">{{ $unit->summary }}</p>
<a class="button" href="{{ route('company.show', $unit->slug) }}">Lihat profil <span aria-hidden="true">→</span></a>
</div>
<div class="journey-plate">
<div class="journey-photo"><img src="{{ $photo }}" alt="{{ $unit->sector }}" width="640" height="736" @if(!$loop->first) loading="lazy" @endif></div>
<div @class(['journey-logo', 'on-light' => $unit->site?->appearance() === 'light'])>@if($logo)<img src="{{ $logo }}" alt="{{ $unit->site->logo_alt ?: $unit->name }}" loading="lazy">@else<span aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($unit->short_name, 0, 2)) }}</span>@endif</div>
@if($unit->tagline)<div class="journey-badge">{{ $unit->tagline }}</div>@endif
</div>
</div>
</article>
@endforeach
</div></div>
<nav class="journey-rail" aria-label="Lompat ke unit bisnis">@foreach($records as $unit)<a href="#company-{{ $unit->id }}" data-journey-jump="{{ $loop->index }}"><span>{{ $unit->short_name }}</span><i></i></a>@endforeach</nav>
<div class="journey-count" aria-hidden="true"><b data-journey-count>01</b> / {{ $pad($total) }}</div>
<div class="journey-bar" aria-hidden="true"><i data-journey-bar></i></div>
</div>
@endif
</div>
