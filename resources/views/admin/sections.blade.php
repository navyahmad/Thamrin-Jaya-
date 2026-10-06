@extends('layouts.admin')
@section('title', 'Section '.$page->title)
@section('content')
<a class="text-link" href="{{ route('admin.sites.pages.edit', [$site, $page]) }}">← {{ $site->resolvedName() }} / {{ $page->title }}</a>
<div class="admin-heading"><div><h1>Section {{ $page->title }}</h1><p>Susun bagian halaman sesuai kebutuhan. Status halaman: {{ $page->is_published ? 'Published' : 'Draft' }}.</p></div><a class="button" href="{{ route('admin.sites.pages.sections.create', [$site, $page]) }}">+ Tambah section</a></div>
<div class="panel">@forelse($sections as $section)<div class="content-row"><span class="row-order">{{ $section->sort_order }}</span><div><strong>{{ $section->title ?? $section->key }}</strong><p>#{{ $section->key }} · {{ $section->type }} · {{ $section->is_active ? 'Aktif' : 'Nonaktif' }} · {{ $section->items_count }} item</p></div><a class="text-link" href="{{ route('admin.sites.pages.sections.edit', [$site, $page, $section]) }}">Edit →</a></div>@empty<p class="empty-state">Belum ada section.</p>@endforelse</div>
@if($sections->isNotEmpty())
<form class="panel form-panel" method="POST" action="{{ route('admin.sites.pages.sections.reorder', [$site, $page]) }}">@csrf @method('PUT')<h2>Urutan seluruh section</h2><p>Pilih satu section berbeda pada setiap posisi. Seluruh urutan disimpan sekaligus.</p>
@foreach($sections as $position => $current)<label class="field"><span>Posisi {{ $position + 1 }}</span><select name="ids[]">@foreach($sections as $option)<option value="{{ $option->id }}" @selected(old('ids.'.$position, $current->id) == $option->id)>{{ $option->title ?? $option->key }} (#{{ $option->key }})</option>@endforeach</select></label>@endforeach
<button class="button">Simpan urutan</button></form>@endif
@endsection
