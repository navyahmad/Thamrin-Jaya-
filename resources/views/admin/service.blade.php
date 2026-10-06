@extends('layouts.admin')
@section('title', $service->exists ? 'Edit layanan' : 'Tambah layanan')
@section('content')
<a class="text-link" href="{{ route('admin.services.index') }}">← Semua layanan</a>
<div class="admin-heading"><h1>{{ $service->exists ? 'Edit '.$service->name : 'Tambah layanan' }}</h1></div>
<form class="panel form-panel" method="POST" enctype="multipart/form-data" action="{{ $service->exists ? route('admin.services.update', $service) : route('admin.services.store') }}">@csrf @if($service->exists) @method('PUT') @endif
<div class="form-grid"><x-field name="name" label="Nama layanan" :value="$service->name" required maxlength="255"/><x-field name="slug" label="Slug layanan" :value="$service->slug" required maxlength="100" hint="Huruf kecil, angka, dan tanda hubung. Menu berbasis ID tetap mengikuti layanan ini."/><x-field name="sort_order" label="Urutan layanan" type="number" :value="$service->sort_order" min="0" max="999" required/></div>
<x-field name="description" label="Deskripsi" type="textarea" :value="$service->description" maxlength="10000"/>
<x-field name="image" label="Gambar layanan" type="file" accept="image/jpeg,image/png,image/webp" hint="JPG, PNG, WebP; maksimal 5 MB."/>
@if($service->image_path)<img class="upload-preview" src="{{ \App\Actions\Cms\MediaManager::url($service->image_path) }}" alt="Gambar layanan"><label class="checkbox-row"><input type="checkbox" name="remove_image" value="1" @checked(old('remove_image'))> Hapus gambar saat disimpan</label>@endif
<label class="checkbox-row"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', session()->hasOldInput() ? false : $service->is_active))> Layanan aktif</label>
<h2>Perusahaan penyedia</h2><p>Centang perusahaan yang menyediakan layanan ini. Urutan relasi menentukan posisi perusahaan dalam layanan dan posisi layanan pada perusahaan. Menghapus centang melepaskan relasi.</p>
@php($rows = old('companies', $companies->map(function ($company) use ($service) { $linked = $service->companies->firstWhere('id', $company->id); return ['id' => $company->id, 'selected' => (bool) $linked, 'sort_order' => $linked?->pivot->sort_order ?? 0]; })->all()))
@forelse($rows as $index => $row)
<div class="form-grid"><input type="hidden" name="companies[{{ $index }}][id]" value="{{ $row['id'] ?? '' }}"><label class="checkbox-row"><input type="checkbox" name="companies[{{ $index }}][selected]" value="1" @checked($row['selected'] ?? false)> {{ $companies->firstWhere('id', $row['id'] ?? null)?->name ?? 'Perusahaan tidak tersedia' }}</label><label class="field"><span>Urutan relasi</span><input type="number" name="companies[{{ $index }}][sort_order]" min="0" max="999" required value="{{ $row['sort_order'] ?? 0 }}"></label></div>
@empty<p>Belum ada perusahaan. Layanan dapat disimpan tanpa relasi.</p>@endforelse
<button class="button">Simpan layanan & relasi</button>
</form>
@if($service->exists)<section class="panel form-panel"><h2>Hapus layanan</h2><p>Layanan ini terhubung ke {{ $service->companies->count() }} perusahaan dan {{ $service->menu_items_count }} item menu. Penghapusan ditolak selama masih memiliki relasi. Nonaktifkan jika hanya ingin menyembunyikan layanan.</p><form method="POST" action="{{ route('admin.services.destroy', $service) }}" data-confirm="Hapus layanan ini? Tindakan ini tidak dapat dibatalkan.">@csrf @method('DELETE')<button class="danger-link">Hapus layanan</button></form></section>@endif
@endsection
