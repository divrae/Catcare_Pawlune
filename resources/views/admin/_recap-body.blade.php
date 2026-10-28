{{-- Dipakai layar, cetak, dan PDF (hanya tabel & blok sederhana agar kompatibel dompdf) --}}
@php
    $show = fn ($s) => $section === 'all' || $section === $s;
    $a = $data['admin']; $d = $data['doctor']; $b = $data['boarding']; $g = $data['grooming']; $p = $data['pos'];
@endphp

@if ($show('pos'))
<h2 class="rh">🧾 REKAP KASIR (POS)</h2>
<table class="kt"><tr>
    <td><span class="kl">Transaksi</span><b>{{ $p['count'] }}</b></td>
    <td><span class="kl">Total pemasukan</span><b>{{ rupiah($p['total']) }}</b></td>
    <td><span class="kl">Rata-rata / transaksi</span><b>{{ rupiah($p['avg']) }}</b></td>
    <td><span class="kl">Total diskon</span><b>{{ rupiah($p['discount']) }}</b></td>
</tr></table>
<table class="rt two"><tr><td>
    <h3>Per metode bayar</h3>
    <table class="rt"><thead><tr><th>Metode</th><th class="n">Trx</th><th class="n">Total</th></tr></thead><tbody>
    @foreach ($p['by_method'] as $m)<tr><td>{{ $m['label'] }}</td><td class="n">{{ $m['count'] }}</td><td class="n">{{ rupiah($m['total']) }}</td></tr>@endforeach</tbody></table>
</td><td>
    <h3>Per departemen</h3>
    <table class="rt"><thead><tr><th>Departemen</th><th class="n">Total</th></tr></thead><tbody>
    @foreach ($p['by_dept'] as $k => $v)<tr><td>{{ $k }}</td><td class="n">{{ rupiah($v) }}</td></tr>@endforeach</tbody></table>
</td></tr></table>
<h3>Per kasir</h3>
<table class="rt"><thead><tr><th>Kasir</th><th class="n">Trx</th><th class="n">Total</th></tr></thead><tbody>
@forelse ($p['by_cashier'] as $c)<tr><td>{{ $c['name'] }}</td><td class="n">{{ $c['count'] }}</td><td class="n">{{ rupiah($c['total']) }}</td></tr>@empty<tr><td colspan="3">-</td></tr>@endforelse</tbody></table>
<h3>Item terlaris</h3>
<table class="rt"><thead><tr><th>Item</th><th class="n">Qty</th><th class="n">Nilai</th></tr></thead><tbody>
@forelse ($p['top_items'] as $i)<tr><td>{{ $i['name'] }}</td><td class="n">{{ $i['qty'] }}</td><td class="n">{{ rupiah($i['total']) }}</td></tr>@empty<tr><td colspan="3">-</td></tr>@endforelse</tbody></table>
<h3>Obat keluar (stok berkurang)</h3>
<table class="rt"><thead><tr><th>Kode</th><th>Obat</th><th class="n">Jumlah</th></tr></thead><tbody>
@forelse ($p['medicines_out'] as $m)<tr><td>{{ $m['code'] }}</td><td>{{ $m['name'] }}</td><td class="n">{{ $m['qty'] }} {{ $m['unit'] }}</td></tr>@empty<tr><td colspan="3">-</td></tr>@endforelse</tbody></table>
<h3>Daftar transaksi</h3>
<table class="rt"><thead><tr><th>Waktu</th><th>Invoice</th><th>Pelanggan</th><th>Metode</th><th class="n">Total</th></tr></thead><tbody>
@forelse ($p['invoices'] as $i)<tr><td>{{ $i->paid_at->format('d/m H:i') }}</td><td>{{ $i->invoice_number }}</td><td>{{ $i->visit->pet->name }} ({{ $i->visit->pet->owner->name }})</td><td>{{ $i->methodLabel() }}</td><td class="n">{{ rupiah($i->grand_total) }}</td></tr>@empty<tr><td colspan="5">Tidak ada transaksi.</td></tr>@endforelse</tbody></table>
@endif

@if ($show('doctor'))
<h2 class="rh">🩺 REKAP DOKTER</h2>
<table class="kt"><tr>
    <td><span class="kl">Pemeriksaan</span><b>{{ $d['total'] }}</b></td><td><span class="kl">Item resep</span><b>{{ $d['prescriptions'] }}</b></td><td><span class="kl">Rujukan boarding</span><b>{{ $d['referrals'] }}</b></td>
</tr></table>
<h3>Per dokter</h3>
<table class="rt"><thead><tr><th>Dokter</th><th class="n">Pemeriksaan</th><th class="n">Rujukan</th><th class="n">Item resep</th></tr></thead><tbody>
@forelse ($d['per_doctor'] as $x)<tr><td>{{ $x['name'] }}</td><td class="n">{{ $x['exams'] }}</td><td class="n">{{ $x['referrals'] }}</td><td class="n">{{ $x['rx'] }}</td></tr>@empty<tr><td colspan="4">-</td></tr>@endforelse</tbody></table>
<h3>Diagnosa terbanyak</h3>
<table class="rt"><tbody>@forelse ($d['top_diagnosis'] as $dx => $n)<tr><td>{{ ucfirst($dx) }}</td><td class="n">{{ $n }}x</td></tr>@empty<tr><td>-</td></tr>@endforelse</tbody></table>
<h3>Rekam medis</h3>
<table class="rt"><thead><tr><th>Tanggal</th><th>Pasien</th><th>Diagnosa</th><th>Dokter</th></tr></thead><tbody>
@forelse ($d['records'] as $r)<tr><td>{{ $r->created_at->format('d/m') }}</td><td>{{ $r->visit->pet->name }} ({{ $r->visit->pet->owner->name }})</td><td>{{ \Illuminate\Support\Str::limit($r->diagnosis, 70) }}</td><td>{{ $r->doctor?->name }}</td></tr>@empty<tr><td colspan="4">-</td></tr>@endforelse</tbody></table>
@endif

@if ($show('boarding'))
<h2 class="rh">🛏️ REKAP BOARDING</h2>
<table class="kt"><tr>
    <td><span class="kl">Check-in</span><b>{{ $b['started'] }}</b></td><td><span class="kl">Check-out</span><b>{{ $b['finished'] }}</b></td><td><span class="kl">Sedang menginap</span><b>{{ $b['active_now'] }}</b></td>
    <td><span class="kl">Rata² lama (hari)</span><b>{{ $b['avg_days'] }}</b></td><td><span class="kl">Pendapatan</span><b>{{ rupiah($b['revenue']) }}</b></td>
</tr></table>
<p>Catatan harian: <b>{{ $b['log_count'] }}</b> • Perlu perhatian/sakit: <b>{{ $b['health_alerts'] }}</b> • Tidak makan: <b>{{ $b['no_meal'] }}</b> • Rujukan dokter: <b>{{ $b['referrals'] }}</b></p>
<table class="rt"><thead><tr><th>Suite</th><th>Hewan</th><th>Pemilik</th><th>Masuk</th><th>Keluar</th><th class="n">Hari</th><th>Status</th><th class="n">Biaya</th></tr></thead><tbody>
@forelse ($b['stays'] as $s)<tr><td>#{{ $s->room?->room_number }}</td><td>{{ $s->pet->name }}</td><td>{{ $s->pet->owner->name }}</td><td>{{ tgl($s->check_in_date, 'd/m') }}</td><td>{{ tgl($s->check_out_date, 'd/m') }}</td><td class="n">{{ $s->billedDays() }}</td><td>{{ $s->statusLabel() }}</td><td class="n">{{ rupiah($s->total()) }}</td></tr>@empty<tr><td colspan="8">-</td></tr>@endforelse</tbody></table>
@endif

@if ($show('grooming'))
<h2 class="rh">🛁 REKAP GROOMING</h2>
<table class="kt"><tr>
    <td><span class="kl">Selesai</span><b>{{ $g['total'] }}</b></td><td><span class="kl">Antrean sendiri</span><b>{{ $g['standalone'] }}</b></td><td><span class="kl">Tamu boarding</span><b>{{ $g['guest'] }}</b></td>
    <td><span class="kl">Dibatalkan</span><b>{{ $g['cancelled'] }}</b></td><td><span class="kl">Pendapatan (sudah dibayar)</span><b>{{ rupiah($g['revenue']) }}</b></td>
</tr></table>
<p>Nilai pekerjaan selesai: <b>{{ rupiah($g['worked']) }}</b> • Masih berjalan/antre: <b>{{ $g['open_now'] }}</b></p>
<h3>Per groomer</h3>
<table class="rt"><thead><tr><th>Groomer</th><th class="n">Selesai</th><th class="n">Nilai</th></tr></thead><tbody>
@forelse ($g['per_groomer'] as $x)<tr><td>{{ $x['name'] }}</td><td class="n">{{ $x['count'] }}</td><td class="n">{{ rupiah($x['total']) }}</td></tr>@empty<tr><td colspan="3">-</td></tr>@endforelse</tbody></table>
<h3>Layanan terlaris</h3>
<table class="rt"><thead><tr><th>Layanan</th><th class="n">Qty</th><th class="n">Nilai</th></tr></thead><tbody>
@forelse ($g['top_services'] as $x)<tr><td>{{ $x['name'] }}</td><td class="n">{{ $x['qty'] }}</td><td class="n">{{ rupiah($x['total']) }}</td></tr>@empty<tr><td colspan="3">-</td></tr>@endforelse</tbody></table>
<h3>Daftar grooming</h3>
<table class="rt"><thead><tr><th>Waktu</th><th>Hewan</th><th>Pemilik</th><th>Jalur</th><th>Layanan</th><th class="n">Biaya</th></tr></thead><tbody>
@forelse ($g['orders'] as $o)<tr><td>{{ $o->finished_at->format('d/m H:i') }}</td><td>{{ $o->pet->name }}</td><td>{{ $o->pet->owner->name }}</td><td>{{ $o->isGuest() ? 'Tamu '.$o->label() : 'Antrean' }}</td><td>{{ $o->services->pluck('name')->join(', ') }}</td><td class="n">{{ rupiah($o->total()) }}</td></tr>@empty<tr><td colspan="6">-</td></tr>@endforelse</tbody></table>
@endif

@if ($show('admin'))
<h2 class="rh">🛠️ REKAP ADMIN</h2>
<table class="kt"><tr>
    <td><span class="kl">Staff aktif</span><b>{{ $a['staff_by_role']->sum() }}</b></td><td><span class="kl">Staff baru</span><b>{{ $a['new_staff'] }}</b></td><td><span class="kl">Pelanggan baru</span><b>{{ $a['new_owners'] }}</b></td>
    <td><span class="kl">Antrean online</span><b>{{ $a['online'] }}</b></td><td><span class="kl">Antrean walk-in</span><b>{{ $a['walk_in'] }}</b></td>
</tr></table>
<p>Komposisi staff: @foreach ($a['staff_by_role'] as $role => $n)<b>{{ config('catcare.roles.'.$role) }}</b> {{ $n }} &nbsp;@endforeach</p>
<h3>Jejak aktivitas</h3>
<table class="rt"><thead><tr><th>Waktu</th><th>Pengguna</th><th>Aktivitas</th></tr></thead><tbody>
@forelse ($a['logs'] as $l)<tr><td>{{ $l->created_at->format('d/m H:i') }}</td><td>{{ $l->user?->name ?? '-' }}</td><td>{{ $l->description }}</td></tr>@empty<tr><td colspan="3">-</td></tr>@endforelse</tbody></table>
@endif
