@extends('layouts.admin')
@section('title', 'Halaman '.$site->resolvedName())
@section('content')
<a class="text-link" href="{{ route('admin.sites.edit', $site) }}">← Pengaturan situs</a>
<div class="admin-heading"><div><h1>Halaman {{ $site->resolvedName() }}</h1><p>Atur judul, urutan, SEO, dan status publikasi. Halaman inti dapat dijadikan draft, tetapi tidak dapat dihapus.</p></div><a class="button" href="{{ route('admin.sites.pages.create', $site) }}">+ Tambah halaman</a></div>
<div class="panel">@forelse($pages as $page)
<div class="content-row"><span class="row-order">{{ $page->sort_order }}</span><div><strong>{{ $page->title }}</strong><p>{{ $page->slug }} · {{ $page->is_published ? 'Published' : 'Draft' }} @if(in_array($page->slug, \App\Actions\Cms\ContentRules::CORE_PAGES, true)) · Halaman inti @endif</p><small>{{ $page->sections_count }} section · {{ $page->menu_items_count }} tautan menu</small></div><a class="text-link" href="{{ route('admin.sites.pages.edit', [$site, $page]) }}">Edit halaman →</a></div>
@empty<p class="empty-state">Belum ada halaman.</p>@endforelse</div>
@endsection
