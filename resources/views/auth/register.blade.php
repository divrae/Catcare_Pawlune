@extends('layouts.public')
@section('title', 'Daftar')
@section('content')
<div class="container">
    <div class="auth-box">
        <div class="card">
            <div class="card-head pink"><h2>🐱 Daftar Pemilik Hewan</h2></div>
            @include('partials.flash')
            <form method="POST" action="{{ route('register.post') }}">
                @csrf
                <div class="field"><label>Nama lengkap</label><input class="input" name="name" value="{{ old('name') }}" required autofocus></div>
                <div class="field">
                    <label>Nomor HP</label>
                    <input class="input" name="phone" inputmode="tel" value="{{ old('phone') }}" placeholder="081234567890" required>
                    <span class="hint">Dipakai untuk masuk. Pernah berkunjung walk-in? Pakai nomor yang sama agar riwayatmu tersambung.</span>
                </div>
                <div class="field"><label>Alamat (opsional)</label><input class="input" name="address" value="{{ old('address') }}"></div>
                <div class="fgrid">
                    <div class="field"><label>Password</label><input class="input" type="password" name="password" minlength="6" required></div>
                    <div class="field"><label>Ulangi password</label><input class="input" type="password" name="password_confirmation" required></div>
                </div>
                <button class="btn btn-pink btn-block btn-lg" type="submit">Buat Akun</button>
            </form>
            <p class="center mt">Sudah punya akun? <a href="{{ route('login') }}" class="bold">Masuk</a></p>
        </div>
    </div>
</div>
@endsection
