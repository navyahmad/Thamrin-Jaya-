@extends('layouts.admin')
@section('title', 'Tambah perusahaan')
@section('content')
<div class="admin-heading"><div><h1>Tambah perusahaan</h1><p>Perusahaan dimulai nonaktif. Situs, empat halaman draft, dan navigasi dibuat otomatis. Upload banner dan logo tersedia setelah disimpan.</p></div></div>
<form class="panel form-panel" method="POST" action="{{ route('admin.companies.store') }}">@csrf
<div class="form-grid">
@foreach(['name' => 'Nama legal perusahaan', 'short_name' => 'Nama singkat', 'slug' => 'Slug URL (huruf kecil dan tanda hubung)', 'sector' => 'Bidang usaha', 'tagline' => 'Headline profil'] as $name => $label)
<x-field :name="$name" :label="$label" required/>
@endforeach
<x-field name="sort_order" label="Urutan gateway" type="number" value="0" min="0" max="999" required/>
<label class="field"><span>Ilustrasi sementara</span><select name="illustration">@foreach(['boxes','carton','pouch','machine','rolls','print'] as $illustration)<option @selected(old('illustration', 'boxes') === $illustration)>{{ $illustration }}</option>@endforeach</select></label>
</div><x-field name="summary" label="Ringkasan gateway" type="textarea" required maxlength="600"/>
<button class="button">Buat perusahaan & situs</button>
</form>
@endsection
