@extends('layouts.app')
@section('title', 'Kamar')
@section('page', 'Kamar Boarding')
@section('subtitle', 'Tarif kamar mengikuti tarif kategori Boarding (per hari)')
@section('actions')<button class="btn btn-pink" data-modal-open="#addModal" type="button">+ Kamar Baru</button>@endsection
@section('content')
<div class="table-wrap"><table class="table">
    <thead><tr><th>Suite</th><th>Nama</th><th>Tipe</th><th>Tarif</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @foreach ($rooms as $r)
        <tr>
            <td><b>#{{ $r->room_number }}</b> <span class="mono xs muted">{{ $r->qr_hash }}</span></td><td>{{ $r->name }}</td><td>{{ strtoupper($r->room_type) }}</td>
            <td class="mono">{{ $r->tariff ? rupiah($r->tariff->price).'/hari' : '-' }}</td>
            <td>@php($st = $r->state())<span class="badge" style="background:{{ $st === 'occupied' ? 'var(--blue)' : ($st === 'maintenance' ? '#ddd' : 'var(--green)') }}">{{ $st === 'occupied' ? 'Terisi: '.$r->currentStay->pet->name : ucfirst($st === 'available' ? 'kosong' : 'maintenance') }}</span></td>
            <td class="nowrap">
                <button class="btn btn-sm btn-yellow" type="button" data-modal-open="#editModal" data-action="{{ route('admin.rooms.update', $r) }}" data-fill-room_number="{{ $r->room_number }}" data-fill-name="{{ $r->name }}" data-fill-room_type="{{ $r->room_type }}" data-fill-tariff_id="{{ $r->tariff_id }}" data-fill-is_maintenance="{{ $r->is_maintenance ? 1 : 0 }}">Ubah</button>
                <form class="inline-form" method="POST" action="{{ route('admin.rooms.destroy', $r) }}" data-confirm="Hapus kamar {{ $r->room_number }}?">@csrf @method('DELETE')<button class="btn btn-sm btn-red">✕</button></form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table></div>

<dialog class="modal" id="addModal" {{ $errors->any() && old('_f') === 'add' ? 'data-auto-open' : '' }}>
    <div class="modal-head"><h3>Kamar Baru</h3><button class="x-btn" data-modal-close type="button">✕</button></div>
    <div class="modal-body"><form method="POST" action="{{ route('admin.rooms.store') }}">@csrf<input type="hidden" name="_f" value="add">@include('admin._room-fields')<button class="btn btn-pink btn-block">Simpan</button></form></div>
</dialog>
<dialog class="modal" id="editModal">
    <div class="modal-head"><h3>Ubah Kamar</h3><button class="x-btn" data-modal-close type="button">✕</button></div>
    <div class="modal-body"><form method="POST" action="#">@csrf @method('PUT')@include('admin._room-fields')
        <input type="hidden" name="is_maintenance" value="0"><label class="check mb"><input type="checkbox" name="is_maintenance" value="1"> Sedang maintenance</label>
        <button class="btn btn-blue btn-block">Simpan</button></form></div>
</dialog>
@endsection
