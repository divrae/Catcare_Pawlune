@extends('layouts.app')
@section('title', 'Riwayat Kasir')
@section('page', 'Riwayat & Tutup Kas')
@section('actions')<button class="btn btn-blue" data-print type="button">🖨️ Cetak Tutup Kas</button>@endsection
@section('content')
<form class="row mb no-print" method="GET">
    <label class="label">Dari</label><input class="input sm" style="width:auto" type="date" name="from" value="{{ $from->toDateString() }}">
    <label class="label">Sampai</label><input class="input sm" style="width:auto" type="date" name="to" value="{{ $to->toDateString() }}">
    <button class="btn btn-sm btn-yellow">Tampilkan</button>
</form>
<div class="print-only"><h2>TUTUP KAS — {{ config('catcare.clinic.name') }}</h2><div>Periode {{ tgl($from) }} – {{ tgl($to) }} • Kasir: {{ auth()->user()->name }}</div></div>
<div class="grid g4 mb">
    <div class="stat c-green"><span class="lbl">Total</span><div class="val" style="font-size:1.35rem">{{ rupiah($sum['total']) }}</div><div class="sub">{{ $invoices->count() }} transaksi</div></div>
    <div class="stat"><span class="lbl">Tunai (uang di laci)</span><div class="val" style="font-size:1.35rem">{{ rupiah($sum['cash']) }}</div></div>
    <div class="stat c-blue"><span class="lbl">QRIS</span><div class="val" style="font-size:1.35rem">{{ rupiah($sum['qris']) }}</div></div>
    <div class="stat c-lilac"><span class="lbl">Transfer</span><div class="val" style="font-size:1.35rem">{{ rupiah($sum['transfer']) }}</div></div>
</div>
<div class="table-wrap"><table class="table">
    <thead><tr><th>Waktu</th><th>No. Invoice</th><th>Pelanggan</th><th>Metode</th><th>Kasir</th><th class="right">Total</th><th class="no-print"></th></tr></thead>
    <tbody>
    @forelse ($invoices as $i)
        <tr><td class="nowrap">{{ $i->paid_at->format('d/m H:i') }}</td><td class="mono">{{ $i->invoice_number }}</td><td>{{ $i->visit->pet->name }} <span class="muted small">({{ $i->visit->pet->owner->name }})</span></td>
            <td><span class="badge">{{ $i->methodLabel() }}</span></td><td>{{ $i->cashier->name }}</td><td class="num">{{ rupiah($i->grand_total) }}</td>
            <td class="no-print"><a class="btn btn-sm" target="_blank" href="{{ route('receipt.show', $i) }}">Struk</a></td></tr>
    @empty
        <tr><td colspan="7" class="center muted">Belum ada transaksi pada periode ini.</td></tr>
    @endforelse
    </tbody>
    <tfoot><tr><td colspan="5">TOTAL</td><td class="num">{{ rupiah($sum['total']) }}</td><td class="no-print"></td></tr></tfoot>
</table></div>
@endsection
