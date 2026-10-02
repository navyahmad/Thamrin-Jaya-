@extends('layouts.auth')
@section('title', 'Login admin')
@section('content')
<span class="eyebrow">WELCOME BACK</span><h2>Masuk ke workspace.</h2><p>Gunakan akun administrator untuk mengelola website.</p><form method="POST" action="{{ route('login.store') }}">@csrf<x-field name="email" label="Email" type="email" required autocomplete="username" autofocus/><x-field name="password" label="Password" type="password" required autocomplete="current-password"/><div class="login-options"><label class="checkbox-row"><input type="checkbox" name="remember" value="1"> Ingat saya</label><a href="{{ route('password.request') }}">Lupa password?</a></div><button class="button" type="submit">Masuk <span>→</span></button></form>
@endsection