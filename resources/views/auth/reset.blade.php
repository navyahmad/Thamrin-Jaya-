@extends('layouts.auth')
@section('title', 'Reset password')
@section('content')
<span class="eyebrow">A FRESH START</span><h2>Buat password baru.</h2><form method="POST" action="{{ route('password.update') }}">@csrf<input type="hidden" name="token" value="{{ $token }}"><x-field name="email" label="Email admin" type="email" :value="$email" required/><x-field name="password" label="Password baru" type="password" required minlength="12" autocomplete="new-password" hint="Minimal 12 karakter, mengandung huruf dan angka."/><x-field name="password_confirmation" label="Ulangi password" type="password" required autocomplete="new-password"/><button class="button" type="submit">Simpan password →</button></form>
@endsection