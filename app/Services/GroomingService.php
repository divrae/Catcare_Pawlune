<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Boarding;
use App\Models\GroomingOrder;
use App\Models\Notice;
use App\Models\Pet;
use App\Models\Tariff;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Grooming: dua jalur.
 *  - Standalone  : hewan datang khusus grooming -> kunjungan sendiri (antrean G-xxx) -> dibayar di kasir.
 *  - Tamu boarding: hewan yang sedang menginap -> order menempel di kunjungan boarding,
 *                   biayanya masuk tagihan boarding dan dibayar saat check-out di kasir.
 */
class GroomingService
{
    public function __construct(private AdmissionService $admission)
    {
    }

    public function request(Pet $pet, ?User $by, ?string $note, string $source = 'online'): GroomingOrder
    {
        $boarding = Boarding::where('pet_id', $pet->id)->where('status', 'active')->first();

        if ($boarding) {
            $open = GroomingOrder::where('boarding_id', $boarding->id)->whereIn('status', GroomingOrder::OPEN)->exists();
            if ($open) {
                throw ValidationException::withMessages(['pet_id' => "{$pet->name} sudah punya order grooming yang belum selesai."]);
            }

            $order = GroomingOrder::create([
                'visit_id' => $boarding->visit_id, 'pet_id' => $pet->id, 'boarding_id' => $boarding->id,
                'status' => 'waiting', 'request_note' => $note,
            ]);
            ActivityLog::record('grooming_req', "Grooming tamu boarding: {$pet->name} (suite {$boarding->room?->room_number})", $by?->id);

            return $order;
        }

        $visit = $this->admission->createVisit($pet, 'grooming', $note ?: 'Grooming', $source, $by?->id);
        $order = GroomingOrder::create([
            'visit_id' => $visit->id, 'pet_id' => $pet->id, 'status' => 'waiting', 'request_note' => $note,
        ]);
        ActivityLog::record('grooming_req', "Antrean grooming {$visit->queueLabel()}: {$pet->name}", $by?->id);

        return $order;
    }

    public function call(GroomingOrder $order, User $groomer): GroomingOrder
    {
        $this->ensure($order, ['waiting']);
        $order->update(['status' => 'called', 'groomer_id' => $groomer->id]);

        if (! $order->isGuest()) {
            $order->visit->update(['status' => 'called', 'called_at' => now()]);
            Notice::send($order->pet->owner_id, "Giliran {$order->pet->name}! ({$order->visit->queueLabel()})",
                'Silakan menuju ruang grooming.', 'called', $order->visit_id);
        } else {
            Notice::send($order->pet->owner_id, "{$order->pet->name} akan segera digrooming",
                'Tim grooming akan menjemput hewanmu dari suite boarding.', 'grooming', $order->visit_id);
        }

        return $order->fresh();
    }

    public function start(GroomingOrder $order, User $groomer, ?string $coat): GroomingOrder
    {
        $this->ensure($order, ['waiting', 'called']);
        $order->update([
            'status' => 'in_progress', 'groomer_id' => $groomer->id,
            'coat_condition' => $coat, 'started_at' => now(),
        ]);
        if (! $order->isGuest()) {
            $order->visit->update(['status' => 'in_treatment']);
        }

        return $order->fresh();
    }

    /** @param array<int,int|string> $items tariff_id => qty */
    public function finish(GroomingOrder $order, User $groomer, array $items, ?string $result): GroomingOrder
    {
        return DB::transaction(function () use ($order, $groomer, $items, $result) {
            $order = GroomingOrder::lockForUpdate()->with('visit', 'pet', 'boarding')->findOrFail($order->id);
            $this->ensure($order, ['in_progress']);

            $items = array_filter($items, fn ($q) => (int) $q > 0);
            if (! $items) {
                throw ValidationException::withMessages(['items' => 'Pilih minimal satu layanan grooming yang dikerjakan.']);
            }

            $order->services()->delete();
            foreach ($items as $tariffId => $qty) {
                $t = Tariff::active()->where('category', 'grooming')->find($tariffId);
                if (! $t) {
                    throw ValidationException::withMessages(['items' => 'Layanan grooming tidak valid / nonaktif.']);
                }
                $qty = min(99, (int) $qty);
                $order->visit->services()->create([
                    'tariff_id' => $t->id, 'name' => $t->name, 'category' => 'grooming', 'qty' => $qty,
                    'unit_price' => $t->price, 'subtotal' => $t->price * $qty,
                    'added_by_role' => 'groomer', 'grooming_order_id' => $order->id,
                ]);
            }

            $order->update(['status' => 'done', 'groomer_id' => $groomer->id, 'result_note' => $result, 'finished_at' => now()]);

            if ($order->isGuest()) {
                Notice::send($order->pet->owner_id, "{$order->pet->name} selesai digrooming ✨",
                    'Biaya grooming otomatis masuk tagihan boarding dan dibayar saat check-out.', 'grooming', $order->visit_id);
            } else {
                $order->visit->update(['status' => 'awaiting_payment']);
                Notice::send($order->pet->owner_id, "{$order->pet->name} selesai digrooming ✨",
                    'Silakan selesaikan pembayaran di kasir.', 'grooming', $order->visit_id);
            }
            ActivityLog::record('grooming_done', "Grooming selesai: {$order->pet->name} (" . rupiah($order->fresh('services')->total()) . ')', $groomer->id);

            return $order->fresh();
        });
    }

    public function cancel(GroomingOrder $order, ?User $by): GroomingOrder
    {
        $this->ensure($order, ['waiting', 'called']);
        $order->update(['status' => 'cancelled']);
        if (! $order->isGuest()) {
            $order->visit->update(['status' => 'cancelled', 'finished_at' => now()]);
        }
        ActivityLog::record('grooming_cancel', "Grooming dibatalkan: {$order->pet->name}", $by?->id);

        return $order->fresh();
    }

    private function ensure(GroomingOrder $order, array $allowed): void
    {
        if (! in_array($order->status, $allowed, true)) {
            throw ValidationException::withMessages(['grooming' => 'Status order grooming sudah berubah (' . $order->statusLabel() . '). Muat ulang halaman.']);
        }
    }
}
