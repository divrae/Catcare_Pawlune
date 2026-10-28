<?php

namespace Database\Seeders;

use App\Models\Boarding;
use App\Models\GroomingOrder;
use App\Models\Invoice;
use App\Models\MedicalRecord;
use App\Models\Medicine;
use App\Models\Pet;
use App\Models\Room;
use App\Models\Testimonial;
use App\Models\Tariff;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/** Data contoh 10 hari ke belakang supaya grafik dashboard admin langsung terisi. Aman dihapus. */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (Invoice::exists()) {
            return;
        }
        mt_srand(42);
        $doctors = User::where('role', 'doctor')->get();
        $cashier = User::where('role', 'cashier')->first();
        $consult = Tariff::where('name', 'Pemeriksaan Umum')->first();
        $inject = Tariff::where('name', 'Suntik / Injeksi')->first();
        $medicines = Medicine::all();
        $rooms = Room::all();

        // beberapa pemilik contoh
        $owners = collect([['Budi Santoso', '081311110001'], ['Siti Aminah', '081311110002'], ['Rina Kartika', '081311110003'], ['Agus Wijaya', '081311110004'], ['Dewi Lestari', '081311110005']])
            ->map(function ($o, $i) {
                $u = User::firstOrCreate(['phone' => $o[1]], ['name' => $o[0], 'role' => 'owner', 'password' => Hash::make('password'), 'created_at' => now()->subDays(10 - $i * 2)]);
                $names = [['Luna', 'kucing'], ['Bruno', 'anjing'], ['Kiki', 'kucing'], ['Oreo', 'kucing'], ['Simba', 'kucing']];
                return Pet::firstOrCreate(['owner_id' => $u->id, 'name' => $names[$i][0]], ['pass_code' => Pet::generatePassCode(), 'species' => $names[$i][1], 'breed' => 'Domestik', 'gender' => $i % 2 ? 'jantan' : 'betina', 'temperament' => 'tenang']);
            });

        $diagnoses = ['Flu kucing (URI)', 'Infeksi jamur kulit', 'Cacingan', 'Gastroenteritis ringan', 'Kutu & tungau telinga', 'Kontrol pasca vaksin', 'Defisiensi vitamin'];
        $meds = $medicines->whereIn('category', ['Antibiotik', 'Vitamin', 'Obat Cacing', 'Anti Jamur', 'Antiparasit']);

        for ($d = 9; $d >= 1; $d--) {
            $date = today()->subDays($d);
            $n = 0;
            foreach (range(1, mt_rand(2, 5)) as $k) {
                $n++;
                $pet = $owners->random();
                $doctor = $doctors->random();
                $when = $date->copy()->setTime(mt_rand(9, 17), mt_rand(0, 59));
                $visit = Visit::create([
                    'visit_code' => 'VST-' . $date->format('Ymd') . '-K' . str_pad((string) $n, 3, '0', STR_PAD_LEFT), 'pet_id' => $pet->id,
                    'service_type' => 'clinic', 'source' => $k % 3 === 0 ? 'walk_in' : 'online', 'queue_date' => $date, 'queue_number' => $n,
                    'complaint' => 'Nafsu makan turun', 'status' => 'completed', 'payment_status' => 'paid', 'doctor_id' => $doctor->id,
                    'called_at' => $when, 'started_at' => $when, 'finished_at' => $when->copy()->addMinutes(40), 'created_at' => $when, 'updated_at' => $when,
                ]);
                $rec = MedicalRecord::create(['visit_id' => $visit->id, 'doctor_id' => $doctor->id, 'anamnesis' => 'Nafsu makan turun, lesu', 'diagnosis' => $diagnoses[array_rand($diagnoses)],
                    'weight_kg' => mt_rand(20, 60) / 10, 'temperature_c' => mt_rand(380, 396) / 10, 'created_at' => $when, 'updated_at' => $when]);
                $items = [['medical', $consult->name, 1, (float) $consult->price]];
                $visit->services()->create(['tariff_id' => $consult->id, 'name' => $consult->name, 'category' => 'pemeriksaan', 'qty' => 1, 'unit_price' => $consult->price, 'subtotal' => $consult->price, 'added_by_role' => 'doctor']);
                if (mt_rand(0, 1)) {
                    $items[] = ['medical', $inject->name, 1, (float) $inject->price];
                    $visit->services()->create(['tariff_id' => $inject->id, 'name' => $inject->name, 'category' => 'tindakan', 'qty' => 1, 'unit_price' => $inject->price, 'subtotal' => $inject->price, 'added_by_role' => 'doctor']);
                }
                $number = 'INV-' . $date->format('Ymd') . '-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
                $stock = [];
                foreach ($meds->random(mt_rand(1, 2)) as $m) {
                    $q = mt_rand(1, 3);
                    $visit->prescriptions()->create(['medical_record_id' => $rec->id, 'medicine_id' => $m->id, 'medicine_name' => $m->name, 'category' => $m->category, 'qty' => $q, 'unit_price' => $m->price, 'subtotal' => $m->price * $q, 'dosage' => '2x1', 'instruction' => 'Sesudah makan']);
                    $items[] = ['pharmacy', "{$m->name} ({$m->category})", $q, (float) $m->price];
                    $stock[] = [$m, $q];
                }
                $total = collect($items)->sum(fn ($i) => $i[2] * $i[3]);
                $sum = fn ($dep) => collect($items)->where(0, $dep)->sum(fn ($i) => $i[2] * $i[3]);
                $inv = Invoice::create(['invoice_number' => $number, 'visit_id' => $visit->id, 'cashier_id' => $cashier->id,
                    'total_medical' => $sum('medical'), 'total_pharmacy' => $sum('pharmacy'), 'total_boarding' => 0, 'total_other' => 0, 'discount' => 0,
                    'grand_total' => $total, 'payment_method' => ['cash', 'qris', 'transfer'][mt_rand(0, 2)], 'amount_paid' => $total, 'change_amount' => 0,
                    'paid_at' => $when->copy()->addMinutes(45), 'created_at' => $when, 'updated_at' => $when]);
                foreach ($items as $i) {
                    $inv->items()->create(['department' => $i[0], 'description' => $i[1], 'qty' => $i[2], 'unit_price' => $i[3], 'subtotal' => $i[2] * $i[3]]);
                }
                foreach ($stock as [$m, $q]) {
                    $m->stock = max(0, $m->stock - $q);
                    $m->save();
                    $m->movements()->create(['type' => 'out', 'qty' => $q, 'stock_after' => $m->stock, 'reference' => $number, 'note' => 'Resep (data contoh)', 'user_id' => $cashier->id, 'created_at' => $when->copy()->addMinutes(45), 'updated_at' => $when]);
                }
            }

            // sesekali boarding selesai
            if ($d % 3 === 0) {
                $pet = $owners->random();
                $room = $rooms->random();
                $days = mt_rand(2, 4);
                $in = $date->copy();
                $out = $date->copy()->addDays($days);
                if ($out->gte(today())) {
                    continue;
                }
                $visit = Visit::create(['visit_code' => 'VST-' . $date->format('Ymd') . '-B001', 'pet_id' => $pet->id, 'service_type' => 'boarding', 'source' => 'online', 'queue_date' => $date,
                    'queue_number' => 1, 'complaint' => 'Penitipan', 'status' => 'completed', 'payment_status' => 'paid', 'finished_at' => $out, 'created_at' => $in, 'updated_at' => $out]);
                $b = Boarding::create(['visit_id' => $visit->id, 'pet_id' => $pet->id, 'room_id' => $room->id, 'status' => 'finished', 'check_in_date' => $in, 'check_out_date' => $out,
                    'checked_in_at' => $in->copy()->setTime(10, 0), 'checked_out_at' => $out->copy()->setTime(15, 0), 'rate_per_day' => $room->pricePerDay(), 'diet_note' => 'Dry food 2x sehari']);
                for ($i = 0; $i < $days; $i++) {
                    $b->logs()->create(['logged_at' => $in->copy()->addDays($i)->setTime(8, 0), 'meal' => 'habis', 'temperature_c' => 38.5, 'health_status' => 'sehat', 'stool' => 'normal', 'note' => 'Aktif dan nafsu makan baik']);
                }
                $total = $b->total();
                $inv = Invoice::create(['invoice_number' => 'INV-' . $date->format('Ymd') . '-9001', 'visit_id' => $visit->id, 'cashier_id' => $cashier->id, 'total_medical' => 0, 'total_pharmacy' => 0,
                    'total_boarding' => $total, 'total_other' => 0, 'discount' => 0, 'grand_total' => $total, 'payment_method' => 'transfer', 'amount_paid' => $total, 'bank_name' => 'BCA', 'reference_no' => '4821',
                    'paid_at' => $out->copy()->setTime(15, 20), 'created_at' => $out, 'updated_at' => $out]);
                $inv->items()->create(['department' => 'boarding', 'description' => "Boarding kamar {$room->room_number} • {$days} hari", 'qty' => $days, 'unit_price' => $b->rate_per_day, 'subtotal' => $total]);
            }
        }

        // ---------- Grooming contoh (antrean sendiri, dibayar di kasir) ----------
        $groomer = User::where('role', 'groomer')->first();
        $groomTariffs = Tariff::where('category', 'grooming')->get();
        if ($groomer && $groomTariffs->isNotEmpty()) {
            for ($d = 8; $d >= 0; $d--) {
                $date = today()->subDays($d);
                foreach (range(1, mt_rand(1, 3)) as $n) {
                    $pet = $owners->random();
                    $when = $date->copy()->setTime(mt_rand(9, 16), mt_rand(0, 59));
                    $visit = Visit::create(['visit_code' => 'VST-' . $date->format('Ymd') . '-G' . str_pad((string) $n, 3, '0', STR_PAD_LEFT), 'pet_id' => $pet->id,
                        'service_type' => 'grooming', 'source' => $n % 2 ? 'online' : 'walk_in', 'queue_date' => $date, 'queue_number' => $n, 'complaint' => 'Grooming',
                        'status' => 'completed', 'payment_status' => 'paid', 'called_at' => $when, 'started_at' => $when, 'finished_at' => $when->copy()->addMinutes(75),
                        'created_at' => $when, 'updated_at' => $when]);
                    $order = GroomingOrder::create(['visit_id' => $visit->id, 'pet_id' => $pet->id, 'groomer_id' => $groomer->id, 'status' => 'done',
                        'request_note' => 'Mandi & rapikan bulu', 'coat_condition' => 'Bulu agak kusut', 'result_note' => 'Bulu bersih dan halus',
                        'started_at' => $when, 'finished_at' => $when->copy()->addMinutes(60), 'created_at' => $when, 'updated_at' => $when]);
                    $t = $groomTariffs->random();
                    $visit->services()->create(['tariff_id' => $t->id, 'name' => $t->name, 'category' => 'grooming', 'qty' => 1, 'unit_price' => $t->price,
                        'subtotal' => $t->price, 'added_by_role' => 'groomer', 'grooming_order_id' => $order->id]);
                    $inv = Invoice::create(['invoice_number' => 'INV-' . $date->format('Ymd') . '-' . str_pad((string) (7000 + $n), 4, '0', STR_PAD_LEFT), 'visit_id' => $visit->id,
                        'cashier_id' => $cashier->id, 'total_medical' => 0, 'total_pharmacy' => 0, 'total_boarding' => 0, 'total_grooming' => $t->price, 'total_other' => 0,
                        'discount' => 0, 'grand_total' => $t->price, 'payment_method' => ['cash', 'qris', 'transfer'][mt_rand(0, 2)], 'amount_paid' => $t->price, 'change_amount' => 0,
                        'paid_at' => $when->copy()->addMinutes(80), 'created_at' => $when, 'updated_at' => $when]);
                    $inv->items()->create(['department' => 'grooming', 'description' => $t->name, 'qty' => 1, 'unit_price' => $t->price, 'subtotal' => $t->price]);
                }
            }
        }

        $v = Visit::where('status', 'completed')->first();
        if ($v) {
            Testimonial::create(['user_id' => $v->pet->owner_id, 'visit_id' => $v->id, 'rating' => 5, 'comment' => 'Dokternya ramah, anabulku langsung membaik. Antreannya jelas dan dikabari lewat dashboard!', 'is_published' => true]);
        }
        $v2 = Visit::where('status', 'completed')->where('service_type', 'boarding')->first();
        if ($v2) {
            Testimonial::create(['user_id' => $v2->pet->owner_id, 'visit_id' => $v2->id, 'rating' => 5, 'comment' => 'Boardingnya bersih, ada laporan makan dan suhu tiap hari. Tenang banget titip di sini.', 'is_published' => true]);
        }
    }
}
