<div class="grid g-main">
    <div class="card">
        <div class="card-head yellow"><h2>🛁 Antrean & Sedang Digrooming ({{ $open->count() }})</h2></div>
        @forelse ($open as $o)
            <div class="ticket" style="margin-bottom:10px">
                <div class="qn" style="font-size:{{ $o->isGuest() ? '15px' : '' }}">{{ $o->isGuest() ? '🛏️' : $o->visit->queueLabel() }}</div>
                <div>
                    <b>{{ $o->pet->emoji() }} {{ $o->pet->name }}</b>
                    <span class="badge" style="background:{{ $o->statusColor() }}">{{ $o->statusLabel() }}</span>
                    @if ($o->isGuest())<span class="badge" style="background:var(--orange)">Tamu Boarding • {{ $o->label() }}</span>@endif
                    <div class="small muted">{{ $o->pet->owner->name }} • {{ $o->pet->speciesLabel() }} {{ $o->pet->breed }} • sifat {{ $o->pet->temperament }}</div>
                    @if ($o->request_note)<div class="small">“{{ $o->request_note }}”</div>@endif
                </div>
                <div class="row">
                    @if ($o->status === 'in_progress')
                        <a class="btn btn-sm btn-blue" href="{{ route('grooming.work', $o) }}">Lanjutkan</a>
                    @else
                        <form method="POST" action="{{ route('grooming.call', $o) }}">@csrf<button class="btn btn-sm btn-pink">📣 {{ $o->status === 'called' ? 'Panggil Lagi' : 'Panggil' }}</button></form>
                        <a class="btn btn-sm btn-blue" href="{{ route('grooming.work', $o) }}">Buka & Mulai</a>
                        <form method="POST" action="{{ route('grooming.cancel', $o) }}" data-confirm="Batalkan order grooming ini?">@csrf<button class="btn btn-sm btn-red">✕</button></form>
                    @endif
                </div>
            </div>
        @empty
            <div class="empty">Tidak ada antrean grooming. 🛁</div>
        @endforelse
    </div>

    <div class="card">
        <div class="card-head green"><h2>✅ Selesai Hari Ini ({{ $done->count() }})</h2></div>
        @forelse ($done as $o)
            <div class="list-line">
                <div><b>{{ $o->isGuest() ? '🛏️' : $o->visit->queueLabel() }} {{ $o->pet->name }}</b>
                    <div class="xs muted">{{ $o->services->pluck('name')->join(', ') }}</div></div>
                <div class="right"><b>{{ rupiah($o->total()) }}</b><div class="xs muted">{{ $o->isGuest() ? 'ikut tagihan boarding' : 'ke kasir' }}</div></div>
            </div>
        @empty
            <div class="muted small">Belum ada.</div>
        @endforelse
    </div>
</div>
