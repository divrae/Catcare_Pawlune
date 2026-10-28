{{-- Layout halaman cetak (rekap / struk) --}}
@extends('layouts.base')
@section('body')
<div class="container" style="max-width:980px;padding-top:18px;padding-bottom:40px">
    <div class="row between no-print mb">
        <a class="btn btn-sm" data-back href="@hasSection('back')@yield('back')@else{{ auth()->user()->homeRoute() }}@endif">← Kembali</a>
        <button class="btn btn-blue" data-print type="button">🖨️ Cetak</button>
    </div>
    @yield('content')
</div>
@endsection
