<div class="fgrid">
    <div class="field"><label>Nomor suite</label><input class="input" name="room_number" value="{{ old('room_number') }}" required></div>
    <div class="field"><label>Nama</label><input class="input" name="name" value="{{ old('name') }}" placeholder="Deluxe Garden"></div>
    <div class="field"><label>Tipe</label><select class="select" name="room_type"><option value="standard">Standard</option><option value="deluxe">Deluxe</option><option value="vip">VIP</option></select></div>
    <div class="field"><label>Tarif per hari</label><select class="select" name="tariff_id" required>@foreach ($boardingTariffs as $t)<option value="{{ $t->id }}">{{ $t->name }} — {{ rupiah($t->price) }}</option>@endforeach</select></div>
</div>
