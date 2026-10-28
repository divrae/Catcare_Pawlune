@extends('layouts.print')
@section('title', 'Rekap Boarding')
@section('back', route('boarding.index'))
@section('content')
<form class="row no-print mb" method="GET">
    <label class="label">Dari</label><input class="input sm" style="width:auto" type="date" name="from" value="{{ $from->toDateString() }}">
    <label class="label">Sampai</label><input class="input sm" style="width:auto" type="date" name="to" value="{{ $to->toDateString() }}">
    <button class="btn btn-sm btn-blue">Tampilkan</button>
</form>
<div class="card">
    <div class="row between" style="border-bottom:3px solid #000;padding-bottom:10px;margin-bottom:12px">
        <div><h2>REKAP BOARDING & PENITIPAN</h2><div class="small">Periode {{ tgl($from) }} – {{ tgl($to) }}</div></div>
        <div class="right small mono">{{ config('catcare.clinic.name') }}<br>Dicetak {{ now()->format('d/m/Y H:i') }}</div>
    </div>
    <div class="grid g4 mb">
        <div class="stat"><span class="lbl">Suite</span><div class="val">{{ $total }}</div></div>
        <div class="stat c-blue"><span class="lbl">Terisi</span><div class="val">{{ $occupied }}</div></div>
        <div class="stat c-green"><span class="lbl">Total menginap</span><div class="val">{{ $stays->count() }}</div></div>
        <div class="stat c-pink"><span class="lbl">Pendapatan est.</span><div class="val" style="font-size:1.2rem">{{ rupiah($stays->sum(fn ($b) => $b->total())) }}</div></div>
    </div>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Suite</th><th>Hewan</th><th>Pemilik</th><th>Masuk</th><th>Keluar</th><th>Hari</th><th>Status</th><th>Catatan</th><th>Rata² suhu</th><th class="right">Biaya</th></tr></thead>
        <tbody>
        @forelse ($stays as $b)
            @php($avg = $b->logs->whereNotNull('temperature_c')->avg('temperature_c'))
            <tr><td><b>#{{ $b->room?->room_number }}</b></td><td>{{ $b->pet->name }}</td><td>{{ $b->pet->owner->name }}</td><td>{{ tgl($b->check_in_date, 'd/m') }}</td><td>{{ tgl($b->check_out_date, 'd/m') }}</td><td>{{ $b->billedDays() }}</td><td>{{ $b->statusLabel() }}</td><td>{{ $b->logs->count() }}x</td><td>{{ $avg ? round($avg, 1).'°' : '-' }}</td><td class="num">{{ rupiah($b->total()) }}</td></tr>
        @empty
            <tr><td colspan="10" class="center muted">Tidak ada data pada periode ini.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <h3 class="mt">Status Suite Saat Ini</h3>
    <div class="row mt-sm">@foreach ($rooms as $r)<span class="badge" style="background:{{ $r->state() === 'occupied' ? 'var(--blue)' : ($r->state() === 'maintenance' ? '#ddd' : 'var(--green)') }}">#{{ $r->room_number }} {{ $r->state() === 'occupied' ? 'Terisi' : ($r->state() === 'maintenance' ? 'Maint.' : 'Kosong') }}</span>@endforeach</div>
</div>
@endsection
