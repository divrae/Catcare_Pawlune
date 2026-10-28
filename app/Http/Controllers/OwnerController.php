<?php

namespace App\Http\Controllers;

use App\Models\Boarding;
use App\Models\Notice;
use App\Models\Pet;
use App\Models\Shift;
use App\Models\Testimonial;
use App\Models\Visit;
use App\Models\GroomingOrder;
use App\Services\AdmissionService;
use App\Services\GroomingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class OwnerController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $pets = $user->pets()->latest()->get();
        $petIds = $pets->pluck('id');

        $active = Visit::with('pet', 'doctor', 'boarding.room')->whereIn('pet_id', $petIds)->active()->latest()->get();
        $history = Visit::with('pet', 'doctor', 'invoice', 'medicalRecord')->whereIn('pet_id', $petIds)
            ->whereIn('status', ['completed', 'cancelled'])->latest()->take(15)->get();

        $reviewed = Testimonial::where('user_id', $user->id)->pluck('visit_id')->filter()->all();
        $canReview = Visit::with('pet')->whereIn('pet_id', $petIds)->where('status', 'completed')
            ->whereNotIn('id', $reviewed)->latest()->get();

        return view('owner.dashboard', [
            'user' => $user,
            'pets' => $pets,
            'active' => $active,
            'history' => $history,
            'canReview' => $canReview,
            'myTestimonials' => Testimonial::where('user_id', $user->id)->latest()->take(5)->get(),
            'notices' => $user->notices()->latest()->take(8)->get(),
            'stay' => Boarding::with('pet', 'room')->whereIn('pet_id', $petIds)->where('status', 'active')->get(),
            'doctorsToday' => Shift::with('user')->whereDate('shift_date', today())
                ->whereHas('user', fn ($q) => $q->where('role', 'doctor'))->get(),
            'waitingClinic' => Visit::today()->where('service_type', 'clinic')->where('status', 'waiting')->count(),
            'waitingBoarding' => Visit::today()->where('service_type', 'boarding')->where('status', 'waiting')->count(),
            'waitingGrooming' => GroomingOrder::whereIn('status', ['waiting', 'called'])->count(),
            'guestGrooming' => GroomingOrder::with('pet', 'boarding.room')->whereIn('pet_id', $petIds)->whereNotNull('boarding_id')
                ->where(fn ($q) => $q->whereIn('status', GroomingOrder::OPEN)->orWhereDate('finished_at', today()))->latest()->get(),
        ]);
    }

    /** JSON ringan untuk auto-refresh status antrean + notifikasi "dipanggil" */
    public function poll(Request $request)
    {
        $user = $request->user();
        $visits = Visit::with('pet', 'doctor')->whereIn('pet_id', $user->pets()->pluck('id'))->active()->get()
            ->map(fn (Visit $v) => [
                'id' => $v->id, 'queue' => $v->queueLabel(), 'pet' => $v->pet->name,
                'service' => $v->serviceLabel(), 'status' => $v->status, 'label' => $v->statusLabel(),
                'color' => $v->statusColor(), 'ahead' => $v->aheadCount(), 'doctor' => $v->doctor?->name,
            ]);

        $notices = $user->notices()->whereNull('read_at')->latest()->take(10)->get()
            ->map(fn (Notice $n) => ['id' => $n->id, 'type' => $n->type, 'title' => $n->title, 'body' => $n->body, 'url' => $n->url]);

        return response()->json([
            'visits' => $visits,
            'notices' => $notices,
            'waiting_clinic' => Visit::today()->where('service_type', 'clinic')->where('status', 'waiting')->count(),
            'waiting_boarding' => Visit::today()->where('service_type', 'boarding')->where('status', 'waiting')->count(),
            'waiting_grooming' => GroomingOrder::whereIn('status', ['waiting', 'called'])->count(),
        ]);
    }

    public function readNotices(Request $request)
    {
        $request->user()->notices()->whereNull('read_at')
            ->when($request->filled('ids'), fn ($q) => $q->whereIn('id', (array) $request->input('ids')))
            ->update(['read_at' => now()]);

        return $request->expectsJson() ? response()->json(['ok' => true]) : back();
    }

    public function storePet(Request $request)
    {
        $data = $this->validatePet($request);
        $data['owner_id'] = $request->user()->id;
        $data['pass_code'] = Pet::generatePassCode();
        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('pets', 'public');
        }

        $pet = Pet::create($data);

        return redirect()->route('owner.dashboard')->with('success', "{$pet->name} terdaftar! Soulmate Pass: {$pet->pass_code}");
    }

    public function updatePet(Request $request, Pet $pet)
    {
        abort_unless($pet->owner_id === $request->user()->id, 403);
        $data = $this->validatePet($request);
        if ($request->hasFile('photo')) {
            if ($pet->photo) {
                Storage::disk('public')->delete($pet->photo);
            }
            $data['photo'] = $request->file('photo')->store('pets', 'public');
        }
        $pet->update($data);

        return back()->with('success', "Data {$pet->name} diperbarui.");
    }

    public function destroyPet(Request $request, Pet $pet)
    {
        abort_unless($pet->owner_id === $request->user()->id, 403);
        if ($pet->visits()->exists()) {
            return back()->withErrors(['pet' => "{$pet->name} punya riwayat kunjungan sehingga tidak bisa dihapus (demi rekam medis)."]);
        }
        if ($pet->photo) {
            Storage::disk('public')->delete($pet->photo);
        }
        $pet->delete();

        return back()->with('success', 'Data hewan dihapus.');
    }

    public function pass(Request $request, Pet $pet)
    {
        abort_unless($pet->owner_id === $request->user()->id, 403);
        $pet->load('owner');

        return view('owner.pass', compact('pet'));
    }

    public function storeQueue(Request $request, AdmissionService $admission, GroomingService $grooming)
    {
        $data = $request->validate([
            'pet_id' => 'required|exists:pets,id',
            'service_type' => 'required|in:clinic,boarding,grooming',
            'request_note' => 'nullable|string|max:300',
            'complaint' => 'required_if:service_type,clinic|nullable|string|max:500',
            'check_in_date' => 'required_if:service_type,boarding|nullable|date|after_or_equal:today',
            'check_out_date' => 'required_if:service_type,boarding|nullable|date|after:check_in_date',
            'diet_note' => 'nullable|string|max:300',
            'belongings' => 'nullable|string|max:300',
        ], ['complaint.required_if' => 'Keluhan wajib diisi untuk pemeriksaan.', 'check_out_date.after' => 'Tanggal keluar harus setelah tanggal masuk.']);

        $pet = Pet::where('owner_id', $request->user()->id)->findOrFail($data['pet_id']);

        if ($data['service_type'] === 'grooming') {
            $order = $grooming->request($pet, $request->user(), $data['request_note'] ?? null, 'online');

            return redirect()->route('owner.dashboard')->with('success', $order->isGuest()
                ? "Permintaan grooming untuk {$pet->name} (tamu boarding) terkirim. Biayanya masuk tagihan boarding."
                : "Nomor antrean grooming {$order->visit->queueLabel()} untuk {$pet->name}. Tunggu hingga dipanggil.");
        }

        $visit = $admission->createVisit($pet, $data['service_type'], $data['complaint'] ?? null, 'online', $request->user()->id, $data);

        return redirect()->route('owner.dashboard')
            ->with('success', "Nomor antreanmu {$visit->queueLabel()} untuk {$pet->name}. Tunggu hingga dipanggil — layar ini akan memberi tahu.");
    }

    public function cancelQueue(Request $request, Visit $visit)
    {
        abort_unless($visit->pet->owner_id === $request->user()->id, 403);
        if ($visit->status !== 'waiting') {
            return back()->withErrors(['queue' => 'Antrean yang sudah dipanggil tidak bisa dibatalkan sendiri. Hubungi petugas.']);
        }
        $visit->update(['status' => 'cancelled']);
        GroomingOrder::where('visit_id', $visit->id)->where('status', 'waiting')->update(['status' => 'cancelled']);
        Boarding::where('visit_id', $visit->id)->where('status', 'reserved')->update(['status' => 'cancelled']);

        return back()->with('success', "Antrean {$visit->queueLabel()} dibatalkan.");
    }

    public function showVisit(Request $request, Visit $visit)
    {
        abort_unless($visit->pet->owner_id === $request->user()->id, 403);
        $visit->load('pet', 'doctor', 'medicalRecord.doctor', 'prescriptions', 'services', 'invoice', 'boarding.room');

        return view('owner.visit', compact('visit'));
    }

    public function showBoarding(Request $request, Boarding $boarding)
    {
        abort_unless($boarding->pet->owner_id === $request->user()->id, 403);
        $boarding->load('pet', 'room', 'logs.recorder', 'groomingOrders.services');

        return view('owner.boarding', compact('boarding'));
    }

    public function storeTestimonial(Request $request)
    {
        $data = $request->validate([
            'visit_id' => 'required|exists:visits,id',
            'rating' => 'required|integer|between:1,5',
            'comment' => 'required|string|min:5|max:500',
        ]);

        $visit = Visit::with('pet')->findOrFail($data['visit_id']);
        abort_unless($visit->pet->owner_id === $request->user()->id && $visit->status === 'completed', 403, 'Testimoni hanya untuk kunjungan yang sudah selesai.');

        if (Testimonial::where('user_id', $request->user()->id)->where('visit_id', $visit->id)->exists()) {
            return back()->withErrors(['comment' => 'Kamu sudah memberi testimoni untuk kunjungan ini.']);
        }

        Testimonial::create([
            'user_id' => $request->user()->id, 'visit_id' => $visit->id,
            'rating' => $data['rating'], 'comment' => $data['comment'], 'is_published' => true,
        ]);

        return back()->with('success', 'Terima kasih! Testimonimu tampil di beranda Pawlune.');
    }

    private function validatePet(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:60',
            'species' => ['required', Rule::in(array_keys(config('catcare.species')))],
            'breed' => 'nullable|string|max:60',
            'gender' => 'required|in:jantan,betina',
            'birth_date' => 'nullable|date|before_or_equal:today',
            'temperament' => 'required|in:tenang,cemas,agresif',
            'allergy_note' => 'nullable|string|max:300',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);
        $data['breed'] = ($data['breed'] ?? null) ?: 'Domestik';

        return $data;
    }
}
