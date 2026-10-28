@extends('layouts.print')
@section('title', 'Rekap Boarding ' . $boarding->pet->name)
@section('back', route('boarding.show', $boarding))
@section('content')
<div class="card">
    <div class="row between" style="border-bottom:3px solid #000;padding-bottom:10px;margin-bottom:12px">
        <div><h2>REKAP BOARDING</h2><div class="small">{{ config('catcare.clinic.name') }} • {{ config('catcare.clinic.address') }}</div></div>
        <div class="right small mono">Dicetak {{ now()->format('d/m/Y H:i') }}</div>
    </div>
    <div class="grid g2">
        <dl class="kv"><dt>Hewan</dt><dd>{{ $boarding->pet->name }} ({{ $boarding->pet->speciesLabel() }}, {{ $boarding->pet->breed }})</dd><dt>Pemilik</dt><dd>{{ $boarding->pet->owner->name }} • {{ $boarding->pet->owner->phone }}</dd><dt>Kamar</dt><dd>Suite #{{ $boarding->room?->room_number }}</dd></dl>
        <dl class="kv"><dt>Masuk</dt><dd>{{ tgl($boarding->check_in_date) }}</dd><dt>Keluar</dt><dd>{{ tgl($boarding->check_out_date) }} ({{ $boarding->statusLabel() }})</dd><dt>Lama / Biaya</dt><dd>{{ $boarding->billedDays() }} hari • {{ rupiah($boarding->total()) }}</dd></dl>
    </div>
    <div class="table-wrap mt"><table class="table">
        <thead><tr><th>Waktu</th><th>Makan</th><th>Suhu</th><th>Kesehatan</th><th>BAB</th><th>Obat</th><th>Catatan</th><th>Petugas</th></tr></thead>
        <tbody>
        @forelse ($boarding->logs->reverse() as $l)
            <tr><td class="nowrap">{{ $l->logged_at->format('d/m H:i') }}</td><td>{{ \App\Models\BoardingLog::MEAL[$l->meal] }}</td><td>{{ $l->temperature_c ?? '-' }}</td><td>{{ \App\Models\BoardingLog::HEALTH[$l->health_status] }}</td><td>{{ \App\Models\BoardingLog::STOOL[$l->stool] }}</td><td>{{ $l->medicine_given ? 'Ya' : '-' }}</td><td>{{ $l->note }}</td><td>{{ $l->recorder?->name }}</td></tr>
        @empty
            <tr><td colspan="8" class="center muted">Belum ada catatan.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div class="row between mt" style="margin-top:40px"><div class="center small">Petugas Boarding<br><br><br>( {{ $boarding->admitter?->name ?? '................' }} )</div><div class="center small">Pemilik<br><br><br>( {{ $boarding->pet->owner->name }} )</div></div>
</div>
@endsection
