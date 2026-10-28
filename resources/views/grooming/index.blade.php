@extends('layouts.app')
@section('title', 'Grooming')
@section('page', 'Antrean Grooming')
@section('subtitle', 'Panggil → mulai → pilih layanan → selesai. Biaya otomatis ke kasir / tagihan boarding')
@section('actions')
    <button class="btn btn-pink" data-modal-open="#walkModal" type="button">+ Walk-in Grooming</button>
@endsection

@section('content')
<div id="queueBox">@include('grooming._queue')</div>

<dialog class="modal" id="walkModal" {{ $errors->has('pass_code') || $errors->has('owner_name') || $errors->has('owner_phone') || $errors->has('pet_name') || $errors->has('pet_id') ? 'data-auto-open' : '' }}>
    <div class="modal-head"><h3>Walk-in Grooming</h3><button class="x-btn" data-modal-close type="button">✕</button></div>
    <div class="modal-body">
        <p class="small muted">Hewan yang sedang menginap di boarding otomatis menjadi <b>tamu boarding</b> — biayanya masuk tagihan boarding.</p>
        <form method="POST" action="{{ route('grooming.walkin') }}">@csrf
            <div class="field"><label>Punya Soulmate Pass? (opsional)</label><input class="input mono" name="pass_code" value="{{ old('pass_code') }}" placeholder="PWL-XXXXXX" style="text-transform:uppercase"></div>
            @error('pet_id')<div class="alert alert-error">{{ $message }}</div>@enderror
            <div class="fgrid">
                <div class="field"><label>Nama pemilik</label><input class="input" name="owner_name" value="{{ old('owner_name') }}"></div>
                <div class="field"><label>Nomor HP</label><input class="input" name="owner_phone" inputmode="tel" value="{{ old('owner_phone') }}"></div>
                <div class="field"><label>Nama hewan</label><input class="input" name="pet_name" value="{{ old('pet_name') }}"></div>
                <div class="field"><label>Jenis</label><select class="select" name="species">@foreach ($species as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
                <div class="field"><label>Ras</label><input class="input" name="breed" value="{{ old('breed') }}" placeholder="Domestik"></div>
                <div class="field"><label>Jenis kelamin</label><select class="select" name="gender"><option value="jantan">Jantan</option><option value="betina">Betina</option></select></div>
                <div class="field full"><label>Permintaan / catatan</label><input class="input" name="request_note" value="{{ old('request_note') }}" placeholder="mis. bulu kusut, takut air"></div>
            </div>
            <button class="btn btn-pink btn-block btn-lg">Daftarkan & Beri Nomor Antrean</button>
        </form>
    </div>
</dialog>
@endsection

@push('scripts')
<script>
setInterval(async () => {
    if (document.querySelector('dialog[open]')) return;
    try {
        const r = await fetch(@json(route('grooming.queue')), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
        if (r.ok) document.getElementById('queueBox').innerHTML = await r.text();
    } catch (e) {}
}, 15000);
</script>
@endpush
