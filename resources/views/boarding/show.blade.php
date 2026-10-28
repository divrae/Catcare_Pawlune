@extends('layouts.app')
@section('title', 'Detail Boarding')
@section('page', 'Boarding ' . $boarding->pet->name)
@section('subtitle', 'Kamar ' . ($boarding->room?->room_number ?? '-') . ' • ' . $boarding->statusLabel())
@section('actions')
    <a class="btn" href="{{ route('boarding.index') }}">← Kembali</a>
    <a class="btn btn-blue" target="_blank" href="{{ route('boarding.print', $boarding) }}">🖨️ Cetak Rekap</a>
@endsection
@section('content')
<div class="grid g-main">
    <div class="card">
        <div class="card-head yellow"><h2>Riwayat Catatan Harian</h2>
            @if ($boarding->status === 'active')<button class="btn btn-sm btn-green" data-modal-open="#logModal" type="button">+ Catat</button>@endif</div>
        @if ($boarding->logs->isEmpty())<div class="empty">Belum ada catatan.</div>@endif
        <div class="timeline">
            @foreach ($boarding->logs as $l)
                <div class="tl-item">
                    <div class="row"><b>{{ $l->logged_at->locale('id')->translatedFormat('l, d M • H:i') }}</b>
                        <span class="badge" style="background:{{ $l->healthColor() }}">{{ $mealOptions ? $healthOptions[$l->health_status] : '' }}</span></div>
                    <div class="small">🍽️ {{ $mealOptions[$l->meal] }}{{ $l->food_note ? ' ('.$l->food_note.')' : '' }} • 🌡️ {{ $l->temperature_c ? $l->temperature_c.' °C' : '-' }} • 💩 {{ $stoolOptions[$l->stool] }}{{ $l->medicine_given ? ' • 💊 obat diberikan' : '' }}</div>
                    @if ($l->note)<div class="small muted">“{{ $l->note }}”</div>@endif
                    <div class="xs muted">oleh {{ $l->recorder?->name ?? '-' }}</div>
                </div>
            @endforeach
        </div>
    </div>
    <div class="stack">
        <div class="card">
            <div class="card-head pink"><h2>🛁 Grooming Tamu</h2></div>
            @forelse ($boarding->groomingOrders->where('status', '!=', 'cancelled') as $g)
                <div class="list-line"><div><span class="badge" style="background:{{ $g->statusColor() }}">{{ $g->statusLabel() }}</span>
                    <div class="xs muted">{{ $g->services->pluck('name')->join(', ') ?: ($g->request_note ?: 'Menunggu groomer') }}</div></div>
                    @if ($g->status === 'done')<b>{{ rupiah($g->total()) }}</b>@endif</div>
            @empty
                <div class="muted small">Belum ada grooming selama menginap.</div>
            @endforelse
            @if ($boarding->status === 'active')
                <form method="POST" action="{{ route('boarding.grooming', $boarding) }}" class="mt-sm">@csrf
                    <div class="field"><input class="input" name="request_note" placeholder="Catatan (mis. mandi + gunting kuku)"></div>
                    <button class="btn btn-pink btn-block">+ Pesan Grooming</button>
                    <div class="xs muted mt-sm">Biaya otomatis masuk tagihan boarding. Check-out diblokir selama grooming belum selesai.</div>
                </form>
            @endif
        </div>
        <div class="card">
            <div class="card-head lilac"><h2>Info</h2></div>
            <dl class="kv">
                <dt>Hewan</dt><dd>{{ $boarding->pet->emoji() }} {{ $boarding->pet->name }} ({{ $boarding->pet->speciesLabel() }})</dd>
                <dt>Pemilik</dt><dd>{{ $boarding->pet->owner->name }}<br>{{ $boarding->pet->owner->phone }}</dd>
                <dt>Masuk</dt><dd>{{ tgl($boarding->check_in_date) }}</dd>
                <dt>Keluar</dt><dd>{{ tgl($boarding->check_out_date) }}</dd>
                <dt>Lama</dt><dd>{{ $boarding->billedDays() }} hari × {{ rupiah($boarding->rate_per_day) }}</dd>
                <dt>Biaya inap</dt><dd class="bold">{{ rupiah($boarding->total()) }}</dd>
                @php($groomTotal = $boarding->groomingOrders->where('status', 'done')->sum(fn ($o) => $o->total()))
                @if ($groomTotal > 0)<dt>Grooming</dt><dd class="bold">{{ rupiah($groomTotal) }}</dd>@endif
                <dt>Makanan</dt><dd>{{ $boarding->diet_note ?: '-' }}</dd>
                <dt>Barang</dt><dd>{{ $boarding->belongings ?: '-' }}</dd>
                @if ($boarding->referral_note)<dt>Rujukan</dt><dd>{{ $boarding->referral_note }}</dd>@endif
            </dl>
        </div>
    </div>
</div>

@if ($boarding->status === 'active')
<dialog class="modal" id="logModal">
    <div class="modal-head"><h3>Catatan Harian — {{ $boarding->pet->name }}</h3><button class="x-btn" data-modal-close type="button">✕</button></div>
    <div class="modal-body"><form method="POST" action="{{ route('boarding.logs.store', $boarding) }}">@csrf
        @include('boarding._log-fields')<button class="btn btn-yellow btn-block btn-lg">Simpan Catatan</button></form></div>
</dialog>
@endif
@endsection
