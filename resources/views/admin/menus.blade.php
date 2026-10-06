@extends('layouts.admin')
@section('title', 'Menu situs')
@section('content')
<a class="text-link" href="{{ route('admin.sites.edit', $site) }}">← {{ $site->resolvedName() }}</a><div class="admin-heading"><div><h1>Menu situs</h1><p>Slot header/footer tetap. Kelola nama, status dan item navigasi.</p></div></div>
<div class="panel">@forelse($menus as $menu)<div class="content-row"><div><strong>{{ $menu->name }}</strong><p>{{ $menu->key }} · {{ $menu->is_active ? 'Aktif' : 'Nonaktif' }} · {{ $menu->items_count }} item</p></div><a class="text-link" href="{{ route('admin.sites.menus.edit', [$site, $menu]) }}">Kelola →</a></div>@empty<p>Belum ada menu. Situs baru melalui CMS otomatis memiliki header dan footer.</p>@endforelse</div>
@endsection
