@extends('layouts.app')
@section('title', 'Pemeriksaan')
@section('page', 'Pemeriksaan ' . $visit->pet->name)
@section('subtitle', $visit->queueLabel() . ' • ' . $visit->pet->owner->name)
@section('actions')<a class="btn" href="{{ route('doctor.dashboard') }}">← Antrean</a>@endsection

@section('content')
@php
    $pet = $visit->pet;
    $oldServices = old('services', $visit->services->where('added_by_role', 'doctor')->map(fn ($s) => ['tariff_id' => $s->tariff_id, 'qty' => $s->qty])->values()->all());
    if (empty($oldServices)) {
        $default = $tariffs->firstWhere('name', 'Pemeriksaan Umum');
        $oldServices = [['tariff_id' => $default?->id, 'qty' => 1]];
    }
    $oldRx = old('prescriptions', $visit->prescriptions->map(fn ($p) => ['medicine_id' => $p->medicine_id, 'qty' => $p->qty, 'dosage' => $p->dosage, 'instruction' => $p->instruction])->values()->all());
    $hasBoarding = $visit->boarding && in_array($visit->boarding->status, ['reserved', 'active', 'finished']);
@endphp

@if ($locked)<div class="flash warn">🔒 Kunjungan sudah dibayar — rekam medis hanya bisa dilihat.</div>@endif

<div class="grid g-main">
<form method="POST" action="{{ route('doctor.exam.save', $visit) }}" class="stack" id="examForm">
    @csrf @method('PUT')
    <fieldset @disabled($locked) style="border:0;padding:0;margin:0;min-width:0" class="stack">
    <div class="card">
        <div class="card-head blue"><h2>1. Pemeriksaan Klinis</h2></div>
        <div class="field"><label>Keluhan pemilik</label><div class="card flat" style="padding:10px;background:var(--yellow)">{{ $visit->complaint ?: '-' }}</div></div>
        <div class="fgrid">
            <div class="field"><label>Berat (kg)</label><input class="input" type="number" step="0.01" name="weight_kg" value="{{ old('weight_kg', $record?->weight_kg) }}"></div>
            <div class="field"><label>Suhu (°C)</label><input class="input" type="number" step="0.1" name="temperature_c" value="{{ old('temperature_c', $record?->temperature_c) }}"></div>
            <div class="field full"><label>Anamnesis / temuan</label><textarea class="textarea" name="anamnesis">{{ old('anamnesis', $record?->anamnesis) }}</textarea></div>
            <div class="field full"><label>Diagnosa *</label><textarea class="textarea" name="diagnosis" required>{{ old('diagnosis', $record?->diagnosis) }}</textarea></div>
            <div class="field full"><label>Catatan tindakan</label><textarea class="textarea" name="treatment_note" style="min-height:60px">{{ old('treatment_note', $record?->treatment_note) }}</textarea></div>
            <div class="field"><label>Kontrol ulang</label><input class="input" type="date" name="follow_up_date" min="{{ today()->toDateString() }}" value="{{ old('follow_up_date', $record?->follow_up_date?->toDateString()) }}"></div>
        </div>
    </div>

    <div class="card">
        <div class="card-head orange"><h2>2. Tarif Pemeriksaan & Tindakan</h2><button type="button" class="btn btn-sm" data-add-row="#tplSvc" data-target="#svcBox">+ Tambah</button></div>
        <div id="svcBox">
            @foreach ($oldServices as $i => $s)
                <div class="svc-row" data-row>
                    <select class="select sm svc-sel" name="services[{{ $i }}][tariff_id]">
                        <option value="">— pilih tarif —</option>
                        @foreach ($tariffs->groupBy('category') as $cat => $list)
                            <optgroup label="{{ config('catcare.tariff_categories.'.$cat, $cat) }}">
                                @foreach ($list as $t)<option value="{{ $t->id }}" data-price="{{ $t->price }}" @selected(($s['tariff_id'] ?? null) == $t->id)>{{ $t->name }} — {{ rupiah($t->price) }}</option>@endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <input class="input sm svc-qty" type="number" min="1" name="services[{{ $i }}][qty]" value="{{ $s['qty'] ?? 1 }}">
                    <button type="button" class="btn btn-sm btn-red" data-remove-row>✕</button>
                </div>
            @endforeach
        </div>
        <div class="hint">Harga diambil dari Tarif Layanan yang diatur admin.</div>
    </div>

    <div class="card">
        <div class="card-head pink"><h2>3. Resep Obat</h2><button type="button" class="btn btn-sm" data-add-row="#tplRx" data-target="#rxBox">+ Tambah obat</button></div>
        <div id="rxBox">
            @foreach ($oldRx as $i => $r)
                <div class="rx-row" data-row>
                    <select class="select sm rx-sel" name="prescriptions[{{ $i }}][medicine_id]">
                        <option value="">— pilih obat —</option>
                        @foreach ($medicines->groupBy('category') as $cat => $list)
                            <optgroup label="{{ $cat }}">
                                @foreach ($list as $m)<option value="{{ $m->id }}" data-price="{{ $m->price }}" data-stock="{{ $m->stock }}" data-unit="{{ $m->unit }}" @selected(($r['medicine_id'] ?? null) == $m->id)>{{ $m->name }} — {{ rupiah($m->price) }}/{{ $m->unit }} (stok {{ $m->stock }})</option>@endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <input class="input sm rx-qty" type="number" min="1" name="prescriptions[{{ $i }}][qty]" value="{{ $r['qty'] ?? 1 }}" title="Jumlah">
                    <input class="input sm" name="prescriptions[{{ $i }}][dosage]" value="{{ $r['dosage'] ?? '' }}" placeholder="Dosis (2x1)">
                    <input class="input sm" name="prescriptions[{{ $i }}][instruction]" value="{{ $r['instruction'] ?? '' }}" placeholder="Aturan pakai">
                    <button type="button" class="btn btn-sm btn-red" data-remove-row>✕</button>
                </div>
            @endforeach
        </div>
        <div class="hint">Hanya obat aktif, belum kedaluwarsa, dan stok tersedia yang muncul. Stok berkurang saat pembayaran di kasir.</div>
    </div>

    <div class="card">
        <div class="card-head lilac"><h2>4. Rujuk ke Boarding (rawat inap)</h2></div>
        @if ($hasBoarding && $visit->boarding->status !== 'reserved')
            <div class="flash warn">Hewan sudah {{ $visit->boarding->status === 'active' ? 'menginap di kamar '.$visit->boarding->room?->room_number : 'selesai menginap' }}.</div>
            <input type="hidden" name="refer_boarding" value="{{ $visit->boarding->status === 'active' ? 1 : 0 }}">
        @else
        <label class="check mb"><input type="checkbox" name="refer_boarding" value="1" id="referChk" @checked(old('refer_boarding', $record?->referred_to_boarding && $hasBoarding))> Rujuk menginap di Boarding (kondisi parah / perlu observasi)</label>
        <div id="referBox" class="hidden">
            <div class="fgrid">
                <div class="field"><label>Perkiraan sampai tanggal</label><input class="input" type="date" name="boarding_until" min="{{ today()->addDay()->toDateString() }}" value="{{ old('boarding_until', $visit->boarding?->check_out_date?->toDateString() ?? today()->addDays(2)->toDateString()) }}"></div>
                <div class="field"><label>Kamar kosong saat ini</label><div class="badge" style="background:var(--green)">{{ max(0, $freeRooms) }} suite</div></div>
                <div class="field full"><label>Catatan rujukan untuk petugas boarding</label><textarea class="textarea" name="referral_note" style="min-height:60px" placeholder="Observasi infus, cek suhu tiap 4 jam, puasa dulu…">{{ old('referral_note', $visit->boarding?->referral_note) }}</textarea></div>
            </div>
            <div class="hint">Biaya menginap = lama menginap × tarif kamar, otomatis masuk tagihan setelah check-out.</div>
        </div>
        @endif
    </div>

    @unless ($locked)
    <div class="card" style="background:var(--yellow)">
        <div class="row between">
            <div><div class="xs upper bold">Estimasi tagihan dokter + obat</div><div class="mono bold" style="font-size:1.6rem" id="estTotal">Rp 0</div></div>
            <button class="btn btn-green btn-lg" type="submit">✔ Selesai Pemeriksaan</button>
        </div>
    </div>
    @endunless
    </fieldset>
</form>

<div class="stack">
    <div class="card">
        <div class="card-head"><h2>{{ $pet->emoji() }} Data Pasien</h2><span class="pass-code">{{ $pet->pass_code }}</span></div>
        <dl class="kv">
            <dt>Nama</dt><dd>{{ $pet->name }}</dd>
            <dt>Jenis</dt><dd>{{ $pet->speciesLabel() }} • {{ $pet->breed }}</dd>
            <dt>Kelamin / Umur</dt><dd>{{ ucfirst($pet->gender) }} • {{ $pet->ageLabel() }}</dd>
            <dt>Sifat</dt><dd>{{ ucfirst($pet->temperament) }}</dd>
            <dt>Alergi</dt><dd>{{ $pet->allergy_note ?: '-' }}</dd>
            <dt>Pemilik</dt><dd>{{ $pet->owner->name }}<br><span class="muted">{{ $pet->owner->phone }}</span></dd>
        </dl>
    </div>
    <div class="card">
        <div class="card-head green"><h2>📁 Riwayat Sebelumnya</h2></div>
        @forelse ($history as $h)
            <div class="list-line" style="display:block">
                <b>{{ tgl($h->created_at) }}</b> — {{ $h->diagnosis }}
                <div class="xs muted">{{ $h->doctor?->name }}@if ($h->prescriptions->isNotEmpty()) • Obat: {{ $h->prescriptions->pluck('medicine_name')->join(', ') }}@endif</div>
            </div>
        @empty
            <div class="muted small">Belum ada riwayat medis.</div>
        @endforelse
    </div>
</div>
</div>

<template id="tplSvc">
    <div class="svc-row" data-row>
        <select class="select sm svc-sel" name="services[__i__][tariff_id]">
            <option value="">— pilih tarif —</option>
            @foreach ($tariffs->groupBy('category') as $cat => $list)
                <optgroup label="{{ config('catcare.tariff_categories.'.$cat, $cat) }}">
                    @foreach ($list as $t)<option value="{{ $t->id }}" data-price="{{ $t->price }}">{{ $t->name }} — {{ rupiah($t->price) }}</option>@endforeach
                </optgroup>
            @endforeach
        </select>
        <input class="input sm svc-qty" type="number" min="1" name="services[__i__][qty]" value="1">
        <button type="button" class="btn btn-sm btn-red" data-remove-row>✕</button>
    </div>
</template>
<template id="tplRx">
    <div class="rx-row" data-row>
        <select class="select sm rx-sel" name="prescriptions[__i__][medicine_id]">
            <option value="">— pilih obat —</option>
            @foreach ($medicines->groupBy('category') as $cat => $list)
                <optgroup label="{{ $cat }}">
                    @foreach ($list as $m)<option value="{{ $m->id }}" data-price="{{ $m->price }}" data-stock="{{ $m->stock }}" data-unit="{{ $m->unit }}">{{ $m->name }} — {{ rupiah($m->price) }}/{{ $m->unit }} (stok {{ $m->stock }})</option>@endforeach
                </optgroup>
            @endforeach
        </select>
        <input class="input sm rx-qty" type="number" min="1" name="prescriptions[__i__][qty]" value="1" title="Jumlah">
        <input class="input sm" name="prescriptions[__i__][dosage]" placeholder="Dosis (2x1)">
        <input class="input sm" name="prescriptions[__i__][instruction]" placeholder="Aturan pakai">
        <button type="button" class="btn btn-sm btn-red" data-remove-row>✕</button>
    </div>
</template>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('examForm');
    const est = document.getElementById('estTotal');
    function recalc() {
        let t = 0;
        form.querySelectorAll('.svc-row').forEach(r => { const o = r.querySelector('.svc-sel').selectedOptions[0]; if (o && o.dataset.price) t += o.dataset.price * (r.querySelector('.svc-qty').value || 1); });
        form.querySelectorAll('.rx-row').forEach(r => {
            const o = r.querySelector('.rx-sel').selectedOptions[0]; const q = parseInt(r.querySelector('.rx-qty').value || 1);
            if (o && o.dataset.price) { t += o.dataset.price * q; r.querySelector('.rx-qty').max = o.dataset.stock; r.style.background = q > parseInt(o.dataset.stock) ? '#ffd0d8' : ''; }
        });
        if (est) est.textContent = fmtRp(t);
    }
    form.addEventListener('input', recalc); form.addEventListener('change', recalc); form.addEventListener('rows:changed', recalc); recalc();

    const chk = document.getElementById('referChk'), box = document.getElementById('referBox');
    if (chk) { const f = () => box.classList.toggle('hidden', !chk.checked); chk.addEventListener('change', f); f(); }
})();
</script>
@endpush
