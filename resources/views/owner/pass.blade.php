@extends('layouts.print')
@section('title', 'Soulmate Pass ' . $pet->name)
@section('back', route('owner.dashboard'))
@section('content')
<div style="max-width:520px;margin:0 auto">
    <div class="pass">
        <div class="pass-top"><span>🐾 Soulmate Pass</span><span>PAWLUNE CATCARE</span></div>
        <div class="pass-body">
            <div class="pet-photo">@if ($pet->photoUrl())<img src="{{ $pet->photoUrl() }}" alt="" style="width:100%;height:100%;object-fit:cover">@else {{ $pet->emoji() }} @endif</div>
            <div>
                <div class="xs upper bold muted">Nama anabul</div>
                <div class="bold" style="font-size:1.5rem">{{ $pet->name }}</div>
                <div class="small">{{ $pet->speciesLabel() }} • {{ $pet->breed }} • {{ ucfirst($pet->gender) }}</div>
                <div class="small">Pemilik: <b>{{ $pet->owner->name }}</b> • {{ $pet->owner->phone }}</div>
                <div class="pass-code mt-sm">{{ $pet->pass_code }}</div>
            </div>
            <div class="qr">{!! $pet->qrSvg(160) !!}</div>
        </div>
    </div>
    <p class="center small muted mt">Tunjukkan QR / kode ini ke petugas untuk pendaftaran cepat saat datang langsung.</p>
</div>
@endsection
