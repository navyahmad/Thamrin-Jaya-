@extends('layouts.admin')
@section('title', 'Kelola konten '.$company->short_name)
@section('content')
@php($label = ['products' => 'Katalog produk', 'pillars' => 'Pilar keunggulan', 'process-steps' => 'Proses kerja'][$type])
<a class="text-link" href="{{ route('admin.companies.edit', $company) }}">← Profil {{ $company->short_name }}</a>
<div class="admin-heading"><div><h1>{{ $label }}</h1><p>{{ $company->name }} · {{ $items->total() }} hasil</p></div><a class="button" href="{{ route('admin.content.create', [$company, $type]) }}">+ Tambah</a></div>
<nav class="editor-nav" aria-label="Konten perusahaan">@foreach(['products' => 'Produk', 'pillars' => 'Keunggulan', 'process-steps' => 'Proses'] as $key => $title)<a href="{{ route('admin.content.index', [$company, $key]) }}" @if($key === $type) aria-current="page" @endif>{{ $title }}</a>@endforeach</nav>
<form class="panel form-panel" method="GET" action="{{ route('admin.content.index', [$company, $type]) }}"><div class="form-grid">
<label class="field"><span>Cari nama/judul atau deskripsi</span><input name="q" value="{{ $filters['q'] ?? '' }}" maxlength="150"></label>
<label class="field"><span>Status</span><select name="status"><option value="">Semua</option>@foreach(['active' => 'Aktif', 'inactive' => 'Nonaktif'] as $value => $title)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $title }}</option>@endforeach</select></label>
@if($type === 'products')<label class="field"><span>Kategori</span><select name="category"><option value="">Semua kategori</option>@foreach($categories as $category)<option value="{{ $category }}" @selected(($filters['category'] ?? '') === $category)>{{ $category }}</option>@endforeach</select></label>@endif
</div><div class="form-actions"><button class="button">Terapkan filter</button><a href="{{ route('admin.content.index', [$company, $type]) }}">Reset</a></div></form>
<div class="panel">@forelse($items as $item)
<div class="content-row"><span class="row-order">{{ $item->sort_order }}</span><div><strong>{{ $item->name ?? $item->title }}</strong><p>{{ Str::limit($item->description, 100) }}</p><small>{{ $item->is_active ? 'Aktif' : 'Nonaktif' }} @if($type === 'products') · {{ $item->category }} @endif</small></div><a class="text-link" href="{{ route('admin.content.edit', [$company, $type, $item->id]) }}">Edit</a><form method="POST" action="{{ route('admin.content.destroy', [$company, $type, $item->id]) }}" data-confirm="Hapus konten ini? Tindakan ini tidak dapat dibatalkan.">@csrf @method('DELETE')<button class="danger-link">Hapus</button></form></div>
@empty<p class="empty-state">Tidak ada konten yang sesuai.</p>@endforelse</div>
{{ $items->links() }}
@endsection
