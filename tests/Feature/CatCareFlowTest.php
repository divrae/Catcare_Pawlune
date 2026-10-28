<?php

namespace Tests\Feature;

use App\Models\Boarding;
use App\Models\Invoice;
use App\Models\Medicine;
use App\Models\Notice;
use App\Models\Room;
use App\Models\Tariff;
use App\Models\User;
use App\Models\Visit;
use Database\Seeders\CatCareSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatCareFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatCareSeeder::class);
    }

    private function u(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function owner(): User
    {
        return User::where('phone', '08129981229')->firstOrFail();
    }

    public function test_public_pages_load(): void
    {
        $this->get('/')->assertOk()->assertSee('Soulmate Pass');
        $this->get('/login')->assertOk();
        $this->get('/daftar')->assertOk();
        $this->get('/owner')->assertRedirect('/login');
    }

    public function test_owner_register_with_phone_and_login(): void
    {
        $this->post('/daftar', ['name' => 'Budi', 'phone' => '+62 812-5555-0001', 'password' => 'secret1', 'password_confirmation' => 'secret1'])
            ->assertRedirect('/owner');
        $this->assertDatabaseHas('users', ['phone' => '081255550001', 'role' => 'owner']);
        auth()->logout();
        $this->post('/login', ['identifier' => '0812 5555 0001', 'password' => 'secret1'])->assertRedirect('/owner');
    }

    public function test_full_clinic_flow_call_exam_prescription_payment_and_stock(): void
    {
        $owner = $this->owner();
        $pet = $owner->pets()->first();

        // Owner ambil antrean
        $this->actingAs($owner)->post('/owner/antrean', ['pet_id' => $pet->id, 'service_type' => 'clinic', 'complaint' => 'batuk'])->assertRedirect('/owner');
        $visit = Visit::firstOrFail();
        $this->assertSame('K-001', $visit->queueLabel());

        // Tidak bisa antre ganda
        $this->post('/owner/antrean', ['pet_id' => $pet->id, 'service_type' => 'clinic', 'complaint' => 'x'])->assertSessionHasErrors('pet_id');

        // Dokter memanggil -> owner dapat notifikasi
        $doctor = $this->u('doctor@catcare.com');
        $this->actingAs($doctor)->post("/dokter/kunjungan/{$visit->id}/panggil")->assertRedirect();
        $this->assertSame('called', $visit->fresh()->status);
        $this->assertTrue(Notice::where('user_id', $owner->id)->where('type', 'called')->exists());

        // Owner poll melihat status called
        $this->actingAs($owner)->getJson('/owner/poll')->assertOk()->assertJsonPath('visits.0.status', 'called')->assertJsonPath('notices.0.type', 'called');

        // Dokter periksa + resep
        $this->actingAs($doctor)->post("/dokter/kunjungan/{$visit->id}/mulai")->assertRedirect("/dokter/kunjungan/{$visit->id}/periksa");
        $this->get("/dokter/kunjungan/{$visit->id}/periksa")->assertOk()->assertSee('Resep Obat');
        $tariff = Tariff::where('name', 'Pemeriksaan Umum')->first();
        $med = Medicine::where('name', 'Amoxicillin 50mg')->first();
        $stock0 = $med->stock;

        $this->put("/dokter/kunjungan/{$visit->id}/periksa", [
            'diagnosis' => 'Flu kucing', 'weight_kg' => 4, 'temperature_c' => 39.1,
            'services' => [['tariff_id' => $tariff->id, 'qty' => 1]],
            'prescriptions' => [['medicine_id' => $med->id, 'qty' => 10, 'dosage' => '2x1', 'instruction' => 'sesudah makan']],
        ])->assertRedirect('/dokter');

        $visit->refresh();
        $this->assertSame('awaiting_payment', $visit->status);
        $this->assertSame($stock0, $med->fresh()->stock, 'stok belum berkurang sebelum dibayar');

        // Kasir membayar tunai
        $cashier = $this->u('cashier@catcare.com');
        $this->actingAs($cashier)->get('/kasir')->assertOk()->assertSee('Amoxicillin 50mg');
        $total = 150000 + 10 * 8000;

        $this->post("/kasir/{$visit->id}/bayar", ['payment_method' => 'cash', 'amount_paid' => $total - 1])->assertSessionHasErrors('amount_paid');
        $this->post("/kasir/{$visit->id}/bayar", ['payment_method' => 'cash', 'amount_paid' => 250000])->assertRedirect();

        $inv = Invoice::firstOrFail();
        $this->assertEquals($total, $inv->grand_total);
        $this->assertEquals(20000, $inv->change_amount);
        $this->assertSame($stock0 - 10, $med->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', ['medicine_id' => $med->id, 'type' => 'out', 'qty' => 10]);
        $this->assertSame('completed', $visit->fresh()->status);
        $this->assertSame(2, $inv->items()->count());

        // tidak bisa bayar dua kali
        $this->post("/kasir/{$visit->id}/bayar", ['payment_method' => 'cash', 'amount_paid' => 250000])->assertSessionHasErrors('pay');

        // Struk: owner boleh, owner lain tidak
        $this->get("/struk/{$inv->id}")->assertOk()->assertSee($inv->invoice_number);
        $this->actingAs($owner)->get("/struk/{$inv->id}")->assertOk();
        $other = User::create(['name' => 'Lain', 'phone' => '081200009999', 'role' => 'owner', 'password' => bcrypt('x')]);
        $this->actingAs($other)->get("/struk/{$inv->id}")->assertForbidden();

        // Testimoni muncul di beranda
        $this->actingAs($owner)->post('/owner/testimoni', ['visit_id' => $visit->id, 'rating' => 5, 'comment' => 'Pelayanan super ramah'])->assertRedirect();
        auth()->logout();
        $this->get('/')->assertSee('Pelayanan super ramah');
    }

    public function test_prescription_rejects_expired_or_too_many(): void
    {
        $owner = $this->owner();
        $pet = $owner->pets()->first();
        $this->actingAs($owner)->post('/owner/antrean', ['pet_id' => $pet->id, 'service_type' => 'clinic', 'complaint' => 'x']);
        $visit = Visit::firstOrFail();
        $doctor = $this->u('doctor@catcare.com');
        $this->actingAs($doctor)->post("/dokter/kunjungan/{$visit->id}/mulai");
        $tariff = Tariff::where('name', 'Pemeriksaan Umum')->first();
        $med = Medicine::first();

        $this->put("/dokter/kunjungan/{$visit->id}/periksa", ['diagnosis' => 'x', 'services' => [['tariff_id' => $tariff->id, 'qty' => 1]],
            'prescriptions' => [['medicine_id' => $med->id, 'qty' => $med->stock + 1]]])->assertSessionHasErrors('prescriptions');

        $med->update(['expired_date' => today()->subDay()]);
        $this->put("/dokter/kunjungan/{$visit->id}/periksa", ['diagnosis' => 'x', 'services' => [['tariff_id' => $tariff->id, 'qty' => 1]],
            'prescriptions' => [['medicine_id' => $med->id, 'qty' => 1]]])->assertSessionHasErrors('prescriptions');

        $this->put("/dokter/kunjungan/{$visit->id}/periksa", ['diagnosis' => 'x'])->assertSessionHasErrors('services');
    }

    public function test_referral_to_boarding_then_checkout_bills_days_times_rate(): void
    {
        $owner = $this->owner();
        $pet = $owner->pets()->first();
        $doctor = $this->u('doctor@catcare.com');
        $staff = $this->u('staff@catcare.com');
        $cashier = $this->u('cashier@catcare.com');

        $this->actingAs($owner)->post('/owner/antrean', ['pet_id' => $pet->id, 'service_type' => 'clinic', 'complaint' => 'lemas']);
        $visit = Visit::firstOrFail();
        $this->actingAs($doctor)->post("/dokter/kunjungan/{$visit->id}/mulai");
        $tariff = Tariff::where('name', 'Pemeriksaan Umum')->first();

        $this->put("/dokter/kunjungan/{$visit->id}/periksa", [
            'diagnosis' => 'Dehidrasi berat', 'services' => [['tariff_id' => $tariff->id, 'qty' => 1]],
            'refer_boarding' => 1, 'boarding_until' => today()->addDays(3)->toDateString(), 'referral_note' => 'Infus',
        ])->assertRedirect('/dokter');
        $this->assertSame('in_boarding', $visit->fresh()->status);

        // kasir belum bisa menagih
        $this->actingAs($cashier)->post("/kasir/{$visit->id}/bayar", ['payment_method' => 'qris'])->assertSessionHasErrors('pay');

        // staff check-in & catatan & check-out
        $boarding = Boarding::firstOrFail();
        $room = Room::where('room_number', '03')->first(); // deluxe 90.000
        $this->actingAs($staff)->get('/boarding')->assertOk()->assertSee('Rujukan Dokter');
        $this->post("/boarding/{$boarding->id}/check-in", ['room_id' => $room->id])->assertRedirect();
        $this->assertSame('active', $boarding->fresh()->status);
        $this->post("/boarding/{$boarding->id}/catatan", ['meal' => 'habis', 'temperature_c' => 38.6, 'health_status' => 'sehat', 'stool' => 'normal'])->assertRedirect();
        $this->assertSame(1, $boarding->logs()->count());
        $this->get("/boarding/{$boarding->id}")->assertOk();
        $this->get("/boarding/{$boarding->id}/cetak")->assertOk();

        // kamar terisi tak bisa dipakai dua kali
        $this->assertSame('occupied', Room::with('currentStay')->find($room->id)->state());

        // geser check-in 2 hari ke belakang agar 2 hari menginap
        $boarding->update(['check_in_date' => today()->subDays(2)]);
        $this->post("/boarding/{$boarding->id}/check-out")->assertRedirect();
        $this->assertSame('awaiting_payment', $visit->fresh()->status);

        $this->actingAs($cashier)->post("/kasir/{$visit->id}/bayar", ['payment_method' => 'transfer', 'bank_name' => 'BCA'])->assertSessionHasErrors('reference_no');
        $this->post("/kasir/{$visit->id}/bayar", ['payment_method' => 'transfer', 'bank_name' => 'BCA', 'reference_no' => '1234', 'discount' => 10000])->assertRedirect();

        $inv = Invoice::firstOrFail();
        $this->assertEquals(150000 + 2 * 90000, $inv->total_medical + $inv->total_boarding);
        $this->assertEquals(2 * 90000, $inv->total_boarding);
        $this->assertEquals(150000 + 180000 - 10000, $inv->grand_total);
    }

    public function test_walk_in_creates_owner_stub_and_account_can_be_claimed(): void
    {
        $doctor = $this->u('doctor@catcare.com');
        $this->actingAs($doctor)->post('/dokter/walk-in', ['owner_name' => 'Pak Joko', 'owner_phone' => '0813 9999 8888', 'pet_name' => 'Tom', 'species' => 'kucing', 'complaint' => 'muntah'])->assertRedirect('/dokter');
        $stub = User::where('phone', '081399998888')->firstOrFail();
        $this->assertNull($stub->password);
        $this->assertSame('walk_in', Visit::first()->source);
        $this->assertSame('K-001', Visit::first()->queueLabel());

        // stub tidak bisa login
        auth()->logout();
        $this->post('/login', ['identifier' => '081399998888', 'password' => 'apapun'])->assertSessionHasErrors('identifier');

        // klaim lewat daftar -> riwayat tersambung
        $this->post('/daftar', ['name' => 'Joko Widodo', 'phone' => '081399998888', 'password' => 'rahasia1', 'password_confirmation' => 'rahasia1'])->assertRedirect('/owner');
        $this->assertSame(1, User::where('phone', '081399998888')->count());
        $this->get('/owner')->assertOk()->assertSee('Tom');
    }

    public function test_role_access_control(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner)->get('/admin')->assertForbidden();
        $this->get('/kasir')->assertForbidden();
        $this->get('/dokter')->assertForbidden();
        $this->actingAs($this->u('cashier@catcare.com'))->get('/dokter')->assertForbidden();
        $this->get('/boarding')->assertForbidden();
        $this->actingAs($this->u('staff@catcare.com'))->get('/kasir')->assertForbidden();
        $this->actingAs($this->u('doctor@catcare.com'))->get('/admin')->assertForbidden();
        $this->actingAs($this->u('admin@catcare.com'))->get('/owner')->assertForbidden();
    }

    public function test_admin_pages_render_and_crud(): void
    {
        $admin = $this->u('admin@catcare.com');
        $this->actingAs($admin);
        foreach (['/admin', '/admin?days=7', '/admin/staff', '/admin/shift', '/admin/tarif', '/admin/obat', '/admin/kamar', '/admin/pelanggan', '/admin/testimoni',
            '/admin/rekap', '/admin/rekap?section=pos', '/admin/rekap/cetak', '/dokter', '/dokter/rekam-medis', '/boarding', '/boarding/rekap', '/kasir', '/kasir/riwayat'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/admin/rekap/pdf')->assertOk()->assertHeader('content-type', 'application/pdf');

        // tambah staff -> wajib ganti password
        $this->post('/admin/staff', ['name' => 'Kasir Baru', 'email' => 'baru@catcare.com', 'phone' => '081377770000', 'role' => 'cashier'])->assertSessionHas('temp_password');
        $new = User::where('email', 'baru@catcare.com')->first();
        $this->assertTrue($new->must_change_password);
        $temp = session('temp_password')['password'];
        auth()->logout();
        $this->post('/login', ['identifier' => 'baru@catcare.com', 'password' => $temp])->assertRedirect();
        $this->get('/kasir')->assertRedirect('/ganti-password');
        $this->post('/ganti-password', ['current_password' => $temp, 'new_password' => 'passbaru1', 'new_password_confirmation' => 'passbaru1'])->assertRedirect();
        $this->get('/kasir')->assertOk();

        // obat: satu kode otomatis + stok awal tercatat
        $this->actingAs($admin)->post('/admin/obat', ['name' => 'Obat Uji', 'category' => 'Vitamin', 'unit' => 'Tablet', 'price' => 5000, 'stock' => 20, 'min_stock' => 5, 'expired_date' => today()->addYear()->toDateString()])->assertRedirect();
        $m = Medicine::where('name', 'Obat Uji')->first();
        $this->assertMatchesRegularExpression('/^OBT-\d{4}$/', $m->code);
        $this->assertSame(20, $m->stock);
        $this->post("/admin/obat/{$m->id}/stok", ['mode' => 'in', 'qty' => 5])->assertRedirect();
        $this->assertSame(25, $m->fresh()->stock);

        // shift
        $doc = $this->u('doctor@catcare.com');
        $week = today()->startOfWeek()->toDateString();
        $this->post('/admin/shift', ['week' => $week, 'shift' => [$doc->id => [$week => 'malam']]])->assertRedirect();
        $this->assertSame('malam', \App\Models\Shift::where('user_id', $doc->id)->whereDate('shift_date', $week)->value('shift_type'));
    }

    public function test_cashier_cannot_pay_with_insufficient_stock_at_payment_time(): void
    {
        $owner = $this->owner();
        $pet = $owner->pets()->first();
        $this->actingAs($owner)->post('/owner/antrean', ['pet_id' => $pet->id, 'service_type' => 'clinic', 'complaint' => 'x']);
        $visit = Visit::firstOrFail();
        $doctor = $this->u('doctor@catcare.com');
        $this->actingAs($doctor)->post("/dokter/kunjungan/{$visit->id}/mulai");
        $tariff = Tariff::where('name', 'Pemeriksaan Umum')->first();
        $med = Medicine::where('name', 'Amoxicillin 50mg')->first();
        $this->put("/dokter/kunjungan/{$visit->id}/periksa", ['diagnosis' => 'x', 'services' => [['tariff_id' => $tariff->id, 'qty' => 1]], 'prescriptions' => [['medicine_id' => $med->id, 'qty' => 100]]]);
        $med->update(['stock' => 5]); // stok habis dipakai pasien lain

        $this->actingAs($this->u('cashier@catcare.com'))->post("/kasir/{$visit->id}/bayar", ['payment_method' => 'qris'])->assertSessionHasErrors('pay');
        $this->assertSame(0, Invoice::count());
        $this->assertSame(5, $med->fresh()->stock);
    }
}
