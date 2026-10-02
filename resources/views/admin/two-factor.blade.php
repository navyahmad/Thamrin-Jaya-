@extends('layouts.admin')
@section('title', 'Autentikasi dua faktor')
@section('content')
<div class="admin-heading"><div><span class="eyebrow">ACCOUNT SECURITY</span><h1>Autentikasi dua faktor.</h1><p>Tambahkan kode dari aplikasi autentikator saat masuk.</p></div></div>
<a href="{{ route('admin.account') }}" class="text-link">Kembali ke akun</a>
<div class="panel form-panel narrow-panel">
    @if(! auth()->user()->two_factor_secret)
        <h2>2FA belum aktif</h2>
        <p>Gunakan aplikasi autentikator yang mendukung kode TOTP. Aktivasi selesai setelah kode pertama berhasil diverifikasi.</p>
        <form method="POST" action="{{ route('two-factor.enable') }}">@csrf<button class="button" type="submit">Mulai pengaturan 2FA</button></form>
    @elseif(! auth()->user()->hasEnabledTwoFactorAuthentication())
        <h2>Pindai QR dan konfirmasi kode</h2>
        <p>Pindai QR berikut menggunakan aplikasi autentikator. Jangan bagikan QR atau kunci pengaturan ini.</p>
        <div aria-label="QR pengaturan autentikator">{!! auth()->user()->twoFactorQrCodeSvg() !!}</div>
        <details><summary>Masukkan kunci secara manual</summary><code>{{ decrypt(auth()->user()->two_factor_secret) }}</code></details>
        @error('code', 'confirmTwoFactorAuthentication')<p role="alert">{{ $message }}</p>@enderror
        <form method="POST" action="{{ route('two-factor.confirm') }}">
            @csrf
            <label for="setup-code">Kode 6 digit</label>
            <input id="setup-code" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required>
            <button class="button" type="submit">Verifikasi dan aktifkan</button>
        </form>
        <form method="POST" action="{{ route('two-factor.disable') }}">@csrf @method('DELETE')<button class="button" type="submit">Batalkan pengaturan</button></form>
    @else
        <h2>2FA aktif</h2>
        <p>Simpan recovery code di tempat aman. Setiap kode hanya dapat digunakan satu kali jika Anda kehilangan akses ke autentikator.</p>
        <ul>@foreach(auth()->user()->recoveryCodes() as $code)<li><code>{{ $code }}</code></li>@endforeach</ul>
        <form method="POST" action="{{ route('two-factor.regenerate-recovery-codes') }}">@csrf<p>Membuat kode baru membatalkan semua recovery code sebelumnya.</p><button class="button" type="submit">Buat recovery code baru</button></form>
        <form method="POST" action="{{ route('two-factor.disable') }}">@csrf @method('DELETE')<p>Setelah dinonaktifkan, login hanya memerlukan password.</p><button class="button" type="submit">Nonaktifkan 2FA</button></form>
    @endif
</div>
@endsection
