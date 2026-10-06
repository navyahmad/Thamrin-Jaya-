@extends('layouts.admin')
@section('title', 'Situs & branding')
@section('content')
<div class="admin-heading"><div><h1>Situs & branding</h1><p>Pilih holding atau anak perusahaan untuk mengelola identitas visualnya. Kontak anak perusahaan dikelola pada profil perusahaan.</p></div></div>
<div class="panel">@forelse($sites as $site)<div class="content-row"><div><strong>{{ $site->resolvedName() }}</strong><p>{{ $site->company_id ? 'Anak perusahaan' : 'Holding' }} · {{ $site->template_key }} · {{ $site->is_active ? 'Situs aktif' : 'Situs nonaktif' }}</p></div><a class="text-link" href="{{ route('admin.sites.edit', $site) }}">Kelola branding →</a><a class="text-link" href="{{ route('admin.sites.pages.index', $site) }}">Halaman</a>@if($site->company)<a class="text-link" href="{{ route('admin.companies.edit', $site->company) }}">Profil & gateway</a>@endif</div>@empty<p>Belum ada situs.</p>@endforelse</div>
@endsection
