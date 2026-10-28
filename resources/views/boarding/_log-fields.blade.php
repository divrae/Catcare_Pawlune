<div class="fgrid">
    <div class="field"><label>Makan</label><select class="select" name="meal">@foreach ($mealOptions as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
    <div class="field"><label>Keterangan makanan</label><input class="input" name="food_note" placeholder="Wet food 1 sachet"></div>
    <div class="field"><label>Suhu badan (°C)</label><input class="input" type="number" step="0.1" min="30" max="45" name="temperature_c" placeholder="38.5"></div>
    <div class="field"><label>Kondisi kesehatan</label><select class="select" name="health_status">@foreach ($healthOptions as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
    <div class="field"><label>BAB</label><select class="select" name="stool">@foreach ($stoolOptions as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
    <div class="field"><label>&nbsp;</label><label class="check"><input type="checkbox" name="medicine_given" value="1"> Obat sudah diberikan</label></div>
    <div class="field full"><label>Catatan</label><textarea class="textarea" name="note" style="min-height:60px" placeholder="Aktif, bermain, tidak muntah…"></textarea></div>
</div>
