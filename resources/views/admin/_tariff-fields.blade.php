<div class="field"><label>Nama layanan</label><input class="input" name="name" value="{{ old('name') }}" required></div>
<div class="fgrid">
    <div class="field"><label>Kategori</label><select class="select" name="category">@foreach ($categories as $k => $l)<option value="{{ $k }}" @selected(old('category') === $k)>{{ $l }}</option>@endforeach</select></div>
    <div class="field"><label>Harga (Rp)</label><input class="input" type="number" min="0" name="price" value="{{ old('price') }}" required></div>
</div>
<input type="hidden" name="is_active" value="0">
<label class="check mb"><input type="checkbox" name="is_active" value="1" checked> Aktif (bisa dipilih dokter/kasir)</label>
