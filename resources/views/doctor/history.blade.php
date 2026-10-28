@extends('layouts.app')
@section('title', 'Rekam Medis')
@section('page', 'Rekam Medis')
@section('subtitle', 'Cari berdasarkan nama hewan, pemilik, kode Soulmate Pass, atau diagnosa')
@section('content')
<form class="row mb" method="GET"><input class="input" style="max-width:380px" name="q" value="{{ $q }}" placeholder="Cari…"><button class="btn btn-blue">Cari</button></form>
<div class="table-wrap"><table class="table">
    <thead><tr><th>Tanggal</th><th>Pasien</th><th>Pemilik</th><th>Diagnosa</th><th>Obat</th><th>Dokter</th></tr></thead>
    <tbody>
    @forelse ($records as $r)
        <tr>
            <td class="nowrap">{{ tgl($r->created_at) }}</td>
            <td><b>{{ $r->visit->pet->name }}</b><br><span class="mono xs">{{ $r->visit->pet->pass_code }}</span></td>
            <td>{{ $r->visit->pet->owner->name }}</td>
            <td style="max-width:280px">{{ \Illuminate\Support\Str::limit($r->diagnosis, 90) }}</td>
            <td class="small">{{ $r->prescriptions->pluck('medicine_name')->join(', ') ?: '-' }}</td>
            <td>{{ $r->doctor?->name }}</td>
        </tr>
    @empty
        <tr><td colspan="6" class="center muted">Tidak ada data.</td></tr>
    @endforelse
    </tbody>
</table></div>
<div class="mt">{{ $records->links('partials.pagination') }}</div>
@endsection
