@extends('layouts.app')
@section('title', 'Testimoni')
@section('page', 'Testimoni Pelanggan')
@section('subtitle', 'Yang berstatus Tampil akan muncul di beranda website')
@section('content')
<div class="grid g2">
@forelse ($testimonials as $t)
    <div class="card" style="{{ $t->is_published ? '' : 'opacity:.6' }}">
        <div class="row between"><b>{{ $t->user->name }}</b><span class="rating">{{ str_repeat('★', $t->rating) }}</span></div>
        <p class="mt-sm">“{{ $t->comment }}”</p>
        <div class="row between"><span class="xs muted">{{ tgl($t->created_at) }}</span>
            <span class="row"><form class="inline-form" method="POST" action="{{ route('admin.testimonials.toggle', $t) }}">@csrf<button class="btn btn-sm {{ $t->is_published ? 'btn-yellow' : 'btn-green' }}">{{ $t->is_published ? 'Sembunyikan' : 'Tampilkan' }}</button></form>
            <form class="inline-form" method="POST" action="{{ route('admin.testimonials.destroy', $t) }}" data-confirm="Hapus testimoni ini?">@csrf @method('DELETE')<button class="btn btn-sm btn-red">Hapus</button></form></span></div>
    </div>
@empty<div class="empty">Belum ada testimoni.</div>@endforelse
</div>
<div class="mt">{{ $testimonials->links('partials.pagination') }}</div>
@endsection
