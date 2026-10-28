@php
    $row = function ($v) { return $v; };
@endphp
<div class="grid g-main">
    <div class="stack">
        @if ($called->isNotEmpty())
        <div class="card">
            <div class="card-head pink"><h2>🔔 Sudah Dipanggil ({{ $called->count() }})</h2></div>
            <div class="stack-sm">
            @foreach ($called as $v)
                <div class="ticket">
                    <div class="qn">{{ $v->queueLabel() }}</div>
                    <div><b>{{ $v->pet->emoji() }} {{ $v->pet->name }}</b> <span class="muted small">• {{ $v->pet->owner->name }}</span>
                        <div class="small">{{ $v->complaint }}</div>
                        <div class="xs muted">Dipanggil {{ $v->called_at?->diffForHumans() }} oleh {{ $v->doctor?->name }}</div></div>
                    <div class="row">
                        <form method="POST" action="{{ route('doctor.start', $v) }}">@csrf<button class="btn btn-sm btn-blue">Mulai Periksa</button></form>
                        <form method="POST" action="{{ route('doctor.call', $v) }}">@csrf<button class="btn btn-sm btn-yellow">Panggil Lagi</button></form>
                        <form method="POST" action="{{ route('doctor.noshow', $v) }}" data-confirm="Tandai tidak hadir?">@csrf<button class="btn btn-sm btn-red">Tidak Hadir</button></form>
                    </div>
                </div>
            @endforeach
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-head yellow"><h2>⏳ Antrean Menunggu ({{ $waiting->count() }})</h2></div>
            @forelse ($waiting as $v)
                <div class="ticket" style="margin-bottom:10px">
                    <div class="qn">{{ $v->queueLabel() }}</div>
                    <div><b>{{ $v->pet->emoji() }} {{ $v->pet->name }}</b>
                        <span class="badge" style="background:{{ $v->source === 'walk_in' ? 'var(--orange)' : 'var(--blue)' }}">{{ $v->source === 'walk_in' ? 'Walk-in' : 'Online' }}</span>
                        <div class="small muted">{{ $v->pet->owner->name }} • {{ $v->pet->speciesLabel() }} {{ $v->pet->breed }} • sifat {{ $v->pet->temperament }}</div>
                        <div class="small">“{{ $v->complaint }}”</div></div>
                    <div class="row">
                        <form method="POST" action="{{ route('doctor.call', $v) }}">@csrf<button class="btn btn-sm btn-pink">📣 Panggil</button></form>
                        <form method="POST" action="{{ route('doctor.start', $v) }}">@csrf<button class="btn btn-sm btn-blue">Langsung Periksa</button></form>
                    </div>
                </div>
            @empty
                <div class="empty">Tidak ada antrean menunggu. ☕</div>
            @endforelse
        </div>
    </div>

    <div class="stack">
        <div class="card">
            <div class="card-head blue"><h2>🩺 Sedang Diperiksa ({{ $treating->count() }})</h2></div>
            @forelse ($treating as $v)
                <div class="list-line">
                    <div><b>{{ $v->queueLabel() }} {{ $v->pet->name }}</b><div class="xs muted">{{ $v->doctor?->name }} • mulai {{ $v->started_at?->format('H:i') }}</div></div>
                    <a class="btn btn-sm btn-blue" href="{{ route('doctor.exam', $v) }}">Buka</a>
                </div>
            @empty
                <div class="muted small">Belum ada pasien yang diperiksa.</div>
            @endforelse
        </div>
        <div class="card">
            <div class="card-head green"><h2>✅ Selesai Hari Ini ({{ $doneCount }})</h2></div>
            @forelse ($done as $v)
                <div class="list-line">
                    <div><b>{{ $v->queueLabel() }} {{ $v->pet->name }}</b><div class="xs muted">{{ $v->doctor?->name }}</div></div>
                    <div class="row"><span class="badge" style="background:{{ $v->statusColor() }}">{{ $v->statusLabel() }}</span>
                        <a class="btn btn-sm" href="{{ route('doctor.exam', $v) }}">Lihat</a></div>
                </div>
            @empty
                <div class="muted small">Belum ada.</div>
            @endforelse
        </div>
    </div>
</div>
