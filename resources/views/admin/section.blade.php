@extends('layouts.admin')
@section('title', $section->exists ? 'Edit section' : 'Tambah section')
@section('content')
@if($section->exists && ($section->settings['source'] ?? null) === 'items')<p><a class="button button-outline" href="{{ route('admin.sites.pages.sections.items.index', [$site, $page, $section]) }}">Kelola item section →</a></p>@endif
<a class="text-link" href="{{ route('admin.sites.pages.sections.index', [$site, $page]) }}">← Section {{ $page->title }}</a>
<div class="admin-heading"><div><h1>{{ $section->exists ? 'Edit section' : 'Tambah section' }}</h1><p>{{ $site->resolvedName() }} / {{ $page->title }}</p></div></div>
@if($page->is_published)<p class="notice">Perubahan section aktif pada halaman published akan tampil publik setelah disimpan. Jadikan halaman draft terlebih dahulu untuk menyunting tanpa ditampilkan.</p>@endif
<form class="panel form-panel" method="GET" action="{{ $section->exists ? route('admin.sites.pages.sections.edit', [$site, $page, $section]) : route('admin.sites.pages.sections.create', [$site, $page]) }}"><label class="field"><span>Jenis section</span><select name="type">@foreach($types as $option)<option @selected($type === $option)>{{ $option }}</option>@endforeach</select></label><p>Pergantian jenis pada section berisi konten akan ditolak. Pilih jenis sebelum mengisi form; isian yang belum disimpan tidak ikut dipindahkan.</p><button class="button button-outline">Buka form jenis ini</button></form>
<form class="panel form-panel" method="POST" enctype="multipart/form-data" action="{{ $section->exists ? route('admin.sites.pages.sections.update', [$site, $page, $section]) : route('admin.sites.pages.sections.store', [$site, $page]) }}">@csrf @if($section->exists) @method('PUT') @endif
<input type="hidden" name="type" value="{{ $type }}"><input type="hidden" name="variant" value="default">
<p>Jenis: <strong>{{ $type }}</strong>. Sumber konten: <strong>{{ $source }}</strong>.</p>
<x-field name="key" label="Key / anchor permanen" :value="$section->key" :readonly="$section->exists" required maxlength="100" hint="Huruf kecil, angka, tanda hubung. Harus unik pada halaman ini dan tidak dapat diganti setelah dibuat."/>
<x-field name="title" label="Judul section" :value="$section->title" maxlength="255"/>
<x-field name="subtitle" label="Subjudul section" :value="$section->subtitle" maxlength="1000"/>
@if($source === 'manual' && in_array($type, ['about', 'history']))
<x-field name="body" label="Isi profil / sejarah holding" type="textarea" :value="$section->body" maxlength="20000" hint="Teks biasa, bukan kode HTML."/>
<x-field name="image" label="Gambar section" type="file" accept="image/jpeg,image/png,image/webp" hint="JPG, PNG, WebP; maksimal 5 MB."/>
@if($section->image_path)<img class="upload-preview" src="{{ \App\Actions\Cms\MediaManager::url($section->image_path) }}" alt="Gambar section"><label class="checkbox-row"><input type="checkbox" name="remove_image" value="1" @checked(old('remove_image'))> Hapus gambar</label>@endif
<x-field name="image_alt" label="Teks alternatif gambar" :value="$section->image_alt" maxlength="255"/>
<x-field name="button_label" label="Label tombol" :value="$section->button_label" maxlength="100"/>
<x-field name="button_url" label="Tujuan tombol" :value="$section->button_url" hint="URL HTTP/HTTPS atau #anchor section pada halaman yang sama."/>
@elseif($source === 'company')<p>Isi berasal dari profil, katalog, keunggulan, atau proses perusahaan.</p><a class="text-link" href="{{ route('admin.companies.edit', $site->company_id) }}">Kelola sumber perusahaan →</a>
@elseif($source === 'contact_details')<p>Kontak berasal dari pengaturan {{ $site->company_id ? 'perusahaan' : 'holding' }}.</p><a class="text-link" href="{{ $site->company_id ? route('admin.companies.edit', $site->company_id) : route('admin.sites.edit', $site) }}">Kelola kontak →</a>
@elseif($source === 'relations')<p>Isi diambil otomatis dari layanan atau perusahaan yang terhubung dan dipublikasikan.</p><a class="text-link" href="{{ $type === 'services' ? route('admin.services.index') : route('admin.dashboard') }}">Kelola sumber konten →</a>
@elseif($source === 'items')<p>Isi section berasal dari item seperti slide, logo klien, CSR, atau sertifikasi.</p>
@else<p>Section bawaan ini tidak tersedia untuk holding dan harus tetap nonaktif.</p>@endif
<div class="form-grid"><x-field name="sort_order" label="Urutan" type="number" :value="$section->sort_order" min="0" max="999" required/><x-field name="limit" label="Batas jumlah konten (opsional)" type="number" :value="$section->settings['limit'] ?? ''" min="1" max="100"/></div>
<label class="checkbox-row"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', session()->hasOldInput() ? false : $section->is_active))> Section aktif</label>
<button class="button">Simpan section</button></form>
@if($section->exists)<section class="panel form-panel"><h2>Hapus section</h2><p>Menghapus section beserta {{ $section->items_count }} item miliknya. Penghapusan ditolak jika anchor masih dipakai menu. Gunakan nonaktifkan untuk menyembunyikan.</p><form method="POST" action="{{ route('admin.sites.pages.sections.destroy', [$site, $page, $section]) }}" data-confirm="Hapus section dan semua item miliknya?">@csrf @method('DELETE')<button class="danger-link">Hapus section</button></form></section>@endif
@endsection
