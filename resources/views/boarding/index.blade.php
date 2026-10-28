@extends('layouts.app')
@section('title', 'Boarding')
@section('page', 'Boarding & Penitipan')
@section('subtitle', 'Suite, check-in/out, dan catatan harian makan • suhu • kesehatan')
@section('actions')
    <a class="btn btn-yellow" target="_blank" href="{{ route('boarding.recap') }}">🖨️ Rekap Cetak</a>
    <button class="btn btn-pink" data-modal-open="#walkModal" type="button">+ Walk-in Titip</button>
@endsection

@section('content')
<div class="grid g4 mb">
    <div class="stat c-blue"><span class="lbl">Okupansi</span><div class="val">{{ $stats['rate'] }}%</div><div class="sub">{{ $stats['occupied'] }} / {{ $stats['total'] }} suite terisi</div></div>
    <div class="stat c-green"><span class="lbl">Suite kosong</span><div class="val">{{ $stats['free'] }}</div><div class="sub">siap dihuni</div></div>
    <div class="stat c-pink"><span class="lbl">Jatuh tempo keluar</span><div class="val">{{ $stats['due'] }}</div><div class="sub">check-out hari ini / lewat</div></div>
    <div class="stat c-lilac"><span class="lbl">Check-in hari ini</span><div class="val">{{ $stats['checkins_today'] }}</div><div class="sub">{{ $waiting->count() }} menunggu check-in</div></div>
</div>

<div class="card mb">
    <div class="card-head yellow"><h2>⏳ Menunggu Check-in ({{ $waiting->count() }})</h2></div>
    @forelse ($waiting as $b)
        <div class="ticket" style="margin-bottom:10px">
            <div class="qn">{{ $b->visit->queueLabel() }}</div>
            <div>
                <b>{{ $b->pet->emoji() }} {{ $b->pet->name }}</b>
                @if ($b->is_referral)<span class="badge" style="background:var(--red)">Rujukan Dokter</span>@endif
                <span class="badge" style="background:{{ $b->visit->statusColor() }}">{{ $b->visit->statusLabel() }}</span>
                <div class="small muted">{{ $b->pet->owner->name }} • {{ $b->pet->owner->phone }} • {{ tgl($b->check_in_date, 'd M') }} → {{ tgl($b->check_out_date, 'd M') }} ({{ $b->billedDays() }} hari)</div>
                @if ($b->referral_note)<div class="small">🩺 {{ $b->referral_note }}</div>@endif
                @if ($b->diet_note)<div class="small">🍽️ {{ $b->diet_note }}</div>@endif
            </div>
            <div class="row">
                @if ($b->visit->status === 'waiting' || $b->visit->status === 'called')
                    <form method="POST" action="{{ route('boarding.call', $b->visit) }}">@csrf<button class="btn btn-sm btn-pink">📣 {{ $b->visit->status === 'called' ? 'Panggil Lagi' : 'Panggil' }}</button></form>
                @endif
                <button class="btn btn-sm btn-green" type="button" data-modal-open="#checkinModal"
                    data-action="{{ route('boarding.checkin', $b) }}" data-fill-pet="{{ $b->pet->name }}"
                    data-fill-diet_note="{{ $b->diet_note }}" data-fill-belongings="{{ $b->belongings }}">Check-in</button>
            </div>
        </div>
    @empty
        <div class="empty">Tidak ada yang menunggu check-in.</div>
    @endforelse
</div>

<div class="card">
    <div class="card-head lilac"><h2>🏠 Peta Suite ({{ $rooms->count() }})</h2>
        <span class="row"><span class="badge" style="background:var(--green)">Kosong</span><span class="badge" style="background:var(--blue)">Terisi</span><span class="badge" style="background:var(--red)">Jatuh tempo</span><span class="badge" style="background:#ddd">Maintenance</span></span></div>
    <div class="rooms">
        @foreach ($rooms as $room)
            @php
                $state = $room->state(); $st = $room->currentStay; $last = $st?->logs->first();
            @endphp
            <div class="room {{ $state }} {{ $st && $st->check_out_date->lte(today()) ? 'due' : '' }}">
                <div class="room-head"><span>Suite #{{ $room->room_number }}</span><span class="xs">{{ strtoupper($room->room_type) }}</span></div>
                <div class="room-body">
                    @if ($state === 'occupied')
                        <b style="font-size:1.05rem">{{ $st->pet->emoji() }} {{ $st->pet->name }}</b>
                        <div class="xs muted">{{ $st->pet->owner->name }}</div>
                        @if ($st->groomingOrders->whereIn('status', ['waiting', 'called', 'in_progress'])->isNotEmpty())<span class="badge" style="background:var(--pink)">🛁 Grooming berjalan</span>@endif
                        <div class="small mt-sm">Hari ke-{{ $st->dayNumber() }} • keluar {{ tgl($st->check_out_date, 'd M') }}</div>
                        @if ($last)<div class="small">🌡️ {{ $last->temperature_c ?? '-' }}° • 🍽️ {{ \App\Models\BoardingLog::MEAL[$last->meal] }}<br><span class="badge" style="background:{{ $last->healthColor() }}">{{ \App\Models\BoardingLog::HEALTH[$last->health_status] }}</span> <span class="xs muted">{{ $last->logged_at->diffForHumans() }}</span></div>
                        @else<div class="small" style="color:#b00020;font-weight:700">Belum ada catatan hari ini</div>@endif
                        <div class="row mt-sm">
                            <button class="btn btn-sm btn-yellow" type="button" data-modal-open="#logModal" data-action="{{ route('boarding.logs.store', $st) }}" data-fill-pet="{{ $st->pet->name }}">Catat</button>
                            <a class="btn btn-sm" href="{{ route('boarding.show', $st) }}">Detail</a>
                            <form method="POST" action="{{ route('boarding.checkout', $st) }}" data-confirm="Check-out {{ $st->pet->name }}? Tagihan akan masuk ke kasir." class="inline-form">@csrf<button class="btn btn-sm btn-red">Keluar</button></form>
                        </div>
                    @elseif ($state === 'maintenance')
                        <div class="muted bold">🔧 Sedang maintenance</div>
                    @else
                        <div class="muted">{{ $room->name }}</div>
                        <div class="bold" style="margin-top:6px">✨ Kosong</div>
                        <div class="mono small">{{ rupiah($room->pricePerDay()) }} / hari</div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>

{{-- Modal check-in --}}
<dialog class="modal" id="checkinModal">
    <div class="modal-head"><h3>Check-in <span data-fill="pet"></span></h3><button class="x-btn" data-modal-close type="button">✕</button></div>
    <div class="modal-body">
        <form method="POST" action="#">@csrf
            <div class="field"><label>Pilih suite kosong</label>
                <select class="select" name="room_id" required>
                    <option value="">— pilih —</option>
                    @foreach ($freeRooms as $r)<option value="{{ $r->id }}">Suite #{{ $r->room_number }} • {{ $r->name }} ({{ strtoupper($r->room_type) }}) — {{ rupiah($r->pricePerDay()) }}/hari</option>@endforeach
                </select>
                @if ($freeRooms->isEmpty())<span class="err">Semua suite penuh / maintenance.</span>@endif</div>
            <div class="field"><label>Makanan / kebiasaan</label><input class="input" name="diet_note"></div>
            <div class="field"><label>Barang bawaan</label><input class="input" name="belongings"></div>
            <button class="btn btn-green btn-block btn-lg">Check-in Sekarang</button>
        </form>
    </div>
</dialog>

{{-- Modal catatan harian --}}
<dialog class="modal" id="logModal">
    <div class="modal-head"><h3>Catatan Harian — <span data-fill="pet"></span></h3><button class="x-btn" data-modal-close type="button">✕</button></div>
    <div class="modal-body">
        <form method="POST" action="#">@csrf
            @include('boarding._log-fields')
            <button class="btn btn-yellow btn-block btn-lg">Simpan Catatan</button>
        </form>
    </div>
</dialog>

{{-- Modal walk-in --}}
<dialog class="modal" id="walkModal" {{ $errors->has('pass_code') || $errors->has('owner_name') || $errors->has('owner_phone') || $errors->has('pet_name') || $errors->has('check_out_date') ? 'data-auto-open' : '' }}>
    <div class="modal-head"><h3>Walk-in Penitipan</h3><button class="x-btn" data-modal-close type="button">✕</button></div>
    <div class="modal-body">
        <form method="POST" action="{{ route('boarding.walkin') }}">@csrf
            <div class="field"><label>Punya Soulmate Pass? (opsional)</label><input class="input mono" name="pass_code" value="{{ old('pass_code') }}" placeholder="PWL-XXXXXX" style="text-transform:uppercase"></div>
            <div class="fgrid">
                <div class="field"><label>Nama pemilik</label><input class="input" name="owner_name" value="{{ old('owner_name') }}"></div>
                <div class="field"><label>Nomor HP</label><input class="input" name="owner_phone" value="{{ old('owner_phone') }}"></div>
                <div class="field"><label>Nama hewan</label><input class="input" name="pet_name" value="{{ old('pet_name') }}"></div>
                <div class="field"><label>Jenis</label><select class="select" name="species">@foreach ($species as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
                <div class="field"><label>Ras</label><input class="input" name="breed" value="{{ old('breed') }}"></div>
                <div class="field"><label>Jenis kelamin</label><select class="select" name="gender"><option value="jantan">Jantan</option><option value="betina">Betina</option></select></div>
                <div class="field"><label>Tanggal masuk</label><input class="input" type="date" name="check_in_date" value="{{ old('check_in_date', today()->toDateString()) }}" required></div>
                <div class="field"><label>Tanggal keluar</label><input class="input" type="date" name="check_out_date" value="{{ old('check_out_date', today()->addDays(2)->toDateString()) }}" required></div>
                <div class="field full"><label>Makanan / kebiasaan</label><input class="input" name="diet_note" value="{{ old('diet_note') }}"></div>
                <div class="field full"><label>Barang bawaan</label><input class="input" name="belongings" value="{{ old('belongings') }}"></div>
            </div>
            <button class="btn btn-pink btn-block btn-lg">Daftarkan & Beri Nomor Antrean</button>
        </form>
    </div>
</dialog>
@endsection
