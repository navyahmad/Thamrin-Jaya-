@extends('layouts.auth')
@section('title', 'Konfirmasi password')
@section('content')
<span class="eyebrow">ACCOUNT SECURITY</span>
<h2>Konfirmasi password.</h2>
<p>Masukkan password akun Anda untuk melanjutkan.</p>
<form method="POST" action="{{ route('password.confirm.store') }}">
    @csrf
    <x-field name="password" label="Password" type="password" required autocomplete="current-password" autofocus/>
    <button class="button" type="submit">Konfirmasi →</button>
</form>
@endsection
