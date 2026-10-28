@extends('layouts.app')
@section('title', 'Shift')
@section('page', 'Jadwal Shift')
@section('subtitle', 'Minggu ' . $start->locale('id')->translatedFormat('d M') . ' – ' . $start->copy()->addDays(6)->locale('id')->translatedFormat('d M Y'))
@section('actions')
    <a class="btn btn-sm" href="?week={{ $prev }}">← Minggu lalu</a>
    <a class="btn btn-sm" href="?week={{ today()->toDateString() }}">Minggu ini</a>
    <a class="btn btn-sm" href="?week={{ $next }}">Minggu depan →</a>
@endsection
@section('content')
<div class="row mb">
    @foreach (config('catcare.shifts') as $k => $s)<span class="badge" style="background:{{ $s['color'] }}">{{ $s['label'] }} • {{ $s['time'] }}</span>@endforeach
    <form class="inline-form" method="POST" action="{{ route('admin.shifts.copy') }}" data-confirm="Salin jadwal minggu sebelumnya ke minggu ini? (menimpa yang sama)">@csrf<input type="hidden" name="week" value="{{ $start->toDateString() }}"><button class="btn btn-sm btn-yellow">📋 Salin dari minggu lalu</button></form>
</div>
<form method="POST" action="{{ route('admin.shifts.save') }}">
    @csrf <input type="hidden" name="week" value="{{ $start->toDateString() }}">
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Staff</th>@foreach ($days as $d)<th class="center" style="{{ $d->isToday() ? 'background:#b00020' : '' }}">{{ $d->locale('id')->translatedFormat('D d/m') }}</th>@endforeach</tr></thead>
        <tbody>
        @foreach ($staff as $s)
            <tr>
                <td><b>{{ $s->name }}</b><br><span class="xs muted upper">{{ $s->roleLabel() }}</span></td>
                @foreach ($days as $d)
                    @php($cur = $shifts[$s->id . '|' . $d->toDateString()] ?? '')
                    <td style="padding:6px;background:{{ $cur ? config('catcare.shifts.'.$cur.'.color') : '#fff' }}">
                        <select class="select sm" style="min-width:86px" name="shift[{{ $s->id }}][{{ $d->toDateString() }}]">
                            <option value="">Libur</option>
                            @foreach (config('catcare.shifts') as $k => $sh)<option value="{{ $k }}" @selected($cur === $k)>{{ $sh['label'] }}</option>@endforeach
                        </select>
                    </td>
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table></div>
    <button class="btn btn-green btn-lg mt">💾 Simpan Jadwal</button>
</form>
@endsection
