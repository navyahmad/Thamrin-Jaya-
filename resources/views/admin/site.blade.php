@extends('layouts.admin')
@section('title', 'Branding '.$site->resolvedName())
@section('content')
<div class="admin-heading"><div><h1>{{ $site->resolvedName() }}</h1><p>Pengaturan situs dan branding. Pilih identitas visual yang sesuai dengan logo perusahaan.</p></div></div>
<details class="panel"><summary>Pindah konteks situs</summary>@foreach($sites as $context)<p><a href="{{ route('admin.sites.edit', $context) }}" @if($context->is($site)) aria-current="page" @endif>{{ $context->resolvedName() }}</a></p>@endforeach</details>
@if($site->company)<p><a class="text-link" href="{{ route('admin.companies.edit', $site->company) }}">Kelola nama, URL, profil, kontak & gateway perusahaan →</a></p>@endif
<form class="panel form-panel" method="POST" action="{{ route('admin.sites.update', $site) }}" enctype="multipart/form-data">@csrf @method('PUT')
@if(!$site->company_id)<x-field name="name" label="Nama holding" :value="$site->name" required/>@endif
<div class="form-grid">
@foreach(['logo' => 'Logo', 'favicon' => 'Favicon'] as $input => $label)
<div><x-field :name="$input" :label="$label" type="file" accept="image/jpeg,image/png,image/webp" hint="JPG, PNG, WebP; maksimal 5 MB."/>
@if($site->{$input.'_path'})<img class="upload-preview" src="{{ \App\Actions\Cms\MediaManager::url($site->{$input.'_path'}) }}" alt="{{ $label }}"><label class="checkbox-row"><input type="checkbox" name="remove_{{ $input }}" value="1" @checked(old('remove_'.$input))> Hapus {{ strtolower($label) }}</label>@endif</div>
@endforeach
<x-field name="logo_alt" label="Teks alternatif logo" :value="$site->logo_alt"/>
<label class="field"><span>Template</span><select name="template_key">@foreach($templates as $template)<option @selected(old('template_key', $site->template_key) === $template)>{{ $template }}</option>@endforeach</select></label>
<x-field name="primary_color" label="Warna utama" type="color" :value="$site->primary_color" required/>
<x-field name="secondary_color" label="Warna sekunder" type="color" :value="$site->secondary_color" required/>
<label class="field"><span>Font</span><select name="font_family">@foreach(['Inter', 'Roboto', 'Poppins', 'Montserrat', 'Arial', 'sans-serif'] as $font)<option @selected(old('font_family', $site->font_family ?? 'Inter') === $font)>{{ $font }}</option>@endforeach</select></label>
@foreach(['container_width' => ['Lebar konten', ['normal', 'wide']], 'button_style' => ['Bentuk tombol', ['rounded', 'square']]] as $key => [$label, $options])
<label class="field"><span>{{ $label }}</span><select name="theme_settings[{{ $key }}]">@foreach($options as $option)<option @selected(old('theme_settings.'.$key, $site->theme_settings[$key] ?? $options[0]) === $option)>{{ $option }}</option>@endforeach</select></label>
@endforeach
</div>
<x-field name="footer_description" label="Deskripsi footer" type="textarea" :value="$site->footer_description"/>
<x-field name="copyright_text" label="Teks hak cipta" :value="$site->copyright_text"/>
<x-field name="seo_title" label="Judul SEO default" :value="$site->seo_title"/>
<x-field name="seo_description" label="Deskripsi SEO default" type="textarea" :value="$site->seo_description"/>
<h2>Media sosial</h2><div class="form-grid">@foreach(['instagram','facebook','linkedin','youtube','tiktok','website'] as $key)
<label class="field"><span>{{ ucfirst($key) }}</span><input type="url" name="social_links[{{ $key }}]" value="{{ old('social_links.'.$key, $site->social_links[$key] ?? '') }}" placeholder="https://"></label>
@endforeach</div>
@if(!$site->company_id)<h2>Kontak holding</h2><div class="form-grid">@foreach(['email' => 'Email', 'phone' => 'Telepon', 'whatsapp' => 'WhatsApp (628…)', 'hours' => 'Jam operasional', 'address' => 'Alamat', 'map_query' => 'Alamat / koordinat peta'] as $key => $label)
<label class="field"><span>{{ $label }}</span><input name="contact_details[{{ $key }}]" value="{{ old('contact_details.'.$key, $site->contact_details[$key] ?? '') }}"></label>
@endforeach</div>@endif
<label class="checkbox-row"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', session()->hasOldInput() ? false : $site->is_active))> Situs aktif (perusahaan dan halaman Home juga harus aktif agar tampil)</label>
<button class="button">Simpan pengaturan situs</button>
</form>
<p>Situs holding dan situs anak perusahaan tidak dihapus terpisah. Nonaktifkan untuk menyembunyikan situs.</p>
@endsection
