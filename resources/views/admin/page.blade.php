@extends('layouts.admin')
@section('title', $page->exists ? 'Edit '.$page->title : 'Tambah halaman')
@section('content')
@if($page->exists)<p><a class="button button-outline" href="{{ route('admin.sites.pages.preview', [$site, $page]) }}" target="_blank" rel="noopener">Preview halaman ↗</a></p><p><a class="button button-outline" href="{{ route('admin.sites.pages.sections.index', [$site, $page]) }}">Kelola section halaman →</a></p>@endif
<a class="text-link" href="{{ route('admin.sites.pages.index', $site) }}">← Halaman {{ $site->resolvedName() }}</a>
<div class="admin-heading"><div><h1>{{ $page->exists ? 'Edit halaman' : 'Tambah halaman' }}</h1><p>{{ $site->resolvedName() }} @if($isCore) · Halaman inti: slug tetap, gunakan draft untuk menyembunyikan. @endif</p></div></div>
<form class="panel form-panel" method="POST" enctype="multipart/form-data" action="{{ $page->exists ? route('admin.sites.pages.update', [$site, $page]) : route('admin.sites.pages.store', $site) }}">@csrf @if($page->exists) @method('PUT') @endif
<div class="form-grid">
<x-field name="title" label="Judul halaman" :value="$page->title" required maxlength="255"/>
<x-field name="slug" label="Slug halaman" :value="$page->slug" :readonly="$isCore" required maxlength="100" hint="Huruf kecil, angka, tanda hubung. Perubahan slug mengubah URL; alamat lama tidak dialihkan otomatis."/>
<x-field name="sort_order" label="Urutan halaman" type="number" :value="$page->sort_order" required min="0" max="999"/>
</div>
<x-field name="meta_title" label="Judul SEO" :value="$page->meta_title" maxlength="255" hint="Kosongkan untuk menggunakan pengaturan SEO situs."/>
<x-field name="meta_description" label="Deskripsi SEO" type="textarea" :value="$page->meta_description" maxlength="500"/>
<x-field name="og_image" label="Gambar berbagi (Open Graph)" type="file" accept="image/jpeg,image/png,image/webp" hint="JPG, PNG, WebP; maksimal 5 MB."/>
@if($page->og_image_path)<img class="upload-preview" src="{{ \App\Actions\Cms\MediaManager::url($page->og_image_path) }}" alt="Gambar berbagi halaman"><label class="checkbox-row"><input type="checkbox" name="remove_og_image" value="1" @checked(old('remove_og_image'))> Hapus gambar saat disimpan</label>@endif
<label class="checkbox-row"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', session()->hasOldInput() ? false : $page->is_published))> Published (situs dan perusahaan juga harus aktif)</label>
@if($page->slug === 'home')<p>Home yang dijadikan draft menyembunyikan halaman utama situs. Untuk anak perusahaan, kartu gateway juga disembunyikan.</p>@endif
<button class="button">Simpan halaman</button>
</form>
@if($page->exists && !$isCore)
<section class="panel form-panel"><h2>Hapus halaman</h2><p>Penghapusan membawa {{ $page->sections_count }} section beserta itemnya. Saat ini terdapat {{ $page->menu_items_count }} tautan menu yang mengarah ke halaman ini. Lepaskan seluruh target menu terlebih dahulu; penghapusan ditolak selama masih dirujuk menu. Media yang masih dipakai konten lain tetap disimpan. Gunakan draft jika hanya ingin menyembunyikan halaman.</p>
<form method="POST" action="{{ route('admin.sites.pages.destroy', [$site, $page]) }}" data-confirm="Hapus halaman beserta section, item, dan tautan menu terkait? Tindakan ini tidak dapat dibatalkan.">@csrf @method('DELETE')<button class="danger-link">Hapus halaman</button></form></section>
@endif
@endsection
