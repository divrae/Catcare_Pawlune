@extends('layouts.public')
@section('title', 'Detail Kunjungan')
@section('content')
@php($mr = $visit->medicalRecord)
<div class="container" style="padding-top:26px;padding-bottom:30px;max-width:900px">
    <a class="btn btn-sm mb" href="{{ route('owner.dashboard') }}">← Dashboard</a>
    <div class="card">
        <div class="card-head" style="background:{{ $visit->statusColor() }}">
            <h2>{{ $visit->pet->emoji() }} {{ $visit->pet->name }} — {{ $visit->queueLabel() }}</h2>
            <span class="badge" style="background:#fff">{{ $visit->statusLabel() }}</span>
        </div>
        <dl class="kv">
            <dt>Tanggal</dt><dd>{{ tgl($visit->queue_date, 'l, d F Y') }}</dd>
            <dt>Layanan</dt><dd>{{ $visit->serviceLabel() }} ({{ $visit->source === 'walk_in' ? 'Walk-in' : 'Online' }})</dd>
            <dt>Keluhan</dt><dd>{{ $visit->complaint ?: '-' }}</dd>
            @if ($visit->doctor)<dt>Dokter</dt><dd>{{ $visit->doctor->name }}</dd>@endif
        </dl>
    </div>

    @if ($mr)
    <div class="card mt">
        <div class="card-head blue"><h2>🩺 Hasil Pemeriksaan</h2></div>
        <dl class="kv">
            <dt>Diagnosa</dt><dd>{{ $mr->diagnosis }}</dd>
            <dt>Anamnesis</dt><dd>{{ $mr->anamnesis ?: '-' }}</dd>
            <dt>Berat / Suhu</dt><dd>{{ $mr->weight_kg ? $mr->weight_kg.' kg' : '-' }} / {{ $mr->temperature_c ? $mr->temperature_c.' °C' : '-' }}</dd>
            @if ($mr->treatment_note)<dt>Catatan</dt><dd>{{ $mr->treatment_note }}</dd>@endif
            @if ($mr->follow_up_date)<dt>Kontrol ulang</dt><dd>{{ tgl($mr->follow_up_date, 'l, d F Y') }}</dd>@endif
        </dl>
        @if ($visit->prescriptions->isNotEmpty())
            <h3 class="mt">💊 Resep Obat</h3>
            <div class="table-wrap mt-sm"><table class="table">
                <thead><tr><th>Obat</th><th>Jumlah</th><th>Dosis</th><th>Aturan pakai</th></tr></thead>
                <tbody>@foreach ($visit->prescriptions as $rx)<tr><td><b>{{ $rx->medicine_name }}</b><br><span class="xs muted">{{ $rx->category }}</span></td><td>{{ $rx->qty }}</td><td>{{ $rx->dosage ?: '-' }}</td><td>{{ $rx->instruction ?: '-' }}</td></tr>@endforeach</tbody>
            </table></div>
        @endif
    </div>
    @endif

    @if ($visit->boarding)
    <div class="card mt">
        <div class="card-head lilac"><h2>🛏️ Boarding</h2><span class="badge" style="background:{{ $visit->boarding->statusColor() }}">{{ $visit->boarding->statusLabel() }}</span></div>
        <dl class="kv">
            <dt>Kamar</dt><dd>{{ $visit->boarding->room?->room_number ?? 'Belum ditentukan' }}</dd>
            <dt>Tanggal</dt><dd>{{ tgl($visit->boarding->check_in_date) }} → {{ tgl($visit->boarding->check_out_date) }} ({{ $visit->boarding->billedDays() }} hari)</dd>
        </dl>
        @if (in_array($visit->boarding->status, ['active', 'finished']))<a class="btn btn-sm btn-lilac mt-sm" href="{{ route('owner.boarding.show', $visit->boarding) }}">Lihat catatan harian</a>@endif
    </div>
    @endif

    @if ($visit->invoice)
    <div class="card mt">
        <div class="card-head green"><h2>🧾 Pembayaran</h2><a class="btn btn-sm" target="_blank" href="{{ route('receipt.show', $visit->invoice) }}">Lihat Struk</a></div>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Item</th><th class="right">Qty</th><th class="right">Harga</th><th class="right">Subtotal</th></tr></thead>
            <tbody>@foreach ($visit->invoice->items as $it)<tr><td>{{ $it->description }}</td><td class="num">{{ $it->qty }}</td><td class="num">{{ rupiah($it->unit_price) }}</td><td class="num">{{ rupiah($it->subtotal) }}</td></tr>@endforeach</tbody>
            <tfoot><tr><td colspan="3">Total ({{ $visit->invoice->methodLabel() }})</td><td class="num">{{ rupiah($visit->invoice->grand_total) }}</td></tr></tfoot>
        </table></div>
    </div>
    @endif
</div>
@endsection
