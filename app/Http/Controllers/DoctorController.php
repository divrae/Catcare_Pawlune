<?php

namespace App\Http\Controllers;

use App\Models\Boarding;
use App\Models\MedicalRecord;
use App\Models\Medicine;
use App\Models\Notice;
use App\Models\Pet;
use App\Models\Room;
use App\Models\Tariff;
use App\Models\Visit;
use App\Services\AdmissionService;
use App\Services\ExamService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DoctorController extends Controller
{
    public function dashboard(Request $request)
    {
        return view('doctor.dashboard', $this->queueData($request) + [
            'shift' => $request->user()->todayShift(),
            'species' => config('catcare.species'),
        ]);
    }

    /** Potongan HTML antrean untuk auto-refresh */
    public function queueFragment(Request $request)
    {
        return view('doctor._queue', $this->queueData($request));
    }

    private function queueData(Request $request): array
    {
        $base = Visit::with('pet.owner', 'doctor')->today()->where('service_type', 'clinic');

        return [
            'waiting' => (clone $base)->where('status', 'waiting')->orderBy('queue_number')->get(),
            'called' => (clone $base)->where('status', 'called')->orderBy('called_at')->get(),
            'treating' => (clone $base)->where('status', 'in_treatment')->orderBy('started_at')->get(),
            'done' => (clone $base)->whereIn('status', ['awaiting_payment', 'in_boarding', 'completed'])->latest('updated_at')->take(12)->get(),
            'doneCount' => (clone $base)->whereIn('status', ['awaiting_payment', 'in_boarding', 'completed'])->count(),
            'me' => $request->user(),
        ];
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
            'complaint' => 'required|string|max:500',
        ], [
            'owner_name.required_without' => 'Nama pemilik wajib diisi (atau isi kode Soulmate Pass).',
            'owner_phone.required_without' => 'Nomor HP pemilik wajib diisi.',
            'pet_name.required_without' => 'Nama hewan wajib diisi.',
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

        $visit = $admission->createVisit($pet, 'clinic', $data['complaint'], 'walk_in', $request->user()->id);

        return redirect()->route('doctor.dashboard')->with('success', "Walk-in {$pet->name} masuk antrean {$visit->queueLabel()}.");
    }

    public function call(Request $request, Visit $visit)
    {
        abort_unless($visit->service_type === 'clinic', 404);
        if (! in_array($visit->status, ['waiting', 'called'], true)) {
            return back()->withErrors(['queue' => 'Antrean ini tidak bisa dipanggil (status: ' . $visit->statusLabel() . ').']);
        }

        $visit->update(['status' => 'called', 'doctor_id' => $request->user()->id, 'called_at' => now()]);
        $visit->load('pet');

        Notice::send($visit->pet->owner_id, "Giliran {$visit->pet->name} dipanggil! 🔔",
            "Antrean {$visit->queueLabel()} — silakan menuju ruang periksa ({$request->user()->name}).", 'called', $visit->id);

        return back()->with('success', "{$visit->queueLabel()} {$visit->pet->name} dipanggil — notifikasi terkirim ke pemilik.");
    }

    public function start(Request $request, Visit $visit)
    {
        abort_unless($visit->service_type === 'clinic', 404);

        if ($visit->status === 'in_treatment' && $visit->doctor_id === $request->user()->id) {
            return redirect()->route('doctor.exam', $visit);
        }
        if (! in_array($visit->status, ['waiting', 'called'], true)) {
            return back()->withErrors(['queue' => 'Antrean ini sedang/sudah diproses dokter lain.']);
        }

        $visit->update([
            'status' => 'in_treatment', 'doctor_id' => $request->user()->id,
            'called_at' => $visit->called_at ?? now(), 'started_at' => now(),
        ]);

        return redirect()->route('doctor.exam', $visit);
    }

    public function noShow(Visit $visit)
    {
        if (! in_array($visit->status, ['waiting', 'called'], true)) {
            return back()->withErrors(['queue' => 'Hanya antrean menunggu/dipanggil yang bisa dilewati.']);
        }
        $visit->update(['status' => 'cancelled']);

        return back()->with('success', "{$visit->queueLabel()} ditandai tidak hadir.");
    }

    public function exam(Request $request, Visit $visit)
    {
        $this->authorizeExam($request, $visit);
        $visit->load('pet.owner', 'medicalRecord', 'prescriptions', 'services', 'boarding.room');

        $history = MedicalRecord::with('doctor', 'visit', 'prescriptions')
            ->whereHas('visit', fn ($q) => $q->where('pet_id', $visit->pet_id)->where('id', '!=', $visit->id))
            ->latest()->take(5)->get();

        $currentMedIds = $visit->prescriptions->pluck('medicine_id');
        $medicines = Medicine::where(fn ($q) => $q->prescribable()->orWhereIn('id', $currentMedIds))
            ->orderBy('category')->orderBy('name')->get();

        return view('doctor.exam', [
            'visit' => $visit,
            'record' => $visit->medicalRecord,
            'history' => $history,
            'tariffs' => Tariff::active()->whereIn('category', ['pemeriksaan', 'tindakan'])->orderBy('category')->orderBy('name')->get(),
            'medicines' => $medicines,
            'freeRooms' => Room::where('is_maintenance', false)->count() - Boarding::where('status', 'active')->count(),
            'locked' => $visit->payment_status === 'paid',
        ]);
    }

    public function saveExam(Request $request, Visit $visit, ExamService $exams)
    {
        $this->authorizeExam($request, $visit);

        $data = $request->validate([
            'anamnesis' => 'nullable|string|max:2000',
            'diagnosis' => 'required|string|max:2000',
            'treatment_note' => 'nullable|string|max:2000',
            'weight_kg' => 'nullable|numeric|between:0.05,99',
            'temperature_c' => 'nullable|numeric|between:30,45',
            'follow_up_date' => 'nullable|date|after_or_equal:today',
            'services' => 'required|array|min:1',
            'services.*.tariff_id' => 'nullable|exists:tariffs,id',
            'services.*.qty' => 'nullable|integer|min:1|max:99',
            'prescriptions' => 'nullable|array',
            'prescriptions.*.medicine_id' => 'nullable|exists:medicines,id',
            'prescriptions.*.qty' => 'nullable|integer|min:1|max:999',
            'prescriptions.*.dosage' => 'nullable|string|max:80',
            'prescriptions.*.instruction' => 'nullable|string|max:150',
            'refer_boarding' => 'nullable|boolean',
            'referral_note' => 'nullable|string|max:500',
            'boarding_until' => 'nullable|date|after:today',
        ], ['services.required' => 'Pilih minimal satu tarif pemeriksaan/tindakan.', 'services.min' => 'Pilih minimal satu tarif pemeriksaan/tindakan.']);

        if (collect($data['services'])->filter(fn ($s) => ! empty($s['tariff_id']))->isEmpty()) {
            throw ValidationException::withMessages(['services' => 'Pilih minimal satu tarif pemeriksaan/tindakan.']);
        }

        $data['refer_boarding'] = $request->boolean('refer_boarding');
        $exams->save($visit, $request->user(), $data);

        $msg = $data['refer_boarding']
            ? 'Pemeriksaan disimpan. Hewan dirujuk ke Boarding — staf akan menyiapkan kamar.'
            : 'Pemeriksaan selesai. Tagihan (tarif + resep) otomatis masuk ke Kasir.';

        return redirect()->route('doctor.dashboard')->with('success', $msg);
    }

    public function history(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $records = MedicalRecord::with('visit.pet.owner', 'doctor', 'prescriptions')
            ->when($q !== '', fn ($w) => $w->where(function ($x) use ($q) {
                $x->where('diagnosis', 'like', "%$q%")
                    ->orWhereHas('visit.pet', fn ($p) => $p->where('name', 'like', "%$q%")->orWhere('pass_code', 'like', "%$q%"))
                    ->orWhereHas('visit.pet.owner', fn ($o) => $o->where('name', 'like', "%$q%")->orWhere('phone', 'like', "%$q%"));
            }))
            ->latest()->paginate(15)->withQueryString();

        return view('doctor.history', compact('records', 'q'));
    }

    private function authorizeExam(Request $request, Visit $visit): void
    {
        abort_unless($visit->service_type === 'clinic', 404);
        abort_unless(in_array($visit->status, ['in_treatment', 'awaiting_payment', 'in_boarding', 'completed'], true), 403, 'Antrean ini belum dimulai pemeriksaannya.');

        $user = $request->user();
        abort_unless($user->role === 'admin' || ! $visit->doctor_id || $visit->doctor_id === $user->id || $visit->status !== 'in_treatment', 403, 'Pasien ini sedang diperiksa dokter lain.');
    }
}
