@extends('layouts.public')
@section('title', 'Dashboard Saya')

@section('content')
<div class="container" style="padding-top:26px;padding-bottom:30px">
    <div class="row between mb">
        <div>
            <h1>Halo, {{ \Illuminate\Support\Str::words($user->name, 2, '') }}! 👋</h1>
            <div class="muted">Kelola anabul, ambil antrean, dan pantau statusnya di sini.</div>
        </div>
        <div class="row">
            <span class="badge" style="background:var(--blue)">Antrean periksa: <span id="wClinic">{{ $waitingClinic }}</span> menunggu</span>
            <span class="badge" style="background:var(--lilac)">Antrean boarding: <span id="wBoard">{{ $waitingBoarding }}</span> menunggu</span>
            <span class="badge" style="background:var(--pink)">Antrean grooming: <span id="wGroom">{{ $waitingGrooming }}</span> menunggu</span>
        </div>
    </div>

    @include('partials.flash')

    @foreach ($guestGrooming as $g)
        <div class="queue-hero {{ $g->status === 'done' ? 'completed' : ($g->status === 'waiting' ? 'waiting' : 'called') }} mb" style="margin-bottom:12px">
            <div class="qn" style="font-size:20px">🛁</div>
            <div>
                <b style="font-size:1.1rem">{{ $g->pet->emoji() }} {{ $g->pet->name }}</b> <span class="muted">• Grooming (tamu boarding, {{ $g->label() }})</span>
                <span class="badge" style="background:#fff">{{ $g->statusLabel() }}</span>
                <div class="small">Biaya grooming ikut tagihan boarding dan dibayar saat check-out.</div>
            </div>
        </div>
    @endforeach

    {{-- ===== Antrean aktif ===== --}}
    <div id="activeQueue" class="stack-sm mb">
        @foreach ($active as $v)
            <div class="queue-hero {{ $v->status }}" data-visit="{{ $v->id }}">
                <div class="qn" data-f="queue">{{ $v->queueLabel() }}</div>
                <div>
                    <div class="row between">
                        <div><b style="font-size:1.25rem">{{ $v->pet->emoji() }} {{ $v->pet->name }}</b> <span class="muted">• {{ $v->serviceLabel() }}</span></div>
                        <span class="badge" data-f="label" style="background:#fff">{{ $v->statusLabel() }}</span>
                    </div>
                    <div class="bold" data-f="hint" style="margin-top:4px">
                        @switch($v->status)
                            @case('waiting') Menunggu giliran — {{ $v->aheadCount() }} antrean di depanmu. @break
                            @case('called') 🔔 GILIRANMU! Silakan menuju {{ ['boarding' => 'meja Boarding', 'grooming' => 'ruang Grooming'][$v->service_type] ?? 'ruang periksa' }}{{ $v->doctor ? ' ('.$v->doctor->name.')' : '' }}. @break
                            @case('in_treatment') {{ $v->service_type === 'grooming' ? 'Sedang digrooming.' : 'Sedang diperiksa'.($v->doctor ? ' oleh '.$v->doctor->name : '').'.' }} @break
                            @case('in_boarding') Sedang menginap{{ $v->boarding?->room ? ' di kamar '.$v->boarding->room->room_number : ' — menunggu kamar disiapkan' }}. @break
                            @case('awaiting_payment') Selesai! Silakan ke kasir untuk pembayaran. @break
                        @endswitch
                    </div>
                    <div class="row mt-sm">
                        @if ($v->status === 'waiting')
                            <form method="POST" action="{{ route('owner.queue.cancel', $v) }}" data-confirm="Batalkan antrean {{ $v->queueLabel() }}?" class="inline-form">@csrf
                                <button class="btn btn-sm btn-red">Batalkan</button></form>
                        @endif
                        <a class="btn btn-sm" href="{{ route('owner.visits.show', $v) }}">Detail</a>
                        @if ($v->boarding && $v->boarding->status === 'active')
                            <a class="btn btn-sm btn-lilac" href="{{ route('owner.boarding.show', $v->boarding) }}">Pantau Boarding</a>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid g-main">
        {{-- ===== Kolom kiri ===== --}}
        <div class="stack">
            <div class="card">
                <div class="card-head pink">
                    <h2>🐾 Soulmate Pass Anabulku</h2>
                    <button class="btn btn-sm btn-yellow" data-modal-open="#petModal" type="button">+ Tambah Anabul</button>
                </div>
                @if ($pets->isEmpty())
                    <div class="empty">Belum ada anabul. Daftarkan dulu untuk mendapat Soulmate Pass & bisa ambil antrean.</div>
                @endif
                <div class="stack">
                    @foreach ($pets as $pet)
                        <div class="pass">
                            <div class="pass-top"><span>🐾 Soulmate Pass</span><span>{{ $pet->speciesLabel() }}</span></div>
                            <div class="pass-body">
                                <div class="pet-photo">@if ($pet->photoUrl())<img src="{{ $pet->photoUrl() }}" alt="{{ $pet->name }}" style="width:100%;height:100%;object-fit:cover">@else {{ $pet->emoji() }} @endif</div>
                                <div>
                                    <div class="xs upper bold muted">Nama anabul</div>
                                    <div class="bold" style="font-size:1.35rem">{{ $pet->name }}</div>
                                    <div class="small">{{ $pet->breed }} • {{ ucfirst($pet->gender) }} • {{ $pet->ageLabel() }}</div>
                                    <div class="small">Sifat: {{ ucfirst($pet->temperament) }}@if ($pet->allergy_note) • Alergi: {{ $pet->allergy_note }}@endif</div>
                                    <div class="pass-code mt-sm">{{ $pet->pass_code }}</div>
                                </div>
                                <div class="qr">{!! $pet->qrSvg() !!}</div>
                            </div>
                            <div class="row no-print" style="padding:0 14px 12px">
                                <a class="btn btn-sm" target="_blank" href="{{ route('owner.pets.pass', $pet) }}">🖨️ Cetak Pass</a>
                                <button class="btn btn-sm btn-yellow" data-modal-open="#editPet{{ $pet->id }}" type="button">Ubah</button>
                                <form method="POST" action="{{ route('owner.pets.destroy', $pet) }}" data-confirm="Hapus data {{ $pet->name }}?" class="inline-form">@csrf @method('DELETE')
                                    <button class="btn btn-sm btn-red">Hapus</button></form>
                            </div>
                        </div>

                        <dialog class="modal" id="editPet{{ $pet->id }}">
                            <div class="modal-head"><h3>Ubah {{ $pet->name }}</h3><button class="x-btn" data-modal-close type="button">✕</button></div>
                            <div class="modal-body">
                                <form method="POST" action="{{ route('owner.pets.update', $pet) }}" enctype="multipart/form-data">@csrf @method('PUT')
                                    @include('owner._pet-fields', ['p' => $pet])
                                    <button class="btn btn-blue btn-block">Simpan Perubahan</button>
                                </form>
                            </div>
                        </dialog>
                    @endforeach
                </div>
            </div>

            @if ($stay->isNotEmpty())
            <div class="card">
                <div class="card-head lilac"><h2>🛏️ Sedang Menginap</h2></div>
                @foreach ($stay as $b)
                    <div class="list-line">
                        <div><b>{{ $b->pet->name }}</b> • Kamar {{ $b->room?->room_number }}<br><span class="small muted">Hari ke-{{ $b->dayNumber() }} • rencana keluar {{ tgl($b->check_out_date) }}</span></div>
                        <a class="btn btn-sm btn-lilac" href="{{ route('owner.boarding.show', $b) }}">Lihat catatan harian</a>
                    </div>
                @endforeach
            </div>
            @endif

            <div class="card">
                <div class="card-head blue"><h2>📜 Riwayat Kunjungan</h2></div>
                @if ($history->isEmpty())
                    <div class="empty">Belum ada riwayat.</div>
                @else
                    <div class="table-wrap"><table class="table">
                        <thead><tr><th>Tanggal</th><th>Antrean</th><th>Anabul</th><th>Layanan</th><th>Status</th><th class="right">Total</th><th></th></tr></thead>
                        <tbody>
                        @foreach ($history as $v)
                            <tr>
                                <td class="nowrap">{{ tgl($v->queue_date) }}</td>
                                <td class="mono">{{ $v->queueLabel() }}</td>
                                <td>{{ $v->pet->name }}</td>
                                <td>{{ $v->serviceLabel() }}</td>
                                <td><span class="badge" style="background:{{ $v->statusColor() }}">{{ $v->statusLabel() }}</span></td>
                                <td class="num">{{ $v->invoice ? rupiah($v->invoice->grand_total) : '-' }}</td>
                                <td class="nowrap">
                                    <a class="btn btn-sm" href="{{ route('owner.visits.show', $v) }}">Detail</a>
                                    @if ($v->invoice)<a class="btn btn-sm btn-yellow" target="_blank" href="{{ route('receipt.show', $v->invoice) }}">Struk</a>@endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table></div>
                @endif
            </div>
        </div>

        {{-- ===== Kolom kanan ===== --}}
        <div class="stack">
            <div class="card">
                <div class="card-head yellow"><h2>🎟️ Ambil Antrean</h2></div>
                @if ($pets->isEmpty())
                    <div class="empty">Tambah anabul dulu.</div>
                @else
                <form method="POST" action="{{ route('owner.queue.store') }}" id="queueForm">
                    @csrf
                    <div class="field"><label>Anabul</label>
                        <select class="select" name="pet_id" required>
                            @foreach ($pets as $pet)<option value="{{ $pet->id }}" @selected(old('pet_id') == $pet->id)>{{ $pet->emoji() }} {{ $pet->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="field"><label>Mau apa?</label>
                        <div class="pay-methods" style="grid-template-columns:1fr 1fr 1fr">
                            <label class="pay-method"><input type="radio" name="service_type" value="clinic" {{ old('service_type', 'clinic') === 'clinic' ? 'checked' : '' }}><span class="big">🩺</span>Periksa</label>
                            <label class="pay-method"><input type="radio" name="service_type" value="boarding" {{ old('service_type') === 'boarding' ? 'checked' : '' }}><span class="big">🛏️</span>Titip</label>
                            <label class="pay-method"><input type="radio" name="service_type" value="grooming" {{ old('service_type') === 'grooming' ? 'checked' : '' }}><span class="big">🛁</span>Grooming</label>
                        </div>
                    </div>
                    <div id="fClinic">
                        <div class="field"><label>Keluhan</label><textarea class="textarea" name="complaint" placeholder="Contoh: batuk-batuk sejak 2 hari, nafsu makan turun">{{ old('complaint') }}</textarea></div>
                    </div>
                    <div id="fGrooming" class="hidden">
                        <div class="field"><label>Permintaan grooming</label><input class="input" name="request_note" value="{{ old('request_note') }}" placeholder="Contoh: mandi + gunting kuku, bulu kusut">
                            <span class="hint">Anabul yang sedang menginap otomatis jadi tamu boarding — biayanya ikut tagihan boarding.</span></div>
                    </div>
                    <div id="fBoarding" class="hidden">
                        <div class="fgrid">
                            <div class="field"><label>Tanggal masuk</label><input class="input" type="date" name="check_in_date" min="{{ today()->toDateString() }}" value="{{ old('check_in_date', today()->toDateString()) }}"></div>
                            <div class="field"><label>Tanggal keluar</label><input class="input" type="date" name="check_out_date" min="{{ today()->addDay()->toDateString() }}" value="{{ old('check_out_date', today()->addDays(2)->toDateString()) }}"></div>
                        </div>
                        <div class="field"><label>Makanan / kebiasaan</label><input class="input" name="diet_note" value="{{ old('diet_note') }}" placeholder="Dry food 2x, alergi seafood"></div>
                        <div class="field"><label>Barang bawaan</label><input class="input" name="belongings" value="{{ old('belongings') }}" placeholder="Selimut, mainan, pasir"></div>
                    </div>
                    <button class="btn btn-yellow btn-block btn-lg" type="submit">Ambil Nomor Antrean</button>
                </form>
                @endif
            </div>

            <div class="card">
                <div class="card-head green">
                    <h2>🔔 Notifikasi</h2>
                    <form method="POST" action="{{ route('owner.notices.read') }}" class="inline-form">@csrf<button class="btn btn-sm">Tandai dibaca</button></form>
                </div>
                <div id="noticeList">
                @forelse ($notices as $n)
                    <div class="list-line" style="{{ $n->read_at ? 'opacity:.6' : '' }}">
                        <div><b>{{ $n->title }}</b><div class="small">{{ $n->body }}</div>@if ($n->url)<a class="small bold" href="{{ $n->url }}" target="_blank">Lihat →</a>@endif</div>
                        <span class="xs muted nowrap">{{ $n->created_at->diffForHumans() }}</span>
                    </div>
                @empty
                    <div class="muted small">Belum ada notifikasi.</div>
                @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-head orange"><h2>💬 Testimoni</h2></div>
                @if ($canReview->isNotEmpty())
                    <form method="POST" action="{{ route('owner.testimonial.store') }}">
                        @csrf
                        <div class="field"><label>Untuk kunjungan</label>
                            <select class="select" name="visit_id">
                                @foreach ($canReview as $v)<option value="{{ $v->id }}">{{ tgl($v->queue_date) }} — {{ $v->pet->name }} ({{ $v->serviceLabel() }})</option>@endforeach
                            </select></div>
                        <div class="field"><label>Rating</label>
                            <div class="rating-input">
                                @for ($i = 5; $i >= 1; $i--)<input type="radio" name="rating" id="st{{ $i }}" value="{{ $i }}" {{ $i === 5 ? 'checked' : '' }}><label for="st{{ $i }}">★</label>@endfor
                            </div></div>
                        <div class="field"><label>Komentar</label><textarea class="textarea" name="comment" placeholder="Ceritakan pengalamanmu…" required>{{ old('comment') }}</textarea><span class="hint">Akan tampil di beranda website.</span></div>
                        <button class="btn btn-orange btn-block">Kirim Testimoni</button>
                    </form>
                @else
                    <div class="muted small">Setelah kunjunganmu selesai & dibayar, kamu bisa menulis testimoni di sini.</div>
                @endif
                @foreach ($myTestimonials as $t)
                    <div class="list-line"><div><span class="rating">{{ str_repeat('★', $t->rating) }}</span><div class="small">“{{ $t->comment }}”</div></div>
                        <span class="badge" style="background:{{ $t->is_published ? 'var(--green)' : '#ddd' }}">{{ $t->is_published ? 'Tampil' : 'Disembunyikan' }}</span></div>
                @endforeach
            </div>

            @if ($doctorsToday->isNotEmpty())
            <div class="card">
                <div class="card-head blue"><h2>🩺 Dokter Hari Ini</h2></div>
                @foreach ($doctorsToday as $s)
                    <div class="list-line"><b>{{ $s->user->name }}</b><span class="badge" style="background:{{ $s->color() }}">{{ $s->label() }} {{ $s->time() }}</span></div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Modal tambah anabul --}}
<dialog class="modal" id="petModal" {{ old('_form') === 'pet' && $errors->any() ? 'data-auto-open' : '' }}>
    <div class="modal-head"><h3>Daftarkan Anabul</h3><button class="x-btn" data-modal-close type="button">✕</button></div>
    <div class="modal-body">
        <form method="POST" action="{{ route('owner.pets.store') }}" enctype="multipart/form-data">
            @csrf <input type="hidden" name="_form" value="pet">
            @include('owner._pet-fields', ['p' => null])
            <button class="btn btn-pink btn-block btn-lg">Daftar & Buat Soulmate Pass</button>
        </form>
    </div>
</dialog>
@endsection

@push('scripts')
<script>
(function () {
    const $ = (s, c) => (c || document).querySelector(s);
    // Toggle form klinik/boarding
    const radios = document.querySelectorAll('input[name=service_type]');
    function toggle() {
        const v = ($('input[name=service_type]:checked') || {}).value;
        $('#fClinic')?.classList.toggle('hidden', v !== 'clinic');
        $('#fGrooming')?.classList.toggle('hidden', v !== 'grooming');
        $('#fBoarding')?.classList.toggle('hidden', v !== 'boarding');
    }
    radios.forEach(r => r.addEventListener('change', toggle)); toggle();

    // Polling status antrean + notifikasi (tiap 7 detik)
    let lastNotice = {{ (int) $notices->max('id') }};
    const labels = {};
    document.querySelectorAll('[data-visit]').forEach(el => labels[el.dataset.visit] = el.className);
    const known = new Set([...document.querySelectorAll('[data-visit]')].map(e => e.dataset.visit));
    const origTitle = document.title; let flashTimer;

    function hintFor(v) {
        switch (v.status) {
            case 'waiting': return 'Menunggu giliran — ' + v.ahead + ' antrean di depanmu.';
            case 'called': return '🔔 GILIRANMU! Silakan menuju ' + (v.service.indexOf('Boarding') >= 0 ? 'meja Boarding' : (v.service.indexOf('Grooming') >= 0 ? 'ruang Grooming' : 'ruang periksa')) + (v.doctor ? ' (' + v.doctor + ')' : '') + '.';
            case 'in_treatment': return v.service.indexOf('Grooming') >= 0 ? 'Sedang digrooming.' : 'Sedang diperiksa' + (v.doctor ? ' oleh ' + v.doctor : '') + '.';
            case 'in_boarding': return 'Sedang menginap.';
            case 'awaiting_payment': return 'Selesai! Silakan ke kasir untuk pembayaran.';
        } return '';
    }

    async function poll() {
        try {
            const r = await fetch(@json(route('owner.poll')), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
            if (!r.ok) return; const d = await r.json();
            $('#wClinic').textContent = d.waiting_clinic; $('#wBoard').textContent = d.waiting_boarding; $('#wGroom').textContent = d.waiting_grooming;

            const ids = new Set(d.visits.map(v => String(v.id)));
            if (ids.size !== known.size || [...ids].some(i => !known.has(i))) { location.reload(); return; }

            d.visits.forEach(v => {
                const el = document.querySelector('[data-visit="' + v.id + '"]'); if (!el) return;
                const wasCalled = el.classList.contains('called');
                el.className = 'queue-hero ' + v.status;
                el.querySelector('[data-f=label]').textContent = v.label;
                el.querySelector('[data-f=hint]').textContent = hintFor(v);
                if (v.status === 'called' && !wasCalled) {
                    pawBeep();
                    let on = true; clearInterval(flashTimer);
                    flashTimer = setInterval(() => { document.title = on ? '🔔 GILIRANMU DIPANGGIL!' : origTitle; on = !on; }, 900);
                    setTimeout(() => { clearInterval(flashTimer); document.title = origTitle; }, 30000);
                }
            });

            const fresh = d.notices.filter(n => n.id > lastNotice).sort((a, b) => a.id - b.id);
            fresh.forEach(n => { pawToast(n); lastNotice = Math.max(lastNotice, n.id); });
            if (fresh.length && fresh.some(n => n.type !== 'called')) pawBeep();
            if (fresh.length) {
                fetch(@json(route('owner.notices.read')), { method: 'POST', credentials: 'same-origin',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ids: fresh.map(n => n.id) }) });
            }
        } catch (e) { /* offline sementara */ }
    }
    setInterval(poll, 7000);
})();
</script>
@endpush
