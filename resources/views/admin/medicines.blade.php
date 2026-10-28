@extends('layouts.app')
@section('title', 'Obat')
@section('page', 'Obat & Stok')
@section('subtitle', 'Satu kode per obat (kode obat & batch digabung) — harga & kategori dipakai dokter saat meresepkan')
@section('actions')<button class="btn btn-pink" data-modal-open="#addModal" type="button">+ Obat Baru</button>@endsection
@section('content')
<form class="row mb" method="GET">
    <input class="input" style="max-width:320px" name="q" value="{{ $q }}" placeholder="Cari kode / nama / kategori">
    <select class="select" style="max-width:200px" name="filter"><option value="">Semua</option><option value="low" @selected($filter === 'low')>Stok menipis</option><option value="expiring" @selected($filter === 'expiring')>Segera kedaluwarsa</option></select>
    <button class="btn btn-blue">Filter</button>
</form>
<div class="table-wrap"><table class="table">
    <thead><tr><th>Kode</th><th>Nama obat</th><th>Kategori</th><th>Satuan</th><th class="right">Harga</th><th class="right">Stok</th><th>Kedaluwarsa</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @forelse ($medicines as $m)
        <tr style="{{ $m->is_active ? '' : 'opacity:.55' }}">
            <td class="mono bold">{{ $m->code }}</td><td><b>{{ $m->name }}</b></td><td>{{ $m->category }}</td><td>{{ $m->unit }}</td><td class="num">{{ rupiah($m->price) }}</td>
            <td class="num"><span class="badge" style="background:{{ $m->stock <= 0 ? 'var(--red)' : ($m->isLow() ? 'var(--yellow)' : 'var(--green)') }}">{{ $m->stock }}</span> <span class="xs muted">min {{ $m->min_stock }}</span></td>
            <td>@if ($m->expired_date)<span class="badge" style="background:{{ $m->isExpired() ? 'var(--red)' : ($m->isExpiringSoon() ? 'var(--orange)' : '#fff') }}">{{ tgl($m->expired_date) }}{{ $m->isExpired() ? ' • ED' : '' }}</span>@else - @endif</td>
            <td>{{ $m->is_active ? 'Aktif' : 'Nonaktif' }}</td>
            <td class="nowrap">
                <button class="btn btn-sm btn-green" type="button" data-modal-open="#stockModal" data-action="{{ route('admin.medicines.stock', $m) }}" data-fill-med="{{ $m->name }} ({{ $m->code }}) — stok {{ $m->stock }}" data-fill-expired_date="{{ $m->expired_date?->toDateString() }}">Stok</button>
                <button class="btn btn-sm btn-yellow" type="button" data-modal-open="#editModal" data-action="{{ route('admin.medicines.update', $m) }}" data-fill-name="{{ $m->name }}" data-fill-category="{{ $m->category }}" data-fill-unit="{{ $m->unit }}" data-fill-price="{{ (int) $m->price }}" data-fill-min_stock="{{ $m->min_stock }}" data-fill-expired_date="{{ $m->expired_date?->toDateString() }}" data-fill-is_active="{{ $m->is_active ? 1 : 0 }}">Ubah</button>
                <form class="inline-form" method="POST" action="{{ route('admin.medicines.destroy', $m) }}" data-confirm="Hapus obat {{ $m->name }}?">@csrf @method('DELETE')<button class="btn btn-sm btn-red">✕</button></form>
            </td>
        </tr>
    @empty
        <tr><td colspan="9" class="center muted">Tidak ada obat.</td></tr>
    @endforelse
    </tbody>
</table></div>

<div class="card mt">
    <div class="card-head"><h3>Mutasi stok terakhir</h3></div>
    @forelse ($movements as $mv)
        <div class="list-line"><div><b>{{ $mv->medicine?->name }}</b> <span class="badge" style="background:{{ $mv->type === 'out' ? 'var(--pink)' : ($mv->type === 'in' ? 'var(--green)' : 'var(--yellow)') }}">{{ $mv->type === 'out' ? '− keluar' : ($mv->type === 'in' ? '+ masuk' : '± opname') }} {{ $mv->qty }}</span><div class="xs muted">{{ $mv->note }} {{ $mv->reference ? '• '.$mv->reference : '' }}</div></div><span class="xs muted">stok {{ $mv->stock_after }} • {{ $mv->created_at->format('d/m H:i') }}</span></div>
    @empty<div class="muted small">Belum ada mutasi.</div>@endforelse
</div>

<dialog class="modal" id="addModal" {{ $errors->any() && old('_f') === 'add' ? 'data-auto-open' : '' }}>
    <div class="modal-head"><h3>Obat Baru — kode otomatis {{ $nextCode }}</h3><button class="x-btn" data-modal-close type="button">✕</button></div>
    <div class="modal-body"><form method="POST" action="{{ route('admin.medicines.store') }}">@csrf<input type="hidden" name="_f" value="add">
        @include('admin._medicine-fields', ['withStock' => true])<button class="btn btn-pink btn-block">Simpan Obat</button></form></div>
</dialog>
<dialog class="modal" id="editModal">
    <div class="modal-head"><h3>Ubah Obat</h3><button class="x-btn" data-modal-close type="button">✕</button></div>
    <div class="modal-body"><form method="POST" action="#">@csrf @method('PUT')
        @include('admin._medicine-fields', ['withStock' => false])
        <input type="hidden" name="is_active" value="0"><label class="check mb"><input type="checkbox" name="is_active" value="1"> Aktif</label>
        <button class="btn btn-blue btn-block">Simpan</button></form></div>
</dialog>
<dialog class="modal" id="stockModal">
    <div class="modal-head"><h3>Atur Stok</h3><button class="x-btn" data-modal-close type="button">✕</button></div>
    <div class="modal-body"><form method="POST" action="#">@csrf
        <p class="bold" data-fill="med"></p>
        <div class="field"><label>Jenis</label><select class="select" name="mode"><option value="in">Restock (tambah)</option><option value="adjust">Stok opname (set jumlah sebenarnya)</option></select></div>
        <div class="fgrid">
            <div class="field"><label>Jumlah</label><input class="input" type="number" min="0" name="qty" required></div>
            <div class="field"><label>Kedaluwarsa (batch terbaru)</label><input class="input" type="date" name="expired_date"></div>
        </div>
        <div class="field"><label>Catatan</label><input class="input" name="note" placeholder="Faktur / alasan"></div>
        <button class="btn btn-green btn-block">Simpan Stok</button></form></div>
</dialog>
@endsection
