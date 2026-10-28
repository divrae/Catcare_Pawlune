@extends('layouts.public')
@section('title', 'Masuk')
@section('content')
<div class="container">
    <div class="auth-box">
        <div class="card">
            <div class="card-head blue"><h2>🔑 Masuk</h2><span class="badge">Pawlune</span></div>
            @include('partials.flash')
            <form method="POST" action="{{ route('login.post') }}">
                @csrf
                <div class="field">
                    <label for="identifier">Nomor HP / Email</label>
                    <input class="input" id="identifier" name="identifier" value="{{ old('identifier') }}" placeholder="0812xxxxxxx atau email staff" autofocus required>
                    <span class="hint">Pemilik hewan masuk pakai nomor HP. Staff masuk pakai email.</span>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input class="input" type="password" id="password" name="password" required>
                </div>
                <label class="check mb"><input type="checkbox" name="remember" value="1"> Ingat saya</label>
                <button class="btn btn-blue btn-block btn-lg" type="submit">Masuk</button>
            </form>
            <hr style="border:0;border-top:3px dashed #000;margin:18px 0">
            <p class="center">Belum punya akun? <a href="{{ route('register') }}" class="bold">Daftar pakai nomor HP</a></p>
        </div>
        <div class="card flat mt" style="background:var(--yellow)">
            <div class="bold small upper">Akun demo (password: <span class="mono">password</span>)</div>
            <div class="mono small" style="line-height:1.7">
                admin@catcare.com • doctor@catcare.com<br>staff@catcare.com • cashier@catcare.com<br>groomer@catcare.com
            </div>
        </div>
    </div>
</div>
@endsection
