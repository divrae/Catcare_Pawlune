@extends('layouts.app')
@section('title', 'Tarif')
@section('page', 'Tarif Layanan')
@section('subtitle', 'Dipakai otomatis oleh Dokter, Boarding, dan Kasir')
@section('actions')<button class="btn btn-pink" data-modal-open="#addModal" type="button">+ Tarif Baru</button>@endsection
@section('content')
<div class="tabs"><a class="tab {{ ! $cat ? 'active' : '' }}" href="{{ route('admin.tariffs.index') }}">Semua</a>
    @foreach ($categories as $k => $l)<a class="tab {{ $cat === $k ? 'active' : '' }}" href="?category={{ $k }}">{{ $l }}</a>@endforeach</div>
<div class="table-wrap"><table class="table">
    <thead><tr><th>Kode</th><th>Nama layanan</th><th>Kategori</th><th class="right">Harga</th><th>Satuan</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @forelse ($tariffs as $t)
        <tr style="{{ $t->is_active ? '' : 'opacity:.55' }}">
            <td class="mono">{{ $t->code }}</td><td><b>{{ $t->name }}</b></td><td>{{ $t->categoryLabel() }}</td><td class="num">{{ rupiah($t->price) }}</td><td>/{{ $t->unit }}</td>
            <td><span class="badge" style="background:{{ $t->is_active ? 'var(--green)' : '#ddd' }}">{{ $t->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
            <td class="nowrap">
                <button class="btn btn-sm btn-yellow" type="button" data-modal-open="#editModal" data-action="{{ route('admin.tariffs.update', $t) }}" data-fill-name="{{ $t->name }}" data-fill-category="{{ $t->category }}" data-fill-price="{{ (int) $t->price }}" data-fill-is_active="{{ $t->is_active ? 1 : 0 }}">Ubah</button>
                <form class="inline-form" method="POST" action="{{ route('admin.tariffs.destroy', $t) }}" data-confirm="Hapus tarif {{ $t->name }}?">@csrf @method('DELETE')<button class="btn btn-sm btn-red">Hapus</button></form>
            </td>
        </tr>
    @empty
        <tr><td colspan="7" class="center muted">Belum ada tarif.</td></tr>
    @endforelse
    </tbody>
</table></div>

<dialog class="modal" id="addModal" {{ $errors->any() && old('_f') === 'add' ? 'data-auto-open' : '' }}>
    <div class="modal-head"><h3>Tarif Baru</h3><button class="x-btn" data-modal-close type="button">✕</button></div>
    <div class="modal-body"><form method="POST" action="{{ route('admin.tariffs.store') }}">@csrf<input type="hidden" name="_f" value="add">
        @include('admin._tariff-fields')<button class="btn btn-pink btn-block">Simpan</button></form></div>
</dialog>
<dialog class="modal" id="editModal">
    <div class="modal-head"><h3>Ubah Tarif</h3><button class="x-btn" data-modal-close type="button">✕</button></div>
    <div class="modal-body"><form method="POST" action="#">@csrf @method('PUT')
        @include('admin._tariff-fields')<button class="btn btn-blue btn-block">Simpan</button></form></div>
</dialog>
@endsection
