@extends('layouts.app')
@section('title', 'Riwayat Grooming')
@section('page', 'Riwayat Grooming')
@section('content')
<form class="row mb" method="GET">
    <input class="input" type="date" name="from" value="{{ $from->toDateString() }}" style="width:auto">
    <input class="input" type="date" name="to" value="{{ $to->toDateString() }}" style="width:auto">
    <button class="btn btn-yellow">Terapkan</button>
</form>
<div class="card">
    <div class="card-head green"><h2>{{ $orders->count() }} grooming • {{ rupiah($total) }}</h2></div>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Waktu</th><th>Hewan</th><th>Pemilik</th><th>Layanan</th><th>Jalur</th><th>Groomer</th><th class="right">Biaya</th></tr></thead>
        <tbody>
        @forelse ($orders as $o)
            <tr><td>{{ $o->finished_at->format('d/m H:i') }}</td><td>{{ $o->pet->name }}</td><td>{{ $o->pet->owner->name }}</td>
                <td>{{ $o->services->pluck('name')->join(', ') }}</td><td>{{ $o->isGuest() ? 'Tamu ' . $o->label() : 'Antrean' }}</td>
                <td>{{ $o->groomer?->name }}</td><td class="num">{{ rupiah($o->total()) }}</td></tr>
        @empty
            <tr><td colspan="7" class="center muted">Tidak ada data.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>
@endsection
