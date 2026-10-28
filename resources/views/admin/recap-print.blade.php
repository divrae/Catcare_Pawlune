<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><title>Rekap Pawlune {{ $from->format('d-m-Y') }} s/d {{ $to->format('d-m-Y') }}</title>
<style>
    body{font-family:DejaVu Sans,Arial,sans-serif;font-size:11px;color:#000;margin:{{ $pdf ? '0' : '24px' }}}
    h1{font-size:18px;margin:0}.sub{color:#444;margin-bottom:8px}
    .rh{background:#FEF08A;border:2px solid #000;padding:4px 10px;margin:18px 0 8px;font-size:13px}
    h3{font-size:11.5px;margin:10px 0 4px}
    table.rt{width:100%;border-collapse:collapse;margin-bottom:10px}table.rt th{background:#222;color:#fff;text-align:left;padding:4px 6px;font-size:9.5px}
    table.rt td{border-top:1px solid #000;padding:4px 6px}.n{text-align:right}
    table.rt.two>tbody>tr>td{border:0;vertical-align:top;width:50%;padding:0 6px 0 0}
    table.kt{width:100%;border-collapse:separate;border-spacing:6px;margin:0 -6px 6px}table.kt td{border:2px solid #000;padding:6px}
    .kl{display:block;font-size:8.5px;text-transform:uppercase;color:#555}.kt b{font-size:13px}
    .head{border-bottom:3px solid #000;padding-bottom:8px;margin-bottom:6px}
    .btn{display:inline-block;padding:8px 14px;border:3px solid #000;background:#A2D2FF;font-weight:bold;cursor:pointer;margin-bottom:12px}
    @media print{.noprint{display:none}}
</style></head>
<body>
@unless ($pdf)<div class="noprint"><a class="btn" style="background:#fff;text-decoration:none;color:#000" href="{{ route('admin.recap.index', ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'section' => $section]) }}">← Kembali</a> <button class="btn" onclick="window.print()">🖨️ Cetak</button></div>@endunless
<div class="head"><h1>REKAP OPERASIONAL — {{ config('catcare.clinic.name') }}</h1>
<div class="sub">Periode {{ tgl($from) }} s/d {{ tgl($to) }} • Dicetak {{ now()->format('d/m/Y H:i') }} oleh {{ auth()->user()->name }}</div></div>
@include('admin._recap-body')
</body></html>
