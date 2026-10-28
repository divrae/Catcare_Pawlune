@extends('layouts.public')
@section('title', 'Pantau Boarding')
@section('content')
<div class="container" style="padding-top:26px;padding-bottom:30px;max-width:900px">
    <a class="btn btn-sm mb" href="{{ route('owner.dashboard') }}">← Dashboard</a>
    <div class="card">
        <div class="card-head lilac"><h2>🛏️ {{ $boarding->pet->name }} — Kamar {{ $boarding->room?->room_number ?? '-' }}</h2><span class="badge" style="background:{{ $boarding->statusColor() }}">{{ $boarding->statusLabel() }}</span></div>
        <dl class="kv">
            <dt>Masuk</dt><dd>{{ tgl($boarding->check_in_date, 'l, d F Y') }}</dd>
            <dt>Rencana keluar</dt><dd>{{ tgl($boarding->check_out_date, 'l, d F Y') }}</dd>
            <dt>Makanan</dt><dd>{{ $boarding->diet_note ?: '-' }}</dd>
        </dl>
    </div>
    @if ($boarding->groomingOrders->where('status', '!=', 'cancelled')->isNotEmpty())
    <div class="card mt">
        <div class="card-head pink"><h2>🛁 Grooming Selama Menginap</h2></div>
        @foreach ($boarding->groomingOrders->where('status', '!=', 'cancelled') as $g)
            <div class="list-line"><div><span class="badge" style="background:{{ $g->statusColor() }}">{{ $g->statusLabel() }}</span>
                <div class="xs muted">{{ $g->services->pluck('name')->join(', ') ?: $g->request_note }}</div>
                @if ($g->result_note)<div class="small">{{ $g->result_note }}</div>@endif</div>
                @if ($g->status === 'done')<b>{{ rupiah($g->total()) }}</b>@endif</div>
        @endforeach
        <div class="xs muted mt-sm">Biaya ikut tagihan boarding, dibayar saat check-out.</div>
    </div>
    @endif
    <div class="card mt">
        <div class="card-head yellow"><h2>Catatan Harian (makan • suhu • kesehatan)</h2></div>
        @if ($boarding->logs->isEmpty())<div class="empty">Belum ada catatan.</div>@endif
        <div class="timeline">
            @foreach ($boarding->logs as $l)
                <div class="tl-item">
                    <div class="row"><b>{{ $l->logged_at->locale('id')->translatedFormat('l, d M • H:i') }}</b>
                        <span class="badge" style="background:{{ $l->healthColor() }}">{{ \App\Models\BoardingLog::HEALTH[$l->health_status] }}</span></div>
                    <div class="small">Makan: <b>{{ \App\Models\BoardingLog::MEAL[$l->meal] }}</b>{{ $l->food_note ? ' ('.$l->food_note.')' : '' }} • Suhu: <b>{{ $l->temperature_c ? $l->temperature_c.' °C' : '-' }}</b> • BAB: {{ \App\Models\BoardingLog::STOOL[$l->stool] }}{{ $l->medicine_given ? ' • 💊 obat diberikan' : '' }}</div>
                    @if ($l->note)<div class="small muted">“{{ $l->note }}”</div>@endif
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
