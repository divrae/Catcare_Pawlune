@extends('layouts.app')
@section('title', 'Pelanggan')
@section('page', 'Pelanggan (Pemilik Hewan)')
@section('content')
<form class="row mb" method="GET"><input class="input" style="max-width:340px" name="q" value="{{ $q }}" placeholder="Cari nama / nomor HP"><button class="btn btn-blue">Cari</button></form>
<div class="table-wrap"><table class="table">
    <thead><tr><th>Nama</th><th>HP</th><th>Hewan</th><th>Jenis akun</th><th>Bergabung</th></tr></thead>
    <tbody>
    @forelse ($customers as $c)
        <tr><td><b>{{ $c->name }}</b></td><td>{{ $c->phone }}</td><td>{{ $c->pets_count }}</td>
            <td><span class="badge" style="background:{{ $c->password ? 'var(--green)' : 'var(--orange)' }}">{{ $c->password ? 'Punya akun' : 'Walk-in' }}</span></td><td>{{ tgl($c->created_at) }}</td></tr>
    @empty<tr><td colspan="5" class="center muted">Belum ada data.</td></tr>@endforelse
    </tbody>
</table></div>
<div class="mt">{{ $customers->links('partials.pagination') }}</div>
@endsection
