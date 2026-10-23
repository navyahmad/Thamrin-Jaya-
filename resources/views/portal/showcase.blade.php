<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $site->resolvedName() }} · Produk &amp; Layanan</title>
@if($preview)<meta name="robots" content="noindex,nofollow">@endif
@if($favicon = \App\Actions\Cms\MediaManager::url($site->favicon_path))<link rel="icon" href="{{ $favicon }}">@endif
<link rel="stylesheet" href="{{ asset('showcase/style.css') }}">
</head>
<body>
<div id="load"><span>menyiapkan katalog…</span></div>

<header class="top">
  <a href="{{ route('home') }}" class="brand" aria-label="{{ $site->resolvedName() }}">Thamrin Jaya <i>Group</i></a>
  <div class="topr"><a href="{{ route('home') }}" class="mono enq">← Beranda</a><span class="mono">Pasuruan · Jawa Timur</span><button class="mono enq" data-go="contact">Hubungi</button></div>
</header>

<!-- DISPLAY -->
<section class="view on" id="v-hang">
  <div id="stage"><div id="world"></div></div>
  <div class="hchips" id="hchips"></div>
  <div class="hint mono" id="hint">Scroll · Drag · ← → · Klik untuk detail produk</div>
  <div class="hud">
    <div class="n" id="hn">01<small>/30</small></div>
    <div class="t" id="ht"></div>
    <div class="m mono" id="hm"></div>
  </div>
  <div class="ticks" id="ticks"></div>
</section>

<!-- KATALOG -->
<section class="view" id="v-index">
  <div class="page">
    <div class="ph rise"><h2>Katalog <i>produk</i></h2><p>Semua produk dan layanan dari enam perusahaan {{ $site->resolvedName() }}. Arahkan kursor untuk preview, klik untuk detail.</p></div>
    <div class="chips rise" id="kchips" style="margin-bottom:22px;display:inline-flex;flex-wrap:wrap"></div>
    <table class="rise">
      <thead><tr><th>No.</th><th>Produk</th><th>Perusahaan</th><th>Bidang</th><th>Teknologi</th></tr></thead>
      <tbody id="rows"></tbody>
    </table>
  </div>
  <div id="peek"><img alt=""></div>
</section>

<!-- GALLERY -->
<section class="view" id="v-gallery">
  <div id="lt"><div id="table"></div></div>
  <div class="ltui">
    <div class="chips rise" id="gchips"></div>
    <div class="zoom rise"><button id="zo">−</button><span id="zl">60%</span><button id="zi">+</button></div>
  </div>
  <div class="lthint mono">Meja produk · drag untuk geser · scroll untuk zoom</div>
</section>

<!-- GRUP -->
<section class="view" id="v-about">
  <div class="ab">
    <div class="wallart">
      <div class="wa wa1" data-depth="18"><div class="sway"><div class="strings"><i></i><i></i></div><div class="clip" style="left:calc(50% - 6px)"></div><div class="frame"><img id="plateimg" alt="{{ $site->resolvedName() }}"><div class="cap"><span>GROUP</span><span>PASURUAN</span></div></div></div></div>
      <div class="wa wa2" data-depth="34"><div class="sway" style="animation-delay:-2s"><div class="strings"><i></i><i></i></div><div class="clip" style="left:calc(50% - 6px)"></div><div class="frame"><img id="aboutprint" alt="Produk"><div class="cap"><span id="apcap">No.</span><span>TJG</span></div></div></div></div>
    </div>
    <div class="abtext">
      <div class="mono rise" style="color:var(--mute);margin-bottom:14px">Tentang grup</div>
      <h2 class="rise">Satu grup, <i>enam perusahaan.</i></h2>
      <p class="rise">{{ $site->resolvedName() }} berbasis di Pasuruan, Jawa Timur. Usahanya mencakup percetakan kemasan, bahan baku dan mesin industri rokok, sampai kardus. Pilih perusahaan di bawah untuk melihat produknya.</p>
      <div class="cats rise" id="cats"></div>
      <div class="facts rise"><div><b>6</b><span class="mono">Perusahaan</span></div><div><b>30</b><span class="mono">Produk &amp; layanan</span></div><div><b>2012</b><span class="mono">Sejak</span></div></div>
    </div>
  </div>
</section>

<!-- KONTAK -->
<section class="view" id="v-contact">
  <div class="ct">
    <div class="ctbg" id="ctbg"></div>
    <div class="ctgrid">
      <form class="form rise" id="form">
        <div class="fields">
          <h2>Minta <i>penawaran</i></h2>
          <p>Isi form ini, nanti WhatsApp terbuka dengan pesan yang sudah jadi, langsung ke perusahaan yang dipilih.</p>
          <div class="row2">
            <label class="fld"><span class="mono">Nama</span><input id="fn" required placeholder="Nama kamu"></label>
            <label class="fld"><span class="mono">Instansi</span><input id="fi" placeholder="Nama perusahaan (opsional)"></label>
          </div>
          <div class="row2">
            <label class="fld"><span class="mono">Perusahaan tujuan</span><select id="selco"></select></label>
            <label class="fld"><span class="mono">Produk</span><select id="selp"></select></label>
          </div>
          <label class="fld"><span class="mono">Kebutuhan</span><textarea id="fm" placeholder="Jumlah, ukuran, bahan, target waktu, dll."></textarea></label>
          <button class="btn dk" type="submit">Kirim via WhatsApp ↗</button>
        </div>
        <div class="sent"><b>WhatsApp sudah dibuka.</b><span class="mono" style="color:var(--mute)">Tinggal tekan kirim di sana</span><br><br><button type="button" class="btn" id="again">Isi lagi</button></div>
      </form>
      <div class="clist rise" id="clist"></div>
    </div>
  </div>
</section>

<!-- DETAIL PRODUK -->
<section class="view" id="plate">
  <div class="pl">
    <div class="viewer rise"><div class="mat"><img id="pimg" alt=""></div><div class="thumbs" id="pthumbs"></div></div>
    <div class="info">
      <button class="back mono rise" id="back">← Kembali</button>
      <div class="mono rise" id="pno" style="color:var(--mute);margin-bottom:12px"></div>
      <h2 class="rise" id="pt"></h2>
      <div class="loc rise" id="pl"></div>
      <p class="d rise" id="pd"></p>
      <dl class="rise" id="pdl"></dl>
      <div class="acts rise"><button class="btn dk" id="penq">Minta penawaran</button><a class="btn" id="pwa" target="_blank" rel="noopener">WhatsApp ↗</a><button class="btn" id="pnext">Berikutnya →</button></div>
      <a class="site mono rise" id="psite"></a>
    </div>
  </div>
</section>

<nav class="nav" id="nav">
  <span class="pill" id="pill"></span>
  <button data-go="hang" class="on">Display</button>
  <button data-go="index">Katalog</button>
  <button data-go="gallery">Gallery</button>
  <button data-go="about">Grup</button>
  <button data-go="contact">Kontak</button>
</nav>

<script>
window.TJ_COMPANY_URLS = {!! json_encode([
    'sj' => route('company.show', 'sinar-jaya'),
    'gl' => route('company.show', 'globalindo'),
    'hte' => route('company.show', 'hte-rotopack'),
    'tp' => route('company.show', 'top-printing'),
    'mx' => route('company.show', 'maxtech'),
    'mp' => route('company.show', 'multipack'),
], JSON_UNESCAPED_SLASHES) !!};
</script>
<script src="{{ asset('showcase/data.js') }}"></script>
<script src="{{ asset('showcase/draw.js') }}"></script>
<script src="{{ asset('showcase/app.js') }}"></script>
</body>
</html>
