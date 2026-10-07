<section class="cms-hero" id="{{ $section->key }}" aria-label="{{ $section->title ?: $page->title }}">
@if($preview && !$section->is_active)<p class="demo-note shell">Section nonaktif</p>@endif
<div class="cms-hero-track" @if($records->count() > 1) tabindex="0" role="region" aria-label="{{ $section->title ?: $page->title }}" @endif>
@forelse($records as $item)
<div class="cms-hero-slide">
<div class="shell cms-hero-copy">
@if($company?->sector)<p class="cms-kicker">{{ $company->sector }}</p>@endif
<{{ $loop->first ? 'h1' : 'h2' }}>{{ $item->title }}</{{ $loop->first ? 'h1' : 'h2' }}>
@if($item->subtitle)<p class="cms-hero-sub">{{ $item->subtitle }}</p>@endif
@if($item->body)<p class="cms-hero-lead cms-copy">{{ $item->body }}</p>@endif
@if($link = $content->link($item->link_url, $page, $preview))<div class="cms-hero-actions"><a class="button" href="{{ $link }}">{{ $item->link_label ?: 'Selengkapnya' }} <span aria-hidden="true">→</span></a></div>@endif
</div>
@if($image = \App\Actions\Cms\MediaManager::url($item->image_path))<div class="shell"><img class="cms-hero-image" src="{{ $image }}" alt="{{ $item->image_alt ?: $item->title }}" @if(!$loop->first) loading="lazy" @endif></div>@endif
</div>
@empty
<div class="cms-hero-slide"><div class="shell cms-hero-copy"><h1>{{ $section->title ?: $page->title }}</h1></div></div>
@endforelse
</div>
</section>
