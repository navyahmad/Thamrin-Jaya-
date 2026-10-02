@extends('layouts.auth')
@section('title', 'Lupa password')
@section('content')
<span class="eyebrow">ACCOUNT RECOVERY</span><h2>Lupa password?</h2><p>Masukkan email admin untuk mendapatkan tautan pemulihan.</p><form method="POST" action="{{ route('password.email') }}">@csrf<x-field name="email" label="Email admin" type="email" required autocomplete="email"/><button class="button" type="submit">Kirim tautan pemulihan →</button></form><a href="{{ route('login') }}" class="text-link">Kembali ke login</a>
@endsection