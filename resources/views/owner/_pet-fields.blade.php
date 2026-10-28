<div class="field"><label>Nama anabul</label><input class="input" name="name" value="{{ old('name', $p?->name) }}" required></div>
<div class="fgrid">
    <div class="field"><label>Jenis</label>
        <select class="select" name="species">@foreach (config('catcare.species') as $k => $l)<option value="{{ $k }}" @selected(old('species', $p?->species ?? 'kucing') === $k)>{{ $l }}</option>@endforeach</select></div>
    <div class="field"><label>Ras</label><input class="input" name="breed" value="{{ old('breed', $p?->breed) }}" placeholder="Domestik / Persia / Anggora"></div>
    <div class="field"><label>Jenis kelamin</label>
        <select class="select" name="gender"><option value="jantan" @selected(old('gender', $p?->gender) === 'jantan')>Jantan</option><option value="betina" @selected(old('gender', $p?->gender) === 'betina')>Betina</option></select></div>
    <div class="field"><label>Tanggal lahir (perkiraan)</label><input class="input" type="date" name="birth_date" max="{{ today()->toDateString() }}" value="{{ old('birth_date', $p?->birth_date?->toDateString()) }}"></div>
    <div class="field"><label>Sifat</label>
        <select class="select" name="temperament">@foreach (['tenang' => 'Tenang', 'cemas' => 'Mudah cemas', 'agresif' => 'Agresif'] as $k => $l)<option value="{{ $k }}" @selected(old('temperament', $p?->temperament ?? 'tenang') === $k)>{{ $l }}</option>@endforeach</select></div>
    <div class="field"><label>Foto (opsional)</label><input class="input" type="file" name="photo" accept="image/*"></div>
    <div class="field full"><label>Alergi / catatan khusus</label><input class="input" name="allergy_note" value="{{ old('allergy_note', $p?->allergy_note) }}"></div>
</div>
