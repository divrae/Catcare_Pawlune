<div class="field"><label>Nama obat</label><input class="input" name="name" value="{{ old('name') }}" required></div>
<div class="fgrid">
    <div class="field"><label>Kategori</label><select class="select" name="category">@foreach ($categories as $c)<option @selected(old('category') === $c)>{{ $c }}</option>@endforeach</select></div>
    <div class="field"><label>Satuan</label><input class="input" name="unit" value="{{ old('unit') }}" placeholder="Tablet / Botol / Tube" required></div>
    <div class="field"><label>Harga per satuan (Rp)</label><input class="input" type="number" min="0" name="price" value="{{ old('price') }}" required></div>
    @if ($withStock)<div class="field"><label>Stok awal</label><input class="input" type="number" min="0" name="stock" value="{{ old('stock', 0) }}" required></div>@endif
    <div class="field"><label>Stok minimum</label><input class="input" type="number" min="0" name="min_stock" value="{{ old('min_stock', 10) }}" required></div>
    <div class="field"><label>Tanggal kedaluwarsa</label><input class="input" type="date" name="expired_date" value="{{ old('expired_date') }}"></div>
</div>
