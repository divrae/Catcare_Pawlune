@extends('layouts.public')
@section('title', 'Klinik Hewan, Boarding & Antrean Online')
@section('content')

<section class="hero">
    <div class="container hero-grid">
        <div>
            <span class="badge" style="background:var(--green)"><span class="dot live"></span> Buka hari ini • {{ config('catcare.clinic.hours') }}</span>
            <h1 style="margin-top:14px">Rawat <span class="hl">anabulmu</span> dengan <span class="hl b">alur yang rapi</span>.</h1>
            <p style="font-size:1.1rem;margin-top:16px;max-width:520px">Daftar pakai nomor HP, dapatkan <b>Soulmate Pass</b> untuk hewanmu, ambil nomor antrean dari rumah, dan dapat notifikasi saat gilirannya dipanggil. Periksa, titip, dan bayar — semuanya terhubung.</p>
            <div class="row mt">
                @auth
                    <a class="btn btn-lg btn-pink" href="{{ auth()->user()->homeRoute() }}">Ke Dashboard →</a>
                @else
                    <a class="btn btn-lg btn-pink" href="{{ route('register') }}">Daftar & Ambil Antrean</a>
                    <a class="btn btn-lg" href="{{ route('login') }}">Masuk</a>
                @endauth
            </div>
            <div class="row mt" style="gap:22px">
                <div><div class="bold" style="font-size:1.7rem">{{ number_format($stats['pets']) }}+</div><div class="xs upper muted bold">Anabul terdaftar</div></div>
                <div><div class="bold" style="font-size:1.7rem">{{ number_format($stats['visits']) }}+</div><div class="xs upper muted bold">Kunjungan selesai</div></div>
                <div><div class="bold" style="font-size:1.7rem">{{ $stats['rooms'] }}</div><div class="xs upper muted bold">Suite boarding</div></div>
            </div>
        </div>
        <div>
            <div class="pass" style="transform:rotate(2deg)">
                <div class="pass-top"><span>🐾 Soulmate Pass</span><span>PAWLUNE</span></div>
                <div class="pass-body">
                    <div class="pet-photo">🐱</div>
                    <div>
                        <div class="xs upper bold muted">Nama anabul</div>
                        <div class="bold" style="font-size:1.35rem">Mimi von Fluff</div>
                        <div class="small">Scottish Fold • Betina</div>
                        <div class="pass-code mt-sm">PWL-A1B2C3</div>
                    </div>
                    <div class="qr" style="display:grid;place-items:center;font-size:3.2rem">▦</div>
                </div>
            </div>
            <div class="card mt" style="transform:rotate(-1.5deg);background:var(--yellow)">
                <div class="row between"><b>Antrean kamu</b><span class="badge" style="background:var(--green)">Dipanggil!</span></div>
                <div class="mono bold" style="font-size:2rem">K-007</div>
                <div class="small">Silakan menuju ruang periksa — drh. Andi</div>
            </div>
        </div>
    </div>
</section>

<section class="sec" id="layanan">
    <div class="container">
        <h2 class="sec-title">Layanan Kami</h2>
        <div class="grid g3">
            <div class="feature" style="background:var(--blue)"><span class="big">🩺</span><h3>Periksa Dokter</h3><p class="mt-sm">Pemeriksaan umum, vaksin, tindakan, dan resep obat. Walk-in tanpa akun pun tetap dilayani dengan nomor antrean.</p></div>
            <div class="feature" style="background:var(--pink)"><span class="big">🛏️</span><h3>Boarding / Penitipan</h3><p class="mt-sm">Suite bersih dengan catatan harian makan, suhu, dan kesehatan yang bisa dipantau pemilik.</p></div>
            <div class="feature" style="background:var(--green)"><span class="big">💳</span><h3>Bayar Mudah</h3><p class="mt-sm">Tunai, QRIS, atau transfer. Struk rinci: tarif dokter, obat, dan lama menginap.</p></div>
        </div>

        @if ($tariffs->isNotEmpty())
        <div class="card mt" style="margin-top:28px">
            <div class="card-head"><h3>Contoh Tarif</h3><span class="xs mono">Harga dapat berubah</span></div>
            <div class="grid g3">
                @foreach ($tariffs as $t)
                    <div class="list-line" style="border:0"><span>{{ $t->name }}</span><b class="mono">{{ rupiah($t->price) }}</b></div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</section>

<section class="sec" id="boarding" style="background:var(--lilac);border-top:3px solid #000;border-bottom:3px solid #000">
    <div class="container grid g2" style="align-items:center">
        <div>
            <h2 class="sec-title" style="background:var(--white)">Boarding Pawlune</h2>
            <p style="font-size:1.05rem">Tiap suite punya QR sendiri. Petugas mencatat makan, suhu, dan kondisi kesehatan setiap hari — pemilik bisa melihatnya langsung di dashboard. Hewan yang butuh rawat inap juga bisa <b>dirujuk langsung oleh dokter</b>.</p>
        </div>
        <div class="grid" style="gap:12px">
            @foreach ($boardingTariffs as $t)
                <div class="card" style="padding:12px 16px"><div class="row between"><b>{{ $t->name }}</b><span class="mono bold">{{ rupiah($t->price) }} / hari</span></div></div>
            @endforeach
        </div>
    </div>
</section>

@if ($doctorsToday->isNotEmpty())
<section class="sec">
    <div class="container">
        <h2 class="sec-title" style="background:var(--green)">Dokter Bertugas Hari Ini</h2>
        <div class="grid g3">
            @foreach ($doctorsToday as $s)
                <div class="card" style="background:{{ $s->color() }}">
                    <div class="row"><span class="avatar" style="background:#fff">{{ $s->user->initials() }}</span><div><b>{{ $s->user->name }}</b><div class="small">Shift {{ $s->label() }} • {{ $s->time() }}</div></div></div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<section class="sec" id="testimoni">
    <div class="container">
        <h2 class="sec-title" style="background:var(--pink)">Kata Mereka</h2>
        @if ($testimonials->isEmpty())
            <div class="empty">Belum ada testimoni. Jadilah yang pertama setelah kunjunganmu selesai! 🐾</div>
        @else
            <div class="grid g3">
                @foreach ($testimonials as $t)
                    <div class="testi">
                        <div class="rating">{{ str_repeat('★', $t->rating) }}{{ str_repeat('☆', 5 - $t->rating) }}</div>
                        <p class="mt-sm" style="font-weight:600">“{{ $t->comment }}”</p>
                        <div class="small bold upper">— {{ \Illuminate\Support\Str::words($t->user->name, 2, '') }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endsection
