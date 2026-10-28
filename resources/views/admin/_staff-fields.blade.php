<div class="field"><label>Nama lengkap</label><input class="input" name="name" value="{{ old('name') }}" required></div>
<div class="fgrid">
    <div class="field"><label>Email (untuk login)</label><input class="input" type="email" name="email" value="{{ old('email') }}" required></div>
    <div class="field"><label>Nomor HP</label><input class="input" name="phone" value="{{ old('phone') }}"></div>
    <div class="field full"><label>Role</label>
        <select class="select" name="role">@foreach ($roles as $k => $l)<option value="{{ $k }}" @selected(old('role') === $k)>{{ $l }}</option>@endforeach</select></div>
</div>
