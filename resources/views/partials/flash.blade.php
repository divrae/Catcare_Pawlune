@if (session('success'))
    <div class="flash ok">✅ <div>{{ session('success') }}</div></div>
@endif
@if (session('error'))
    <div class="flash bad">⚠️ <div>{{ session('error') }}</div></div>
@endif
@if (session('temp_password'))
    @php($tp = session('temp_password'))
    <div class="flash warn" style="display:block">
        <div class="bold">🔑 Password sementara untuk {{ $tp['name'] }} (hanya tampil sekali — catat/kirim ke yang bersangkutan)</div>
        <div class="mono" style="font-size:1.1rem;margin-top:6px">{{ $tp['email'] }} &nbsp;/&nbsp; <span style="background:#fff;border:2px solid #000;padding:1px 8px">{{ $tp['password'] }}</span></div>
        <div class="small">Saat login pertama, staff wajib menggantinya.</div>
    </div>
@endif
@if ($errors->any())
    <div class="flash bad">⚠️
        <div>
            <div class="bold">Ada yang perlu diperbaiki:</div>
            <ul>@foreach (collect($errors->all())->unique() as $msg)<li>{{ $msg }}</li>@endforeach</ul>
        </div>
    </div>
@endif
