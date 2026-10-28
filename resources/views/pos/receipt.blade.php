@extends('layouts.base')
@section('title', 'Struk ' . $invoice->invoice_number)
@push('head')
<style>
    body{background:#e9e6f0;background-image:none}
    .sheet{width:340px;margin:22px auto;background:#fff;border:3px solid #000;box-shadow:6px 6px 0 #000;padding:18px 16px;font-family:var(--mono);font-size:12px;line-height:1.45}
    .sheet h2{font-size:17px;text-align:center;margin:0} .c{text-align:center} .dash{border-top:2px dashed #000;margin:9px 0}
    .r2{display:flex;justify-content:space-between;gap:8px} .tot{font-size:15px;font-weight:800}
    .item .n{font-weight:700} .bar-actions{width:340px;margin:16px auto 0;display:flex;gap:8px}
    @media print{ body{background:#fff} .sheet{box-shadow:none;border:0;margin:0;width:100%} .bar-actions{display:none} }
</style>
@endpush
@section('body')
@php($v = $invoice->visit)
<div class="bar-actions no-print">
    <button class="btn btn-blue" data-print type="button" style="flex:1">🖨️ Cetak</button>
    <a class="btn" style="flex:1" href="{{ auth()->user()->role === 'owner' ? route('owner.dashboard') : route('pos.index') }}">← Kembali</a>
</div>
<div class="sheet">
    <h2>{{ config('catcare.clinic.name') }}</h2>
    <div class="c">{{ config('catcare.clinic.address') }}<br>Telp {{ config('catcare.clinic.phone') }}</div>
    <div class="dash"></div>
    <div class="r2"><span>No</span><b>{{ $invoice->invoice_number }}</b></div>
    <div class="r2"><span>Tanggal</span><span>{{ $invoice->paid_at->format('d/m/Y H:i') }}</span></div>
    <div class="r2"><span>Kasir</span><span>{{ $invoice->cashier->name }}</span></div>
    <div class="r2"><span>Antrean</span><span>{{ $v->queueLabel() }}</span></div>
    <div class="r2"><span>Pemilik</span><span>{{ $v->pet->owner->name }}</span></div>
    <div class="r2"><span>Hewan</span><span>{{ $v->pet->name }} ({{ $v->pet->speciesLabel() }})</span></div>
    @if ($v->medicalRecord?->doctor)<div class="r2"><span>Dokter</span><span>{{ $v->medicalRecord->doctor->name }}</span></div>@endif
    <div class="dash"></div>
    @php($dn = ['medical' => 'KLINIK', 'pharmacy' => 'OBAT', 'boarding' => 'BOARDING', 'grooming' => 'GROOMING', 'other' => 'LAINNYA'])
    @foreach ($invoice->items->groupBy('department') as $dep => $items)
        <div class="bold">{{ $dn[$dep] }}</div>
        @foreach ($items as $it)
            <div class="item"><div class="n">{{ $it->description }}</div><div class="r2"><span>{{ $it->qty }} x {{ number_format($it->unit_price, 0, ',', '.') }}</span><span>{{ number_format($it->subtotal, 0, ',', '.') }}</span></div></div>
        @endforeach
    @endforeach
    <div class="dash"></div>
    <div class="r2"><span>Subtotal</span><span>{{ rupiah($invoice->grand_total + $invoice->discount) }}</span></div>
    @if ($invoice->discount > 0)<div class="r2"><span>Diskon</span><span>-{{ rupiah($invoice->discount) }}</span></div>@endif
    <div class="r2 tot"><span>TOTAL</span><span>{{ rupiah($invoice->grand_total) }}</span></div>
    <div class="r2"><span>Bayar ({{ $invoice->methodLabel() }})</span><span>{{ rupiah($invoice->amount_paid) }}</span></div>
    @if ($invoice->payment_method === 'cash')<div class="r2"><span>Kembali</span><span>{{ rupiah($invoice->change_amount) }}</span></div>@endif
    @if ($invoice->bank_name)<div class="r2"><span>Bank</span><span>{{ $invoice->bank_name }}</span></div>@endif
    @if ($invoice->reference_no)<div class="r2"><span>Ref</span><span>{{ $invoice->reference_no }}</span></div>@endif
    <div class="dash"></div>
    <div class="c" style="width:90px;margin:0 auto">{!! \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(90)->margin(0)->generate($invoice->invoice_number) !!}</div>
    <div class="c"><b>LUNAS ✔</b><br>Terima kasih sudah merawat anabul bersama kami 🐾</div>
</div>
@endsection
@push('scripts')
@if (request()->boolean('print'))<script>window.addEventListener('load', () => setTimeout(() => window.print(), 400));</script>@endif
@endpush
