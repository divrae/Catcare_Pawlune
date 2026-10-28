@extends('layouts.app')
@section('title', 'Dashboard Admin')
@section('page', 'Dashboard Admin')
@section('subtitle', 'Ringkasan operasional hari ini • ' . tgl(today(), 'l, d F Y'))
@section('actions')
    <div class="row">@foreach ([7, 14, 30] as $d)<a class="tab {{ $days === $d ? 'active' : '' }}" href="?days={{ $d }}">{{ $d }} hari</a>@endforeach</div>
@endsection

@section('content')
@php($k = $kpi)
<div class="grid g4 mb">
    <div class="stat c-green"><span class="lbl">Pemasukan hari ini</span><div class="val" style="font-size:1.45rem">{{ rupiah($k['income_today']) }}</div><div class="sub">{{ $k['tx_today'] }} transaksi • bulan ini {{ rupiah($k['income_month']) }}</div></div>
    <div class="stat c-blue"><span class="lbl">Ke dokter hari ini</span><div class="val">{{ $k['clinic_today'] }}</div><div class="sub">{{ $k['waiting_clinic'] }} menunggu</div></div>
    <div class="stat c-lilac"><span class="lbl">Ke boarding hari ini</span><div class="val">{{ $k['boarding_today'] }}</div><div class="sub">{{ $k['rooms_occupied'] }} menginap • {{ $k['waiting_boarding'] }} antre</div></div>
    <div class="stat c-pink"><span class="lbl">Hewan dilayani</span><div class="val">{{ $k['pets_served'] }}</div><div class="sub">{{ $k['called'] }} sedang dipanggil</div></div>
</div>
<div class="grid g4 mb">
    <div class="stat"><span class="lbl">Pelanggan</span><div class="val">{{ number_format($k['customers']) }}</div><div class="sub">+{{ $k['customers_new_today'] }} baru hari ini</div></div>
    <div class="stat c-orange"><span class="lbl">Obat keluar hari ini</span><div class="val">{{ $k['med_out_today'] }}</div><div class="sub">unit terjual lewat resep</div></div>
    <div class="stat c-pink"><span class="lbl">Stok menipis / ED</span><div class="val">{{ $k['med_low'] }} <span style="font-size:1rem">/ {{ $k['med_expired'] }}</span></div><div class="sub"><a href="{{ route('admin.medicines.index', ['filter' => 'low']) }}" class="bold">Lihat obat →</a></div></div>
    <div class="stat c-blue"><span class="lbl">Okupansi boarding</span><div class="val">{{ $k['occupancy'] }}%</div><div class="bar mt-sm"><i style="width:{{ $k['occupancy'] }}%"></i></div></div>
</div>

<div class="grid g3 mb">
    <div class="stat c-pink"><span class="lbl">Grooming hari ini</span><div class="val">{{ $k['grooming_today'] }}</div><div class="sub">{{ $k['grooming_guest_today'] }} tamu boarding • <a href="{{ route('grooming.index') }}" class="bold">buka modul →</a></div></div>
    <div class="stat c-yellow"><span class="lbl">Grooming berjalan / antre</span><div class="val">{{ $k['grooming_open'] }}</div><div class="sub">menunggu, dipanggil, atau sedang dikerjakan</div></div>
    <div class="stat c-green"><span class="lbl">Pemasukan grooming hari ini</span><div class="val" style="font-size:1.45rem">{{ rupiah($k['grooming_income_today']) }}</div><div class="sub">sudah dibayar di kasir</div></div>
</div>

<div class="grid g2 mb">
    <div class="card"><div class="card-head green"><h3>Pemasukan per hari</h3></div><div class="chart-box"><canvas id="cIncome"></canvas></div></div>
    <div class="card"><div class="card-head blue"><h3>Kunjungan: dokter, boarding, grooming</h3></div><div class="chart-box"><canvas id="cVisits"></canvas></div></div>
</div>
<div class="grid g3 mb">
    <div class="card"><div class="card-head orange"><h3>Obat berkurang per hari</h3></div><div class="chart-box sm"><canvas id="cMed"></canvas></div></div>
    <div class="card"><div class="card-head pink"><h3>Pelanggan baru</h3></div><div class="chart-box sm"><canvas id="cCust"></canvas></div></div>
    <div class="card"><div class="card-head lilac"><h3>Sumber pemasukan (30 hari)</h3></div><div class="chart-box sm"><canvas id="cMix"></canvas></div></div>
</div>

<div class="grid g3">
    <div class="card">
        <div class="card-head yellow"><h3>Antrean saat ini ({{ $queueNow->count() }})</h3></div>
        @forelse ($queueNow as $q)
            <div class="list-line"><div><b class="mono">{{ $q->queueLabel() }}</b> {{ $q->pet->name }}<div class="xs muted">{{ $q->pet->owner->name }}</div></div><span class="badge" style="background:{{ $q->statusColor() }}">{{ $q->statusLabel() }}</span></div>
        @empty<div class="muted small">Tidak ada antrean aktif.</div>@endforelse
    </div>
    <div class="card">
        <div class="card-head orange"><h3>Obat terlaris (keluar)</h3></div>
        <div class="chart-box sm"><canvas id="cTop"></canvas></div>
    </div>
    <div class="card">
        <div class="card-head pink"><h3>Perlu perhatian: stok / ED</h3></div>
        @forelse ($lowMedicines as $m)
            <div class="list-line"><div><b>{{ $m->name }}</b> <span class="mono xs">{{ $m->code }}</span><div class="xs muted">ED {{ $m->expired_date ? tgl($m->expired_date) : '-' }}</div></div>
                <span class="badge" style="background:{{ $m->isLow() ? 'var(--red)' : 'var(--yellow)' }}">stok {{ $m->stock }}</span></div>
        @empty<div class="muted small">Semua stok aman ✔</div>@endforelse
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/chart.umd.js') }}"></script>
<script>
(function () {
    const C = @json($charts);
    const col = { blue: '#A2D2FF', pink: '#FFC8DD', yellow: '#FEF08A', green: '#B9F6CA', orange: '#FFD6A5', lilac: '#CDB4DB', red: '#FF8FA3' };
    Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif"; Chart.defaults.font.weight = '600'; Chart.defaults.color = '#0b0b0f';
    Chart.defaults.maintainAspectRatio = false;
    const grid = { color: 'rgba(0,0,0,.12)' };
    const ds = (label, data, bg) => ({ label, data, backgroundColor: bg, borderColor: '#0b0b0f', borderWidth: 2 });
    const rp = v => 'Rp ' + Math.round(v).toLocaleString('id-ID');
    const scales = (fmt) => ({ x: { grid: { display: false } }, y: { beginAtZero: true, grid, ticks: fmt ? { callback: v => v >= 1000 ? (v / 1000) + 'rb' : v } : { precision: 0 } } });

    new Chart('cIncome', { type: 'bar', data: { labels: C.labels, datasets: [ds('Pemasukan', C.income, col.green)] },
        options: { plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => rp(c.raw) } } }, scales: scales(true) } });
    new Chart('cVisits', { type: 'bar', data: { labels: C.labels, datasets: [ds('Dokter', C.clinic, col.blue), ds('Boarding', C.boarding, col.lilac), ds('Grooming', C.grooming, col.pink)] },
        options: { scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, beginAtZero: true, grid, ticks: { precision: 0 } } } } });
    new Chart('cMed', { type: 'line', data: { labels: C.labels, datasets: [{ label: 'Unit keluar', data: C.medOut, borderColor: '#0b0b0f', backgroundColor: col.orange, borderWidth: 3, fill: true, tension: .25, pointBackgroundColor: '#fff', pointBorderWidth: 2 }] },
        options: { plugins: { legend: { display: false } }, scales: scales(false) } });
    new Chart('cCust', { type: 'bar', data: { labels: C.labels, datasets: [ds('Pelanggan baru', C.newCustomers, col.pink)] }, options: { plugins: { legend: { display: false } }, scales: scales(false) } });
    const mix = Object.entries(C.revenueMix);
    new Chart('cMix', { type: 'doughnut', data: { labels: mix.map(e => e[0]), datasets: [{ data: mix.map(e => e[1]), backgroundColor: [col.blue, col.pink, col.lilac, col.green, col.yellow], borderColor: '#0b0b0f', borderWidth: 3 }] },
        options: { plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: c => c.label + ': ' + rp(c.raw) } } } } });
    new Chart('cTop', { type: 'bar', data: { labels: C.topMeds.map(m => m.name), datasets: [ds('Unit', C.topMeds.map(m => m.qty), col.orange)] },
        options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, grid, ticks: { precision: 0 } }, y: { grid: { display: false } } } } });
})();
</script>
@endpush
