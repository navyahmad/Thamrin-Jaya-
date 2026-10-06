@extends('layouts.admin')
@section('title', $item->exists ? 'Edit item' : 'Tambah item')
@section('content')
<a class="text-link" href="{{ route('admin.sites.pages.sections.items.index', [$site, $page, $section]) }}">← Item {{ $section->title ?? $section->key }}</a>
<div class="admin-heading"><div><h1>{{ $item->exists ? 'Edit item' : 'Tambah item' }}</h1><p>{{ $site->resolvedName() }} / {{ $page->title }} / {{ $section->type }}</p></div></div>
@if($page->is_published && $section->is_active)<p class="notice">Perubahan item aktif akan tampil pada halaman published setelah disimpan. Nonaktifkan item untuk menyunting tanpa ditampilkan.</p>@endif
<form class="panel form-panel" method="POST" enctype="multipart/form-data" action="{{ $item->exists ? route('admin.sites.pages.sections.items.update', [$site, $page, $section, $item]) : route('admin.sites.pages.sections.items.store', [$site, $page, $section]) }}">@csrf @if($item->exists) @method('PUT') @endif
@if($section->type === 'vision_mission')<label class="field"><span>Jenis</span><select name="key">@foreach(['vision' => 'Visi', 'mission' => 'Misi'] as $key => $label)<option value="{{ $key }}" @selected(old('key', $item->key) === $key)>{{ $label }}</option>@endforeach</select></label>@else<x-field name="key" label="Key item (opsional)" :value="$item->key" maxlength="100" hint="Huruf kecil, angka, tanda hubung; unik dalam section."/>@endif
<x-field name="title" label="Judul / nama" :value="$item->title" maxlength="255" required/>
@foreach(['subtitle' => 'Subjudul', 'body' => 'Isi / narasi', 'value' => 'Nilai kapasitas', 'unit' => 'Satuan', 'image_alt' => 'Teks alternatif gambar', 'link_label' => 'Label tombol', 'link_url' => 'Tujuan tautan'] as $field => $label)
@if(in_array($field, $fields, true))<x-field :name="$field" :label="$label" :value="$item->$field" :type="$field === 'body' ? 'textarea' : 'text'" :hint="$field === 'link_url' ? 'URL HTTP/HTTPS atau #anchor section yang ada pada halaman ini.' : null"/>@endif
@endforeach
@if(in_array('occurred_on', $fields, true))<x-field name="occurred_on" label="Tanggal kegiatan / penerbitan" type="date" :value="$item->occurred_on?->format('Y-m-d')"/>@endif
@if(in_array('issuer', $fields, true))<x-field name="issuer" label="Penerbit sertifikat" :value="$item->settings['issuer'] ?? ''" maxlength="255"/>@endif
@if(in_array('icon', $fields, true))<label class="field"><span>Ikon (opsional)</span><select name="icon"><option value="">Tanpa ikon</option>@foreach(['factory', 'quality', 'clock', 'price', 'leaf', 'award'] as $icon)<option @selected(old('icon', $item->settings['icon'] ?? '') === $icon)>{{ $icon }}</option>@endforeach</select></label>@endif
@foreach(['image' => ['Gambar / logo', 'image_path'], 'file' => ['Dokumen sertifikasi PDF', 'file_path']] as $field => [$label, $column])
@if(in_array($field, $fields, true))<x-field :name="$field" :label="$label" type="file" :accept="$field === 'file' ? 'application/pdf' : 'image/jpeg,image/png,image/webp'" :hint="$field === 'file' ? 'PDF maksimal 10 MB.' : 'JPG, PNG, WebP maksimal 5 MB.'"/>
@if($item->$column)
@if($field === 'image')<img class="upload-preview" src="{{ \App\Actions\Cms\MediaManager::url($item->$column) }}" alt="Gambar item">@else<a class="text-link" href="{{ \App\Actions\Cms\MediaManager::url($item->$column) }}" target="_blank" rel="noopener">Buka PDF tersimpan ↗</a>@endif
<label class="checkbox-row"><input type="checkbox" name="remove_{{ $field }}" value="1" @checked(old('remove_'.$field))> Hapus {{ strtolower($label) }}</label>
@endif @endif @endforeach
<x-field name="sort_order" label="Urutan" type="number" :value="$item->sort_order" min="0" max="999" required/>
<label class="checkbox-row"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', session()->hasOldInput() ? false : $item->is_active))> Item aktif</label>
<button class="button">Simpan item</button></form>
@if($item->exists)<section class="panel form-panel"><h2>Hapus item</h2><p>File yang masih dipakai konten lain tetap disimpan.</p><form method="POST" action="{{ route('admin.sites.pages.sections.items.destroy', [$site, $page, $section, $item]) }}" data-confirm="Hapus item ini? Tindakan ini tidak dapat dibatalkan.">@csrf @method('DELETE')<button class="danger-link">Hapus item</button></form></section>@endif
@endsection
