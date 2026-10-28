@extends('layouts.app')
@section('title', 'Staff')
@section('page', 'Manajemen Staff')
@section('subtitle', 'Admin mengontrol penambahan staff, role, dan akses')
@section('actions')<button class="btn btn-pink" data-modal-open="#addModal" type="button">+ Tambah Staff</button>@endsection
@section('content')
<div class="table-wrap"><table class="table">
    <thead><tr><th>Nama</th><th>Email (login)</th><th>HP</th><th>Role</th><th>Shift hari ini</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @foreach ($staff as $s)
        @php($sh = $s->shifts->first(fn ($x) => $x->shift_date->isToday()))
        <tr style="{{ $s->is_active ? '' : 'opacity:.55' }}">
            <td><b>{{ $s->name }}</b>@if ($s->must_change_password)<br><span class="xs" style="color:#b00020">belum ganti password</span>@endif</td>
            <td>{{ $s->email }}</td><td>{{ $s->phone ?: '-' }}</td>
            <td><span class="badge" style="background:var(--blue)">{{ $s->roleLabel() }}</span></td>
            <td>@if ($sh)<span class="badge" style="background:{{ $sh->color() }}">{{ $sh->label() }}</span>@else<span class="muted">-</span>@endif</td>
            <td><span class="badge" style="background:{{ $s->is_active ? 'var(--green)' : '#ddd' }}">{{ $s->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
            <td class="nowrap">
                <button class="btn btn-sm btn-yellow" type="button" data-modal-open="#editModal" data-action="{{ route('admin.staff.update', $s) }}"
                    data-fill-name="{{ $s->name }}" data-fill-email="{{ $s->email }}" data-fill-phone="{{ $s->phone }}" data-fill-role="{{ $s->role }}">Ubah</button>
                <form class="inline-form" method="POST" action="{{ route('admin.staff.reset', $s) }}" data-confirm="Reset password {{ $s->name }}?">@csrf<button class="btn btn-sm">Reset PW</button></form>
                @if ($s->id !== auth()->id())
                <form class="inline-form" method="POST" action="{{ route('admin.staff.toggle', $s) }}">@csrf<button class="btn btn-sm {{ $s->is_active ? 'btn-red' : 'btn-green' }}">{{ $s->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table></div>

<dialog class="modal" id="addModal" {{ $errors->any() && old('_f') === 'add' ? 'data-auto-open' : '' }}>
    <div class="modal-head"><h3>Tambah Staff</h3><button class="x-btn" data-modal-close type="button">✕</button></div>
    <div class="modal-body"><form method="POST" action="{{ route('admin.staff.store') }}">@csrf<input type="hidden" name="_f" value="add">
        @include('admin._staff-fields')
        <p class="small muted">Password sementara dibuat otomatis & ditampilkan sekali. Staff wajib menggantinya saat login pertama.</p>
        <button class="btn btn-pink btn-block">Buat Akun</button></form></div>
</dialog>
<dialog class="modal" id="editModal">
    <div class="modal-head"><h3>Ubah Staff</h3><button class="x-btn" data-modal-close type="button">✕</button></div>
    <div class="modal-body"><form method="POST" action="#">@csrf @method('PUT')
        @include('admin._staff-fields')<button class="btn btn-blue btn-block">Simpan</button></form></div>
</dialog>
@endsection
