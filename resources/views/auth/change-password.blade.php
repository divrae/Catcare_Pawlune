@extends(auth()->user()->role === 'owner' ? 'layouts.public' : 'layouts.app')
@section('title', 'Ganti Password')
@section('page', 'Ganti Password')
@section('content')
<div class="{{ auth()->user()->role === 'owner' ? 'container' : '' }}">
    <div class="{{ auth()->user()->role === 'owner' ? 'auth-box' : '' }}" style="max-width:480px">
        <div class="card">
            <div class="card-head orange"><h2>🔐 Ganti Password</h2></div>
            @if (auth()->user()->must_change_password)
                <div class="flash warn">Akunmu memakai password sementara. Buat password baru dulu sebelum lanjut.</div>
            @endif
            @if (auth()->user()->role === 'owner')@include('partials.flash')@endif
            <form method="POST" action="{{ route('password.change.update') }}">
                @csrf
                <div class="field"><label>Password saat ini</label><input class="input" type="password" name="current_password" required></div>
                <div class="field"><label>Password baru</label><input class="input" type="password" name="new_password" minlength="6" required></div>
                <div class="field"><label>Ulangi password baru</label><input class="input" type="password" name="new_password_confirmation" required></div>
                <button class="btn btn-orange btn-block" type="submit">Simpan Password</button>
            </form>
        </div>
    </div>
</div>
@endsection
