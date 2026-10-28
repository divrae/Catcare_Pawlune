<?php

namespace App\Http\Controllers;

use App\Models\GroomingOrder;
use App\Models\Pet;
use App\Models\Tariff;
use App\Services\AdmissionService;
use App\Services\GroomingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GroomingController extends Controller
{
    public function index(Request $request)
    {
        return view('grooming.index', $this->queueData() + [
            'shift' => $request->user()->todayShift(),
            'species' => config('catcare.species'),
        ]);
    }

    public function queueFragment()
    {
        return view('grooming._queue', $this->queueData());
    }

    private function queueData(): array
    {
        $base = fn () => GroomingOrder::with('pet.owner', 'visit', 'boarding.room', 'groomer')
            ->where(fn ($q) => $q->whereHas('visit', fn ($v) => $v->whereDate('queue_date', today()))
                ->orWhereIn('status', GroomingOrder::OPEN));

        return [
            'open' => $base()->whereIn('status', GroomingOrder::OPEN)->orderByRaw("CASE status WHEN 'in_progress' THEN 0 WHEN 'called' THEN 1 ELSE 2 END")->orderBy('id')->get(),
            'done' => GroomingOrder::with('pet.owner', 'visit', 'boarding.room', 'groomer', 'services')
                ->where('status', 'done')->whereDate('finished_at', today())->latest('finished_at')->get(),
        ];
    }

    public function walkIn(Request $request, AdmissionService $admission, GroomingService $grooming)
    {
        $data = $request->validate([
            'pass_code' => 'nullable|string|max:20',
            'owner_name' => 'required_without:pass_code|nullable|string|max:100',
            'owner_phone' => 'required_without:pass_code|nullable|string|max:20',
            'pet_name' => 'required_without:pass_code|nullable|string|max:60',
            'species' => ['nullable', Rule::in(array_keys(config('catcare.species')))],
            'breed' => 'nullable|string|max:60',
            'gender' => 'nullable|in:jantan,betina',
            'request_note' => 'nullable|string|max:300',
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

        $order = $grooming->request($pet, $request->user(), $data['request_note'] ?? null, 'walk_in');

        return redirect()->route('grooming.index')->with('success', "{$pet->name} masuk antrean grooming ({$order->label()}).");
    }

    public function call(Request $request, GroomingOrder $order, GroomingService $grooming)
    {
        $grooming->call($order, $request->user());

        return back()->with('success', "{$order->pet->name} dipanggil.");
    }

    public function start(Request $request, GroomingOrder $order, GroomingService $grooming)
    {
        $data = $request->validate(['coat_condition' => 'nullable|string|max:150']);
        $grooming->start($order, $request->user(), $data['coat_condition'] ?? null);

        return redirect()->route('grooming.work', $order)->with('success', 'Grooming dimulai.');
    }

    public function work(GroomingOrder $order)
    {
        $order->load('pet.owner', 'visit', 'boarding.room', 'groomer', 'services');

        return view('grooming.work', [
            'order' => $order,
            'tariffs' => Tariff::active()->where('category', 'grooming')->orderBy('price')->get(),
            'selected' => $order->services->pluck('qty', 'tariff_id'),
            'history' => GroomingOrder::with('groomer', 'services')->where('pet_id', $order->pet_id)
                ->where('status', 'done')->where('id', '!=', $order->id)->latest('finished_at')->take(5)->get(),
        ]);
    }

    public function finish(Request $request, GroomingOrder $order, GroomingService $grooming)
    {
        $data = $request->validate([
            'items' => 'required|array',
            'items.*' => 'nullable|integer|min:0|max:99',
            'result_note' => 'nullable|string|max:600',
        ]);
        $grooming->finish($order, $request->user(), $data['items'], $data['result_note'] ?? null);

        return redirect()->route('grooming.index')->with('success', $order->pet->name . ($order->isGuest()
            ? ' selesai digrooming. Biaya masuk ke tagihan boarding.' : ' selesai digrooming. Pemilik diarahkan ke kasir.'));
    }

    public function cancel(Request $request, GroomingOrder $order, GroomingService $grooming)
    {
        $grooming->cancel($order, $request->user());

        return back()->with('success', 'Order grooming dibatalkan.');
    }

    public function history(Request $request)
    {
        $from = $request->date('from') ?? today()->subDays(6);
        $to = $request->date('to') ?? today();
        $orders = GroomingOrder::with('pet.owner', 'groomer', 'services', 'visit', 'boarding.room')
            ->where('status', 'done')->whereDate('finished_at', '>=', $from)->whereDate('finished_at', '<=', $to)->latest('finished_at')->get();

        return view('grooming.history', [
            'orders' => $orders, 'from' => $from, 'to' => $to,
            'total' => (float) $orders->sum(fn ($o) => $o->total()),
        ]);
    }
}
