@extends('layouts.admin')
@section('title', 'Layanan & perusahaan')
@section('content')
<div class="admin-heading"><div><h1>Layanan & perusahaan</h1><p>Kelola kelompok layanan dan perusahaan yang menyediakannya.</p></div><a class="button" href="{{ route('admin.services.create') }}">+ Tambah layanan</a></div>
<form class="panel form-panel" method="GET" action="{{ route('admin.services.index') }}"><div class="form-grid">
<label class="field"><span>Cari nama/deskripsi</span><input name="q" maxlength="150" value="{{ $filters['q'] ?? '' }}"></label>
<label class="field"><span>Status</span><select name="status"><option value="">Semua</option>@foreach(['active' => 'Aktif', 'inactive' => 'Nonaktif'] as $key => $label)<option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>@endforeach</select></label>
<label class="field"><span>Perusahaan</span><select name="company_id"><option value="">Semua perusahaan</option>@foreach($companies as $company)<option value="{{ $company->id }}" @selected(($filters['company_id'] ?? '') == $company->id)>{{ $company->name }}</option>@endforeach</select></label>
</div><div class="form-actions"><button class="button">Terapkan</button><a href="{{ route('admin.services.index') }}">Reset</a></div></form>
<div class="panel">@forelse($services as $service)<div class="content-row"><span class="row-order">{{ $service->sort_order }}</span><div><strong>{{ $service->name }}</strong><p>{{ $service->slug }} · {{ $service->is_active ? 'Aktif' : 'Nonaktif' }}</p><small>{{ $service->companies_count }} perusahaan · {{ $service->menu_items_count }} target menu</small></div><a class="text-link" href="{{ route('admin.services.edit', $service) }}">Kelola →</a></div>@empty<p class="empty-state">Tidak ada layanan yang sesuai.</p>@endforelse</div>
{{ $services->links() }}
@endsection
