<?php

namespace App\Http\Controllers;

use App\Models\Boarding;
use App\Models\BoardingLog;
use App\Models\Notice;
use App\Models\Pet;
use App\Models\Room;
use App\Models\Visit;
use App\Services\AdmissionService;
use App\Services\BoardingService;
use App\Services\GroomingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BoardingController extends Controller
{
    public function index(Request $request)
    {
        $rooms = Room::with('tariff', 'currentStay.pet.owner', 'currentStay.logs', 'currentStay.groomingOrders')->orderBy('room_number')->get();

        $waiting = Boarding::with('pet.owner', 'visit')->where('status', 'reserved')
            ->whereHas('visit', fn ($q) => $q->whereNotIn('status', ['cancelled', 'completed']))
            ->orderBy('visit_id')->get();
        $active = Boarding::with('pet.owner', 'room', 'logs')->where('status', 'active')->orderBy('check_out_date')->get();

        $total = $rooms->where('is_maintenance', false)->count();
        $occupied = $rooms->filter(fn ($r) => $r->state() === 'occupied')->count();

        return view('boarding.index', [
            'rooms' => $rooms,
            'waiting' => $waiting,
            'active' => $active,
            'stats' => [
                'total' => $total, 'occupied' => $occupied, 'free' => max(0, $total - $occupied),
                'rate' => $total ? round($occupied / $total * 100) : 0,
                'due' => $active->filter(fn ($b) => $b->check_out_date->lte(today()))->count(),
                'checkins_today' => Boarding::whereDate('checked_in_at', today())->count(),
            ],
            'freeRooms' => $rooms->filter(fn ($r) => $r->state() === 'available'),
            'shift' => $request->user()->todayShift(),
            'species' => config('catcare.species'),
            'mealOptions' => BoardingLog::MEAL, 'healthOptions' => BoardingLog::HEALTH, 'stoolOptions' => BoardingLog::STOOL,
        ]);
    }

    public function walkIn(Request $request, AdmissionService $admission)
    {
        $data = $request->validate([
            'pass_code' => 'nullable|string|max:20',
            'owner_name' => 'required_without:pass_code|nullable|string|max:100',
            'owner_phone' => 'required_without:pass_code|nullable|string|max:20',
            'pet_name' => 'required_without:pass_code|nullable|string|max:60',
            'species' => ['nullable', Rule::in(array_keys(config('catcare.species')))],
            'breed' => 'nullable|string|max:60',
            'gender' => 'nullable|in:jantan,betina',
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after:check_in_date',
            'diet_note' => 'nullable|string|max:300',
            'belongings' => 'nullable|string|max:300',
        ], [
            'owner_name.required_without' => 'Nama pemilik wajib diisi (atau isi kode Soulmate Pass).',
            'owner_phone.required_without' => 'Nomor HP pemilik wajib diisi.',
            'pet_name.required_without' => 'Nama hewan wajib diisi.',
            'check_out_date.after' => 'Tanggal keluar harus setelah tanggal masuk.',
        ]);

        if (! empty($data['pass_code'])) {
            $pet = Pet::where('pass_code', strtoupper(trim($data['pass_code'])))->first();
            if (! $pet) {
                throw ValidationException::withMessages(['pass_code' => 'Kode Soulmate Pass tidak ditemukan.']);
            }
        } else {
            $owner = $admission->findOrCreateOwner($data['owner_name'], $data['owner_phone']);
            $pet = $admission->findOrCreatePet($owner, $data);
        }

        $visit = $admission->createVisit($pet, 'boarding', 'Penitipan (walk-in)', 'walk_in', $request->user()->id, $data);

        return redirect()->route('boarding.index')->with('success', "Walk-in {$pet->name} masuk antrean {$visit->queueLabel()}. Silakan lanjut check-in.");
    }

    public function call(Visit $visit)
    {
        abort_unless($visit->service_type === 'boarding', 404);
        if (! in_array($visit->status, ['waiting', 'called'], true)) {
            return back()->withErrors(['queue' => 'Antrean ini tidak bisa dipanggil.']);
        }
        $visit->update(['status' => 'called', 'called_at' => now()]);
        $visit->load('pet');

        Notice::send($visit->pet->owner_id, "Giliran {$visit->pet->name} dipanggil! 🔔",
            "Antrean {$visit->queueLabel()} — silakan menuju meja Boarding untuk check-in.", 'called', $visit->id);

        return back()->with('success', "{$visit->queueLabel()} {$visit->pet->name} dipanggil.");
    }

    public function checkIn(Request $request, Boarding $boarding, BoardingService $svc)
    {
        $data = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'diet_note' => 'nullable|string|max:300',
            'belongings' => 'nullable|string|max:300',
        ]);
        $svc->checkIn($boarding, (int) $data['room_id'], $request->user(), $data);

        return back()->with('success', 'Check-in berhasil. Jangan lupa isi catatan harian.');
    }

    public function checkOut(Request $request, Boarding $boarding, BoardingService $svc)
    {
        $b = $svc->checkOut($boarding, $request->user());

        return back()->with('success', "Check-out selesai ({$b->billedDays()} hari). Tagihan sudah masuk ke Kasir.");
    }

    public function storeLog(Request $request, Boarding $boarding)
    {
        abort_unless($boarding->status === 'active', 403, 'Catatan hanya untuk hewan yang sedang menginap.');
        $data = $request->validate([
            'meal' => ['required', Rule::in(array_keys(BoardingLog::MEAL))],
            'food_note' => 'nullable|string|max:150',
            'temperature_c' => 'nullable|numeric|between:30,45',
            'health_status' => ['required', Rule::in(array_keys(BoardingLog::HEALTH))],
            'stool' => ['required', Rule::in(array_keys(BoardingLog::STOOL))],
            'medicine_given' => 'nullable|boolean',
            'note' => 'nullable|string|max:500',
        ]);
        $data['medicine_given'] = $request->boolean('medicine_given');

        $boarding->logs()->create($data + ['logged_at' => now(), 'recorded_by' => $request->user()->id]);

        return back()->with('success', 'Catatan harian tersimpan.');
    }

    /** Pesan grooming untuk tamu boarding (biaya masuk tagihan boarding, dibayar saat check-out) */
    public function orderGrooming(Request $request, Boarding $boarding, GroomingService $grooming)
    {
        abort_unless($boarding->status === 'active', 403, 'Grooming tamu hanya untuk hewan yang sedang menginap.');
        $data = $request->validate(['request_note' => 'nullable|string|max:300']);
        $grooming->request($boarding->pet, $request->user(), $data['request_note'] ?? null, 'walk_in');

        return back()->with('success', "Grooming untuk {$boarding->pet->name} diteruskan ke tim Grooming.");
    }

    public function show(Boarding $boarding)
    {
        $boarding->load('pet.owner', 'room.tariff', 'logs.recorder', 'visit', 'groomingOrders.services', 'groomingOrders.groomer');

        return view('boarding.show', [
            'boarding' => $boarding,
            'mealOptions' => BoardingLog::MEAL, 'healthOptions' => BoardingLog::HEALTH, 'stoolOptions' => BoardingLog::STOOL,
        ]);
    }

    /** Cetak rekap 1 hewan (log harian) */
    public function print(Boarding $boarding)
    {
        $boarding->load('pet.owner', 'room', 'logs.recorder', 'admitter');

        return view('boarding.print', compact('boarding'));
    }

    /** Rekap boarding seluruh hewan dalam rentang tanggal -> cetak */
    public function recap(Request $request)
    {
        $from = $request->date('from') ?? today();
        $to = $request->date('to') ?? today();

        $stays = Boarding::with('pet.owner', 'room', 'logs')
            ->whereIn('status', ['active', 'finished'])
            ->whereDate('check_in_date', '<=', $to)->whereDate('check_out_date', '>=', $from)
            ->orderBy('room_id')->get();

        $rooms = Room::orderBy('room_number')->get();
        $total = $rooms->where('is_maintenance', false)->count();

        return view('boarding.recap', [
            'stays' => $stays, 'rooms' => $rooms, 'from' => $from, 'to' => $to,
            'occupied' => $stays->where('status', 'active')->count(), 'total' => $total,
        ]);
    }
}
