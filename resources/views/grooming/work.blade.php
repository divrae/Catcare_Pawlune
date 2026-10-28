@extends('layouts.app')
@section('title', 'Grooming ' . $order->pet->name)
@section('page', 'Grooming: ' . $order->pet->name)
@section('subtitle', $order->isGuest() ? 'Tamu boarding • ' . $order->label() : 'Antrean ' . $order->visit->queueLabel())
@section('actions')<a class="btn btn-sm" href="{{ route('grooming.index') }}">← Kembali</a>@endsection

@section('content')
<div class="grid g-main">
    <div class="stack">
        <div class="card">
            <div class="card-head yellow"><h2>{{ $order->pet->emoji() }} {{ $order->pet->name }} <span class="badge" style="background:{{ $order->statusColor() }}">{{ $order->statusLabel() }}</span></h2></div>
            <div class="small">{{ $order->pet->speciesLabel() }} • {{ $order->pet->breed }} • {{ $order->pet->gender }} • sifat <b>{{ $order->pet->temperament }}</b></div>
            <div class="small muted">Pemilik: {{ $order->pet->owner->name }} • {{ $order->pet->owner->phone }}</div>
            @if ($order->request_note)<div class="alert mt-sm">Permintaan: “{{ $order->request_note }}”</div>@endif
            @if ($order->isGuest())<div class="alert mt-sm">🛏️ Tamu boarding ({{ $order->label() }}). Biaya masuk ke tagihan boarding, dibayar saat check-out.</div>@endif
        </div>

        @if (in_array($order->status, ['waiting', 'called']))
        <div class="card">
            <div class="card-head pink"><h2>Mulai Grooming</h2></div>
            <form method="POST" action="{{ route('grooming.start', $order) }}">@csrf
                <div class="field"><label>Kondisi bulu / kulit sebelum grooming</label><input class="input" name="coat_condition" placeholder="mis. kusut, ada kutu, jamur ringan"></div>
                <button class="btn btn-blue btn-lg btn-block">▶ Mulai Grooming</button>
            </form>
        </div>
        @elseif ($order->status === 'in_progress')
        <div class="card">
            <div class="card-head blue"><h2>Layanan yang Dikerjakan</h2></div>
            <form method="POST" action="{{ route('grooming.finish', $order) }}">@csrf
                @error('items')<div class="alert alert-error">{{ $message }}</div>@enderror
                <div class="table-wrap"><table class="table">
                    <thead><tr><th>Layanan</th><th class="right">Harga</th><th style="width:90px">Qty</th></tr></thead>
                    <tbody>
                    @forelse ($tariffs as $t)
                        <tr><td>{{ $t->name }}</td><td class="num">{{ rupiah($t->price) }}</td>
                            <td><input class="input" type="number" min="0" max="99" name="items[{{ $t->id }}]" value="{{ old('items.'.$t->id, $selected[$t->id] ?? 0) }}"></td></tr>
                    @empty
                        <tr><td colspan="3" class="center muted">Belum ada tarif Grooming. Minta admin menambahkannya di menu Tarif.</td></tr>
                    @endforelse
                    </tbody>
                </table></div>
                <div class="field mt"><label>Catatan hasil / saran untuk pemilik</label><textarea class="textarea" name="result_note">{{ old('result_note') }}</textarea></div>
                <button class="btn btn-green btn-lg btn-block">✔ Selesai & {{ $order->isGuest() ? 'Masukkan ke Tagihan Boarding' : 'Kirim ke Kasir' }}</button>
            </form>
        </div>
        @else
        <div class="card">
            <div class="card-head green"><h2>Ringkasan</h2></div>
            @foreach ($order->services as $s)<div class="list-line"><span>{{ $s->name }} × {{ $s->qty }}</span><b>{{ rupiah($s->subtotal) }}</b></div>@endforeach
            <div class="list-line"><b>Total</b><b>{{ rupiah($order->total()) }}</b></div>
            @if ($order->result_note)<div class="small mt-sm">{{ $order->result_note }}</div>@endif
        </div>
        @endif
    </div>

    <div class="card">
        <div class="card-head"><h2>Riwayat Grooming</h2></div>
        @forelse ($history as $h)
            <div class="list-line"><div><b>{{ $h->finished_at->format('d M Y') }}</b><div class="xs muted">{{ $h->services->pluck('name')->join(', ') }}</div>
                @if ($h->coat_condition)<div class="xs">Kondisi: {{ $h->coat_condition }}</div>@endif</div></div>
        @empty
            <div class="muted small">Belum pernah digrooming di sini.</div>
        @endforelse
    </div>
</div>
@endsection
