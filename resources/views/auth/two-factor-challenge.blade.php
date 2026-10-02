@extends('layouts.auth')
@section('title', 'Verifikasi dua faktor')
@section('content')
<span class="eyebrow">ACCOUNT SECURITY</span>
<h2>Verifikasi dua faktor.</h2>
<p>Masukkan kode dari aplikasi autentikator. Selesaikan dalam 10 menit setelah memasukkan password.</p>
<form method="POST" action="{{ route('two-factor.login.store') }}">
    @csrf
    <label for="code">Kode autentikator</label>
    <input id="code" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required autofocus>
    <button class="button" type="submit">Verifikasi</button>
</form>
<details><summary>Gunakan recovery code</summary>
    <form method="POST" action="{{ route('two-factor.login.store') }}">
        @csrf
        <label for="recovery_code">Recovery code</label>
        <input id="recovery_code" name="recovery_code" autocomplete="off" required>
        <button class="button" type="submit">Masuk dengan recovery code</button>
    </form>
</details>
<a href="{{ route('login') }}" class="text-link">Kembali ke login</a>
@endsection
