@extends('layouts.admin')
@section('title', $menu->name)
@section('content')
<a class="text-link" href="{{ route('admin.sites.menus.index', $site) }}">← Menu {{ $site->resolvedName() }}</a><div class="admin-heading"><h1>{{ $menu->name }} ({{ $menu->key }})</h1></div>
<form class="panel form-panel" method="POST" action="{{ route('admin.sites.menus.update', [$site, $menu]) }}">@csrf @method('PUT')<x-field name="name" label="Nama menu" :value="$menu->name" required maxlength="255"/><label class="checkbox-row"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', session()->hasOldInput() ? false : $menu->is_active))> Menu aktif</label><button class="button">Simpan menu</button></form>
<div class="panel-heading"><h2>Item navigasi</h2><a class="button" href="{{ route('admin.sites.menus.items.create', [$site, $menu]) }}">+ Tambah item</a></div><p>Urutan berlaku di antara item dengan induk yang sama. Menonaktifkan induk menyembunyikan seluruh cabangnya.</p>
<div class="panel">@forelse($items as $item)<div class="content-row"><span class="row-order">{{ $item->sort_order }}</span><div><strong>{{ $item->label }}</strong><p>Induk: {{ $item->parent?->label ?? 'Menu utama' }} · {{ $item->is_active ? 'Aktif' : 'Nonaktif' }} · {{ $item->children_count }} submenu</p><small>Target: {{ $item->page?->title ?? $item->service?->name ?? $item->url ?? 'Heading' }} @if($item->anchor) #{{ $item->anchor }} @endif</small></div><a class="text-link" href="{{ route('admin.sites.menus.items.edit', [$site, $menu, $item]) }}">Edit →</a></div>@empty<p class="empty-state">Belum ada item menu.</p>@endforelse</div>
@foreach($items->groupBy(fn ($item) => $item->parent_id ?? 'root') as $parentId => $siblings)
<form class="panel form-panel" method="POST" action="{{ route('admin.sites.menus.reorder', [$site, $menu]) }}">@csrf @method('PUT')
@if($parentId !== 'root')<input type="hidden" name="parent_id" value="{{ $parentId }}">@endif
<h2>Urutan {{ $parentId === 'root' ? 'menu utama' : 'submenu '.$items->firstWhere('id', $parentId)?->label }}</h2>
@foreach($siblings as $position => $current)<label class="field"><span>Posisi {{ $position + 1 }}</span><select name="ids[]">@foreach($siblings as $option)<option value="{{ $option->id }}" @selected($option->id === $current->id)>{{ $option->label }}</option>@endforeach</select></label>@endforeach
<button class="button">Simpan urutan cabang</button></form>
@endforeach
@endsection
