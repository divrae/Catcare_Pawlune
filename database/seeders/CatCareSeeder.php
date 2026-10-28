<?php

namespace Database\Seeders;

use App\Models\Medicine;
use App\Models\Pet;
use App\Models\Room;
use App\Models\Shift;
use App\Models\Tariff;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/** Data master + akun awal. Semua akun demo berpassword: password */
class CatCareSeeder extends Seeder
{
    public function run(): void
    {
        $pw = Hash::make('password');

        $users = [
            ['System Administrator', 'admin@catcare.com', '081200000001', 'admin'],
            ['Dr. Andi Vet, DVM', 'doctor@catcare.com', '081200000002', 'doctor'],
            ['Drh. Sari Wulandari', 'doctor2@catcare.com', '081200000003', 'doctor'],
            ['Nurse Amy', 'staff@catcare.com', '081200000004', 'staff'],
            ['Cashier Dewi', 'cashier@catcare.com', '081200000005', 'cashier'],
            ['Groomer Rani', 'groomer@catcare.com', '081200000006', 'groomer'],
        ];
        foreach ($users as [$name, $email, $phone, $role]) {
            User::updateOrCreate(['email' => $email], compact('name', 'phone', 'role') + ['password' => $pw, 'is_active' => true]);
        }

        $owner = User::updateOrCreate(['phone' => '08129981229'], [
            'name' => 'Diva Paramida', 'email' => 'owner@catcare.com', 'role' => 'owner', 'password' => $pw, 'address' => 'Klaten, Jawa Tengah',
        ]);
        if (! $owner->pets()->exists()) {
            Pet::create(['owner_id' => $owner->id, 'pass_code' => Pet::generatePassCode(), 'name' => 'Mimi von Fluff', 'species' => 'kucing', 'breed' => 'Scottish Fold', 'gender' => 'betina', 'temperament' => 'tenang']);
            Pet::create(['owner_id' => $owner->id, 'pass_code' => Pet::generatePassCode(), 'name' => 'Mocca', 'species' => 'kucing', 'breed' => 'Domestik', 'gender' => 'jantan', 'temperament' => 'cemas']);
        }

        // ---------- Tarif ----------
        $tariffs = [
            ['Pemeriksaan Umum', 'pemeriksaan', 150000], ['Konsultasi Lanjutan / Kontrol', 'pemeriksaan', 75000],
            ['Pemeriksaan Darah Lengkap', 'pemeriksaan', 180000], ['Pemeriksaan IGD (Darurat)', 'pemeriksaan', 250000],
            ['Suntik / Injeksi', 'tindakan', 50000], ['Jasa Vaksinasi', 'tindakan', 75000], ['Pemasangan Infus', 'tindakan', 120000],
            ['Perawatan Luka', 'tindakan', 60000], ['Scaling Gigi', 'tindakan', 250000], ['Sterilisasi / Steril', 'tindakan', 450000],
            ['Mandi Anti Jamur/Kutu', 'grooming', 80000], ['Grooming Lengkap', 'grooming', 120000], ['Potong Kuku & Bersih Telinga', 'grooming', 35000],
            ['Boarding Standard', 'boarding', 60000], ['Boarding Deluxe', 'boarding', 90000], ['Boarding VIP / Penthouse', 'boarding', 150000],
        ];
        foreach ($tariffs as [$name, $cat, $price]) {
            if (! Tariff::where('name', $name)->exists()) {
                Tariff::create(['code' => Tariff::nextCode(), 'name' => $name, 'category' => $cat, 'unit' => $cat === 'boarding' ? 'hari' : 'kali', 'price' => $price, 'is_active' => true]);
            }
        }

        // ---------- Obat (1 kode = obat + batch) ----------
        $meds = [
            ['Neozin', 'Vaksin', 'Botol', 65000, 12, 10, '+4 months'], ['Axiri', 'Vitamin', 'Kapsul', 31000, 40, 10, '+8 months'],
            ['Yoryoxir', 'Salep/Luar', 'Tube', 50000, 18, 8, '+10 months'], ['Amoxicillin 50mg', 'Antibiotik', 'Tablet', 8000, 120, 30, '+9 months'],
            ['Drontal Cat', 'Obat Cacing', 'Tablet', 25000, 36, 10, '+14 months'], ['Griseofulvin', 'Anti Jamur', 'Tablet', 12000, 60, 20, '+6 months'],
            ['Frontline Spot-On', 'Antiparasit', 'Pipet', 85000, 9, 10, '+5 months'], ['Metacam Oral', 'Pereda Nyeri', 'Botol', 140000, 6, 5, '+12 months'],
            ['Ringer Lactate 500ml', 'Cairan/Infus', 'Kantong', 22000, 25, 10, '+11 months'], ['Probiotik Vet', 'Suplemen', 'Sachet', 9000, 8, 15, '+20 days'],
        ];
        foreach ($meds as [$name, $cat, $unit, $price, $stock, $min, $exp]) {
            if (Medicine::where('name', $name)->exists()) {
                continue;
            }
            $m = Medicine::create(['code' => Medicine::nextCode(), 'name' => $name, 'category' => $cat, 'unit' => $unit, 'price' => $price, 'stock' => 0, 'min_stock' => $min, 'expired_date' => now()->modify($exp), 'is_active' => true]);
            $m->moveStock('in', $stock, null, 'Stok awal');
        }

        // ---------- Kamar (10 suite seperti desain boarding) ----------
        $std = Tariff::where('name', 'Boarding Standard')->first()->id;
        $dlx = Tariff::where('name', 'Boarding Deluxe')->first()->id;
        $vip = Tariff::where('name', 'Boarding VIP / Penthouse')->first()->id;
        $rooms = [
            ['01', 'Penthouse', 'vip', $vip], ['02', 'Penthouse Twin', 'vip', $vip],
            ['03', 'Deluxe Garden', 'deluxe', $dlx], ['04', 'Deluxe Garden', 'deluxe', $dlx],
            ['05', 'Deluxe Window', 'deluxe', $dlx], ['06', 'Deluxe Window', 'deluxe', $dlx],
            ['07', 'Standard', 'standard', $std], ['08', 'Standard', 'standard', $std],
            ['09', 'Standard', 'standard', $std], ['10', 'Standard', 'standard', $std],
        ];
        foreach ($rooms as [$no, $name, $type, $tariff]) {
            Room::firstOrCreate(['room_number' => $no], ['name' => $name, 'room_type' => $type, 'tariff_id' => $tariff]);
        }

        // ---------- Shift minggu ini ----------
        $week = today()->startOfWeek();
        $plan = ['doctor@catcare.com' => ['pagi', 'pagi', 'pagi', 'siang', 'siang', 'pagi', null],
            'doctor2@catcare.com' => ['siang', 'siang', 'siang', 'pagi', 'pagi', null, 'pagi'],
            'staff@catcare.com' => ['pagi', 'pagi', 'malam', 'malam', 'pagi', 'pagi', 'pagi'],
            'cashier@catcare.com' => ['pagi', 'pagi', 'pagi', 'pagi', 'siang', 'siang', null],
            'groomer@catcare.com' => ['pagi', 'pagi', 'siang', 'siang', 'pagi', 'pagi', null]];
        foreach ($plan as $email => $days) {
            $u = User::where('email', $email)->first();
            foreach ($days as $i => $type) {
                if ($type) {
                    Shift::firstOrCreate(['user_id' => $u->id, 'shift_date' => $week->copy()->addDays($i)->toDateString()], ['shift_type' => $type]);
                }
            }
        }
    }
}
