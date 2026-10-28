@extends('layouts.app')
@section('title', 'Rekap')
@section('page', 'Rekap Admin, Dokter, Boarding & Kasir')
@section('subtitle', 'Periode ' . tgl($from) . ' – ' . tgl($to))
@push('head')
<style>
    .rh{background:var(--yellow);border:3px solid #000;padding:6px 12px;margin:24px 0 12px;display:inline-block;font-size:1.05rem}
    .rt{width:100%;border-collapse:collapse;font-size:.85rem;margin-bottom:14px;background:#fff}.rt th{background:#0b0b0f;color:#fff;text-align:left;padding:6px 8px;font-size:.7rem;text-transform:uppercase}
    .rt td{border-top:2px solid #000;padding:6px 8px}.rt .n{text-align:right;font-family:var(--mono);white-space:nowrap}.rt.two>tbody>tr>td{border:0;vertical-align:top;width:50%;padding:0 8px 0 0}
    .kt{width:100%;border-spacing:10px;margin:0 -10px 10px}.kt td{border:3px solid #000;background:#fff;padding:10px;box-shadow:4px 4px 0 #000}.kt .kl{display:block;font-size:.66rem;text-transform:uppercase;font-weight:800;color:#555}.kt b{font-size:1.15rem}
    h3{margin:14px 0 6px;font-size:.95rem}
</style>
@endpush
@section('content')
@php($qs = ['from' => $from->toDateString(), 'to' => $to->toDateString()])
<form class="row mb" method="GET">
    <label class="label">Dari</label><input class="input sm" style="width:auto" type="date" name="from" value="{{ $from->toDateString() }}">
    <label class="label">Sampai</label><input class="input sm" style="width:auto" type="date" name="to" value="{{ $to->toDateString() }}">
    <input type="hidden" name="section" value="{{ $section }}">
    <button class="btn btn-sm btn-blue">Tampilkan</button>
    <a class="btn btn-sm" href="?from={{ today()->toDateString() }}&to={{ today()->toDateString() }}&section={{ $section }}">Hari ini</a>
    <a class="btn btn-sm" href="?from={{ today()->startOfMonth()->toDateString() }}&to={{ today()->toDateString() }}&section={{ $section }}">Bulan ini</a>
</form>
<div class="tabs">
    @foreach (['all' => 'Semua', 'admin' => 'Admin', 'doctor' => 'Dokter', 'boarding' => 'Boarding', 'grooming' => 'Grooming', 'pos' => 'Kasir (POS)'] as $k => $l)
        <a class="tab {{ $section === $k ? 'active' : '' }}" href="?{{ http_build_query($qs + ['section' => $k]) }}">{{ $l }}</a>
    @endforeach
    <a class="btn btn-green" target="_blank" href="{{ route('admin.recap.print', $qs + ['section' => $section]) }}">🖨️ Cetak</a>
    <a class="btn btn-orange" href="{{ route('admin.recap.pdf', $qs + ['section' => $section]) }}">⬇️ PDF</a>
</div>
@include('admin._recap-body')
@endsection
