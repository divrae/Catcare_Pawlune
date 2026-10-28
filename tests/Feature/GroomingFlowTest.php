<?php

namespace Tests\Feature;

use App\Models\Boarding;
use App\Models\GroomingOrder;
use App\Models\Invoice;
use App\Models\Notice;
use App\Models\Room;
use App\Models\Tariff;
use App\Models\User;
use App\Models\Visit;
use Database\Seeders\CatCareSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroomingFlowTest extends TestCase
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

    public function test_standalone_grooming_flow_from_owner_to_cashier(): void
    {
        $owner = $this->owner();
        $pet = $owner->pets()->first();
        $groomer = $this->u('groomer@catcare.com');
        $cashier = $this->u('cashier@catcare.com');

        $this->actingAs($owner)->post('/owner/antrean', ['pet_id' => $pet->id, 'service_type' => 'grooming', 'request_note' => 'bulu kusut'])->assertRedirect('/owner');
        $visit = Visit::firstOrFail();
        $this->assertSame('grooming', $visit->service_type);
        $this->assertSame('G-001', $visit->queueLabel());
        $order = GroomingOrder::firstOrFail();

        // owner tidak boleh masuk dashboard groomer
        $this->get('/grooming')->assertForbidden();

        $this->actingAs($groomer)->get('/grooming')->assertOk()->assertSee($pet->name);
        $this->get('/grooming/antrean')->assertOk();
        $this->post("/grooming/{$order->id}/panggil")->assertRedirect();
        $this->assertSame('called', $visit->fresh()->status);
        $this->assertTrue(Notice::where('user_id', $owner->id)->where('type', 'called')->exists());

        // kasir belum bisa menagih selagi grooming berjalan
        $this->actingAs($cashier)->post("/kasir/{$visit->id}/bayar", ['payment_method' => 'qris'])->assertSessionHasErrors('pay');

        $this->actingAs($groomer)->post("/grooming/{$order->id}/mulai", ['coat_condition' => 'kusut'])->assertRedirect("/grooming/{$order->id}");
        $this->assertSame('in_treatment', $visit->fresh()->status);
        $this->get("/grooming/{$order->id}")->assertOk()->assertSee('Layanan yang Dikerjakan');

        // wajib pilih layanan
        $this->post("/grooming/{$order->id}/selesai", ['items' => []])->assertSessionHasErrors('items');
        $t = Tariff::where('category', 'grooming')->where('name', 'Grooming Lengkap')->firstOrFail();
        $this->post("/grooming/{$order->id}/selesai", ['items' => [$t->id => 1], 'result_note' => 'Bulu halus'])->assertRedirect('/grooming');
        $this->assertSame('awaiting_payment', $visit->fresh()->status);
        $this->assertSame('done', $order->fresh()->status);

        $this->actingAs($cashier)->get('/kasir')->assertOk()->assertSee('Grooming');
        $this->post("/kasir/{$visit->id}/bayar", ['payment_method' => 'qris'])->assertRedirect();
        $inv = Invoice::firstOrFail();
        $this->assertEquals(120000, $inv->total_grooming);
        $this->assertEquals(120000, $inv->grand_total);
        $this->assertSame('completed', $visit->fresh()->status);
        $this->assertSame('grooming', $inv->items->first()->department);
        $this->get("/struk/{$inv->id}")->assertOk()->assertSee('GROOMING');
    }

    public function test_boarding_guest_grooming_is_billed_at_checkout(): void
    {
        $owner = $this->owner();
        $pet = $owner->pets()->first();
        $staff = $this->u('staff@catcare.com');
        $groomer = $this->u('groomer@catcare.com');
        $cashier = $this->u('cashier@catcare.com');

        $this->actingAs($owner)->post('/owner/antrean', [
            'pet_id' => $pet->id, 'service_type' => 'boarding',
            'check_in_date' => today()->toDateString(), 'check_out_date' => today()->addDays(2)->toDateString(),
        ]);
        $boarding = Boarding::firstOrFail();
        $room = Room::where('room_number', '03')->first(); // 90.000
        $this->actingAs($staff)->post("/boarding/{$boarding->id}/check-in", ['room_id' => $room->id]);
        $this->assertSame('active', $boarding->fresh()->status);

        // owner memesan grooming -> otomatis jadi tamu boarding (tanpa antrean baru)
        $this->actingAs($owner)->post('/owner/antrean', ['pet_id' => $pet->id, 'service_type' => 'grooming', 'request_note' => 'mandi'])->assertRedirect('/owner');
        $this->assertSame(1, Visit::count());
        $order = GroomingOrder::firstOrFail();
        $this->assertTrue($order->isGuest());
        $this->assertSame($boarding->id, $order->boarding_id);

        // sudah ada order terbuka -> ditolak
        $this->post('/owner/antrean', ['pet_id' => $pet->id, 'service_type' => 'grooming'])->assertSessionHasErrors('pet_id');

        // check-out diblokir selama grooming belum selesai
        $this->actingAs($staff)->post("/boarding/{$boarding->id}/check-out")->assertSessionHasErrors('boarding');
        $this->get("/boarding/{$boarding->id}")->assertOk()->assertSee('Grooming Tamu');
        $this->get('/boarding')->assertOk()->assertSee('Grooming berjalan');

        $this->actingAs($groomer)->post("/grooming/{$order->id}/mulai", ['coat_condition' => 'normal']);
        $this->assertSame('in_boarding', $boarding->visit->fresh()->status); // status boarding tidak berubah
        $t = Tariff::where('category', 'grooming')->where('name', 'like', 'Mandi%')->firstOrFail();
        $this->post("/grooming/{$order->id}/selesai", ['items' => [$t->id => 1]])->assertRedirect('/grooming');
        $this->assertSame('in_boarding', $boarding->visit->fresh()->status);

        $this->actingAs($owner)->get("/owner/boarding/{$boarding->id}")->assertOk()->assertSee('Grooming Selama Menginap');

        // staff memesan grooming kedua lalu membatalkan -> tidak menghalangi check-out
        $this->actingAs($staff)->post("/boarding/{$boarding->id}/grooming", ['request_note' => 'kuku'])->assertRedirect();
        $second = GroomingOrder::where('status', 'waiting')->firstOrFail();
        $this->actingAs($groomer)->post("/grooming/{$second->id}/batal")->assertRedirect();

        $boarding->update(['check_in_date' => today()->subDays(2)]);
        $this->actingAs($staff)->post("/boarding/{$boarding->id}/check-out")->assertRedirect();
        $visit = $boarding->visit->fresh();
        $this->assertSame('awaiting_payment', $visit->status);

        $this->actingAs($cashier)->post("/kasir/{$visit->id}/bayar", ['payment_method' => 'qris'])->assertRedirect();
        $inv = Invoice::firstOrFail();
        $this->assertEquals(2 * 90000, $inv->total_boarding);
        $this->assertEquals($t->price, $inv->total_grooming);
        $this->assertEquals(2 * 90000 + $t->price, $inv->grand_total);
    }

    public function test_walkin_admin_views_and_recap_include_grooming(): void
    {
        $groomer = $this->u('groomer@catcare.com');
        $admin = $this->u('admin@catcare.com');

        $this->actingAs($groomer)->post('/grooming/walk-in', ['owner_name' => 'Tamu Baru', 'owner_phone' => '081399990000', 'pet_name' => 'Bobi', 'species' => 'anjing', 'request_note' => 'mandi'])->assertRedirect('/grooming');
        $this->assertSame('G-001', Visit::firstOrFail()->queueLabel());
        $this->get('/grooming/riwayat')->assertOk();

        $this->actingAs($admin);
        foreach (['/admin', '/admin/rekap', '/admin/rekap?section=grooming', '/admin/rekap/cetak?section=grooming', '/grooming', '/grooming/riwayat'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/admin/rekap/pdf?section=grooming')->assertOk();
        $this->get('/admin')->assertSee('Grooming hari ini');

        // akun groomer punya shift & bisa dibuat admin
        $this->get('/admin/shift')->assertOk()->assertSee('Groomer Rani');
        $this->post('/admin/staff', ['name' => 'Groomer Baru', 'email' => 'g2@catcare.com', 'phone' => '081377771111', 'role' => 'groomer'])->assertSessionHas('temp_password');
        $this->assertSame('groomer', User::where('email', 'g2@catcare.com')->value('role'));
        $this->assertSame(route('grooming.index'), $groomer->homeRoute());
    }
}
