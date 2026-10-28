{{-- Layout panel internal (admin, dokter, staff boarding, kasir) --}}
@extends('layouts.base')

@section('body')
@php
    $u = auth()->user();
    $is = fn (...$p) => request()->routeIs(...$p) ? 'active' : '';
@endphp
<div class="shell">
    <aside class="sidebar no-print">
        <a href="{{ $u->homeRoute() }}" class="brand">
            <span class="brand-logo">🐾</span>
            <span><b>PAWLUNE</b><small>CATCARE PANEL</small></span>
        </a>

        @if ($u->role === 'admin')
            <div class="nav-group">Admin</div>
            <a class="nav-link {{ $is('admin.dashboard') }}" href="{{ route('admin.dashboard') }}"><span class="ico">📊</span> Dashboard</a>
            <a class="nav-link {{ $is('admin.recap.*') }}" href="{{ route('admin.recap.index') }}"><span class="ico">🧾</span> Rekap & Cetak</a>
            <div class="nav-group">Pengaturan</div>
            <a class="nav-link {{ $is('admin.staff.*') }}" href="{{ route('admin.staff.index') }}"><span class="ico">👥</span> Staff</a>
            <a class="nav-link {{ $is('admin.shifts.*') }}" href="{{ route('admin.shifts.index') }}"><span class="ico">🗓️</span> Shift</a>
            <a class="nav-link {{ $is('admin.tariffs.*') }}" href="{{ route('admin.tariffs.index') }}"><span class="ico">🏷️</span> Tarif Layanan</a>
            <a class="nav-link {{ $is('admin.medicines.*') }}" href="{{ route('admin.medicines.index') }}"><span class="ico">💊</span> Obat & Stok</a>
            <a class="nav-link {{ $is('admin.rooms.*') }}" href="{{ route('admin.rooms.index') }}"><span class="ico">🏠</span> Kamar Boarding</a>
            <a class="nav-link {{ $is('admin.customers.*') }}" href="{{ route('admin.customers.index') }}"><span class="ico">🙋</span> Pelanggan</a>
            <a class="nav-link {{ $is('admin.testimonials.*') }}" href="{{ route('admin.testimonials.index') }}"><span class="ico">💬</span> Testimoni</a>
            <div class="nav-group">Buka Modul</div>
            <a class="nav-link {{ $is('doctor.*') }}" href="{{ route('doctor.dashboard') }}"><span class="ico">🩺</span> Dokter</a>
            <a class="nav-link {{ $is('boarding.*') }}" href="{{ route('boarding.index') }}"><span class="ico">🛏️</span> Boarding</a>
            <a class="nav-link {{ $is('grooming.*') }}" href="{{ route('grooming.index') }}"><span class="ico">🛁</span> Grooming</a>
            <a class="nav-link {{ $is('pos.*') }}" href="{{ route('pos.index') }}"><span class="ico">💳</span> Kasir (POS)</a>
        @elseif ($u->role === 'groomer')
            <div class="nav-group">Grooming</div>
            <a class="nav-link {{ $is('grooming.index','grooming.work') }}" href="{{ route('grooming.index') }}"><span class="ico">🛁</span> Antrean Grooming</a>
            <a class="nav-link {{ $is('grooming.history') }}" href="{{ route('grooming.history') }}"><span class="ico">📁</span> Riwayat Grooming</a>
        @elseif ($u->role === 'doctor')
            <div class="nav-group">Dokter</div>
            <a class="nav-link {{ $is('doctor.dashboard','doctor.exam') }}" href="{{ route('doctor.dashboard') }}"><span class="ico">🩺</span> Antrean Periksa</a>
            <a class="nav-link {{ $is('doctor.history') }}" href="{{ route('doctor.history') }}"><span class="ico">📁</span> Rekam Medis</a>
        @elseif ($u->role === 'staff')
            <div class="nav-group">Boarding</div>
            <a class="nav-link {{ $is('boarding.index','boarding.show') }}" href="{{ route('boarding.index') }}"><span class="ico">🛏️</span> Kamar & Penitipan</a>
            <a class="nav-link" href="{{ route('boarding.recap') }}" target="_blank"><span class="ico">🖨️</span> Cetak Rekap</a>
        @elseif ($u->role === 'cashier')
            <div class="nav-group">Kasir</div>
            <a class="nav-link {{ $is('pos.index') }}" href="{{ route('pos.index') }}"><span class="ico">💳</span> Kasir (POS)</a>
            <a class="nav-link {{ $is('pos.history') }}" href="{{ route('pos.history') }}"><span class="ico">🧾</span> Riwayat & Tutup Kas</a>
        @endif

        <div class="sidebar-foot">
            <a class="nav-link" href="{{ route('home') }}" target="_blank"><span class="ico">🌐</span> Lihat Website</a>
            <a class="nav-link {{ $is('password.change') }}" href="{{ route('password.change') }}"><span class="ico">🔐</span> Ganti Password</a>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="btn btn-block btn-red mt-sm" type="submit">Keluar</button>
            </form>
        </div>
    </aside>

    <main class="main">
        <div class="topbar no-print">
            <div class="row">
                <button class="btn btn-yellow menu-btn" data-menu type="button">☰</button>
                <div>
                    <h1>@yield('page')</h1>
                    @hasSection('subtitle')<div class="muted small">@yield('subtitle')</div>@endif
                </div>
            </div>
            <div class="row">
                @yield('actions')
                @php($shiftToday = $u->todayShift())
                @if ($shiftToday)
                    <span class="badge" style="background: {{ $shiftToday->color() }}">Shift {{ $shiftToday->label() }} {{ $shiftToday->time() }}</span>
                @endif
                <div class="user-chip">
                    <span class="avatar">{{ $u->initials() }}</span>
                    <span><b class="small">{{ $u->name }}</b><br><span class="xs muted upper">{{ $u->roleLabel() }}</span></span>
                </div>
            </div>
        </div>

        @include('partials.flash')
        @yield('content')
    </main>
</div>
@endsection
