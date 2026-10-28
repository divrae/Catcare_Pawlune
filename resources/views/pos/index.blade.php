@extends('layouts.app')
@section('title', 'Kasir')
@section('page', 'Kasir (POS)')
@section('subtitle', 'Tarif dokter + obat + boarding dalam satu tagihan')
@section('actions')<a class="btn btn-yellow" href="{{ route('pos.history') }}">🧾 Riwayat & Tutup Kas</a>@endsection

@section('content')
<div class="grid g4 mb">
    <div class="stat c-green"><span class="lbl">Pemasukan hari ini</span><div class="val" style="font-size:1.35rem">{{ rupiah($today['total']) }}</div><div class="sub">{{ $today['count'] }} transaksi</div></div>
    <div class="stat"><span class="lbl">Tunai</span><div class="val" style="font-size:1.35rem">{{ rupiah($today['cash']) }}</div></div>
    <div class="stat c-blue"><span class="lbl">QRIS</span><div class="val" style="font-size:1.35rem">{{ rupiah($today['qris']) }}</div></div>
    <div class="stat c-lilac"><span class="lbl">Transfer</span><div class="val" style="font-size:1.35rem">{{ rupiah($today['transfer']) }}</div></div>
</div>

<div class="grid" style="grid-template-columns:minmax(0,320px) minmax(0,1fr)">
    <div class="card" style="align-self:start">
        <div class="card-head orange"><h2>Antrean Bayar ({{ $queue->count() }})</h2></div>
        @forelse ($queue as $q)
            @php($ready = $q->status === 'awaiting_payment')
            <a href="{{ $ready ? route('pos.index', ['visit' => $q->id]) : '#' }}" class="list-line" style="text-decoration:none;{{ $selected && $selected->id === $q->id ? 'background:var(--yellow);padding:8px' : '' }}{{ $ready ? '' : 'opacity:.55;cursor:not-allowed' }}">
                <div><b>{{ $q->queueLabel() }} {{ $q->pet->emoji() }} {{ $q->pet->name }}</b><div class="xs muted">{{ $q->pet->owner->name }}</div></div>
                <span class="badge" style="background:{{ $q->statusColor() }}">{{ $ready ? 'Siap bayar' : 'Masih menginap' }}</span>
            </a>
        @empty
            <div class="empty">Belum ada tagihan. Menunggu dokter/boarding menyelesaikan layanan.</div>
        @endforelse
    </div>

    <div>
    @if (! $selected)
        <div class="empty">Pilih antrean di kiri untuk menampilkan tagihan.</div>
    @else
        @php($v = $selected)
        <div class="card">
            <div class="card-head" style="background:{{ $v->statusColor() }}">
                <h2>{{ $v->queueLabel() }} • {{ $v->pet->emoji() }} {{ $v->pet->name }}</h2>
                <span class="small bold">{{ $v->pet->owner->name }} • {{ $v->pet->owner->phone }}</span>
            </div>
            @if ($summary['blocked'])<div class="flash warn">{{ $summary['blocked'] }}</div>@endif

            <div class="table-wrap"><table class="table">
                <thead><tr><th>Departemen</th><th>Item</th><th class="right">Qty</th><th class="right">Harga</th><th class="right">Subtotal</th><th></th></tr></thead>
                <tbody>
                @php($deptNames = ['medical' => '🩺 Klinik', 'pharmacy' => '💊 Obat', 'boarding' => '🛏️ Boarding', 'grooming' => '🛁 Grooming', 'other' => '✨ Lainnya'])
                @forelse ($summary['lines'] as $l)
                    <tr>
                        <td>{{ $deptNames[$l['department']] }}</td><td>{{ $l['description'] }}</td>
                        <td class="num">{{ $l['qty'] }}</td><td class="num">{{ rupiah($l['unit_price']) }}</td><td class="num">{{ rupiah($l['subtotal']) }}</td>
                        <td>@if (! empty($l['removable']))
                            <form method="POST" action="{{ route('pos.item.remove', [$v, $l['id']]) }}" class="inline-form">@csrf @method('DELETE')<button class="btn btn-sm btn-red">✕</button></form>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="center muted">Tagihan kosong.</td></tr>
                @endforelse
                </tbody>
                <tfoot><tr><td colspan="4">Subtotal</td><td class="num" id="subtotalCell" data-v="{{ $summary['subtotal'] }}">{{ rupiah($summary['subtotal']) }}</td><td></td></tr></tfoot>
            </table></div>

            @if (! $summary['blocked'])
            <form method="POST" action="{{ route('pos.item.add', $v) }}" class="row mt">
                @csrf
                <select class="select sm" name="tariff_id" style="max-width:340px" required>
                    <option value="">+ Tambah layanan (grooming, tindakan…)</option>
                    @foreach ($extraTariffs as $t)<option value="{{ $t->id }}">{{ $t->name }} — {{ rupiah($t->price) }}</option>@endforeach
                </select>
                <input class="input sm" style="width:72px" type="number" min="1" value="1" name="qty">
                <button class="btn btn-sm btn-yellow">Tambah</button>
            </form>
            @endif
        </div>

        @if (! $summary['blocked'])
        <form method="POST" action="{{ route('pos.pay', $v) }}" class="card mt" id="payForm">
            @csrf
            <div class="card-head green"><h2>Pembayaran</h2></div>
            <div class="pay-methods mb">
                <label class="pay-method"><input type="radio" name="payment_method" value="cash" checked><span class="big">💵</span>Tunai</label>
                <label class="pay-method"><input type="radio" name="payment_method" value="qris"><span class="big">▦</span>QRIS</label>
                <label class="pay-method"><input type="radio" name="payment_method" value="transfer"><span class="big">🏦</span>Transfer</label>
            </div>

            <div class="fgrid">
                <div class="field"><label>Diskon (Rp)</label><input class="input" type="number" min="0" max="{{ (int) $summary['subtotal'] }}" name="discount" id="discount" value="{{ old('discount', 0) }}"></div>
                <div class="field"><label>Total yang harus dibayar</label><div class="total-box" style="padding:8px 14px"><span class="amt" id="grand">{{ rupiah($summary['subtotal']) }}</span></div></div>
            </div>

            <div id="pane-cash">
                <div class="field"><label>Uang diterima (Rp)</label><input class="input mono" type="number" min="0" name="amount_paid" id="amountPaid" value="{{ old('amount_paid') }}" placeholder="0">
                    <div class="row mt-sm"><button type="button" class="btn btn-sm" data-quick="exact">Uang pas</button>@foreach ([50000, 100000, 200000] as $n)<button type="button" class="btn btn-sm" data-quick="{{ $n }}">{{ number_format($n, 0, ',', '.') }}</button>@endforeach</div></div>
                <div class="total-box" style="background:var(--blue);color:#000"><span>Kembalian</span><span class="mono" style="font-size:1.4rem" id="change">Rp 0</span></div>
            </div>

            <div id="pane-qris" class="hidden center">
                @if ($qrisImage)
                    <img src="{{ $qrisImage }}" alt="QRIS" style="width:240px;border:3px solid #000">
                @else
                    <div class="qr" style="width:240px;height:240px;margin:0 auto">{!! \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(220)->margin(0)->generate('QRIS-DEMO|'.config('catcare.payment.qris_merchant').'|'.$v->visit_code.'|'.(int) $summary['subtotal']) !!}</div>
                @endif
                <div class="bold mt-sm">{{ config('catcare.payment.qris_merchant') }}</div>
                <div class="small muted">Minta pelanggan scan lalu bayar sesuai total. Setelah notifikasi masuk di HP merchant, klik “Konfirmasi Bayar”.</div>
                @unless ($qrisImage)<div class="flash warn mt-sm small" style="text-align:left"><span class="mono"></span></div>@endunless
                <div class="field mt-sm" style="text-align:left"><label>No. referensi QRIS (opsional)</label><input class="input" name="reference_no" form="payForm" disabled data-pane="qris"></div>
            </div>

            <div id="pane-transfer" class="hidden">
                @foreach ($banks as $b)
                    <div class="list-line"><div><b>{{ $b['bank'] }}</b> <span class="mono bold" style="font-size:1.1rem">{{ $b['number'] }}</span><div class="xs muted">a.n. {{ $b['holder'] }}</div></div></div>
                @endforeach
                <div class="fgrid mt-sm">
                    <div class="field"><label>Bank pengirim / tujuan</label><select class="select" name="bank_name" disabled data-pane="transfer">@foreach ($banks as $b)<option>{{ $b['bank'] }}</option>@endforeach<option>Lainnya</option></select></div>
                    <div class="field"><label>No. referensi / 4 digit akhir bukti *</label><input class="input" name="reference_no" disabled data-pane="transfer" value="{{ old('reference_no') }}"></div>
                </div>
            </div>

            <button class="btn btn-green btn-block btn-lg mt" type="submit" data-confirm-pay>✔ Konfirmasi Bayar & Cetak Struk</button>
        </form>
        @endif
    @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const f = document.getElementById('payForm'); if (!f) return;
    const sub = parseFloat(document.getElementById('subtotalCell').dataset.v);
    const disc = document.getElementById('discount'), grand = document.getElementById('grand'), paid = document.getElementById('amountPaid'), change = document.getElementById('change');
    const total = () => Math.max(0, sub - Math.min(sub, parseFloat(disc.value || 0)));
    function recalc() {
        grand.textContent = fmtRp(total());
        change.textContent = fmtRp(Math.max(0, (parseFloat(paid.value || 0)) - total()));
    }
    function pane() {
        const m = f.querySelector('input[name=payment_method]:checked').value;
        ['cash', 'qris', 'transfer'].forEach(p => {
            document.getElementById('pane-' + p).classList.toggle('hidden', p !== m);
            f.querySelectorAll('[data-pane="' + p + '"]').forEach(e => e.disabled = (p !== m));
        });
        paid.disabled = (m !== 'cash');
    }
    f.querySelectorAll('input[name=payment_method]').forEach(r => r.addEventListener('change', pane));
    [disc, paid].forEach(e => e.addEventListener('input', recalc));
    f.querySelectorAll('[data-quick]').forEach(b => b.addEventListener('click', () => { paid.value = b.dataset.quick === 'exact' ? total() : b.dataset.quick; recalc(); }));
    f.addEventListener('submit', e => { if (!confirm('Konfirmasi pembayaran ' + grand.textContent + '?')) e.preventDefault(); });
    pane(); recalc();
})();
</script>
@endpush
