{{-- Layout website publik + portal owner --}}
@extends('layouts.base')

@section('body')
@php($u = auth()->user())
<div class="marquee no-print">🐾 Klinik hewan • Boarding • Antrean online — daftar pakai nomor HP, dapat Soulmate Pass</div>
<nav class="pub-nav no-print">
    <div class="container">
        <a href="{{ route('home') }}" class="brand" style="margin:0">
            <span class="brand-logo">🐾</span>
            <span><b>PAWLUNE</b><small>CATCARE</small></span>
        </a>
        <div class="pub-links">
            <a class="btn btn-sm btn-yellow" href="{{ route('home') }}#layanan">Layanan</a>
            <a class="btn btn-sm btn-yellow" href="{{ route('home') }}#boarding">Boarding</a>
            <a class="btn btn-sm btn-yellow" href="{{ route('home') }}#testimoni">Testimoni</a>
            @auth
                <a class="btn btn-sm btn-blue" href="{{ $u->homeRoute() }}">{{ $u->role === 'owner' ? 'Dashboard Saya' : 'Buka Panel' }}</a>
                <form method="POST" action="{{ route('logout') }}" class="inline-form">@csrf<button class="btn btn-sm btn-red" type="submit">Keluar</button></form>
            @else
                <a class="btn btn-sm" href="{{ route('login') }}">Masuk</a>
                <a class="btn btn-sm btn-pink" href="{{ route('register') }}">Daftar</a>
            @endauth
        </div>
    </div>
</nav>

@yield('content')

<footer class="footer no-print">
    <div class="container row between">
        <div>
            <b>{{ config('catcare.clinic.name') }}</b><br>
            <span class="small">{{ config('catcare.clinic.address') }} • {{ config('catcare.clinic.phone') }}</span><br>
            <span class="small">{{ config('catcare.clinic.hours') }}</span>
        </div>
        <div class="small mono">© {{ date('Y') }} PAWLUNE CATCARE</div>
    </div>
</footer>
@endsection
