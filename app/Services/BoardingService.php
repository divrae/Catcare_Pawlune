<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Boarding;
use App\Models\Notice;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BoardingService
{
    public function checkIn(Boarding $boarding, int $roomId, User $by, array $extra = []): Boarding
    {
        return DB::transaction(function () use ($boarding, $roomId, $by, $extra) {
            $boarding = Boarding::lockForUpdate()->findOrFail($boarding->id);
            if ($boarding->status !== 'reserved') {
                throw ValidationException::withMessages(['room_id' => 'Boarding ini sudah check-in / selesai.']);
            }

            $room = Room::with('tariff', 'currentStay')->findOrFail($roomId);
            if ($room->is_maintenance) {
                throw ValidationException::withMessages(['room_id' => "Kamar {$room->room_number} sedang maintenance."]);
            }
            if ($room->currentStay) {
                throw ValidationException::withMessages(['room_id' => "Kamar {$room->room_number} sedang terisi."]);
            }
            if ($room->pricePerDay() <= 0) {
                throw ValidationException::withMessages(['room_id' => "Tarif kamar {$room->room_number} belum diatur oleh admin."]);
            }

            $out = $boarding->check_out_date;
            if ($out->lte(today())) {
                $out = today()->addDay();
            }

            $boarding->update([
                'room_id' => $room->id,
                'status' => 'active',
                'check_in_date' => today(),
                'check_out_date' => $out,
                'checked_in_at' => now(),
                'rate_per_day' => $room->pricePerDay(),
                'diet_note' => $extra['diet_note'] ?? $boarding->diet_note,
                'belongings' => $extra['belongings'] ?? $boarding->belongings,
                'admitted_by' => $by->id,
            ]);

            $boarding->visit->update(['status' => 'in_boarding']);

            Notice::send($boarding->pet->owner_id, "{$boarding->pet->name} sudah check-in",
                "Menempati kamar {$room->room_number}. Pantau kondisi harian di dashboard.", 'boarding', $boarding->visit_id);
            ActivityLog::record('boarding_in', "{$boarding->pet->name} check-in kamar {$room->room_number}", $by->id);

            return $boarding->fresh();
        });
    }

    public function checkOut(Boarding $boarding, User $by): Boarding
    {
        return DB::transaction(function () use ($boarding, $by) {
            $boarding = Boarding::lockForUpdate()->findOrFail($boarding->id);
            if ($boarding->status !== 'active') {
                throw ValidationException::withMessages(['boarding' => 'Hanya boarding aktif yang bisa check-out.']);
            }

            $openGrooming = $boarding->groomingOrders()->whereIn('status', ['waiting', 'called', 'in_progress'])->count();
            if ($openGrooming) {
                throw ValidationException::withMessages(['boarding' => 'Masih ada order grooming yang belum selesai. Selesaikan/batalkan dulu sebelum check-out.']);
            }

            $boarding->update([
                'status' => 'finished',
                'checked_out_at' => now(),
                'check_out_date' => today(),
            ]);
            $boarding->visit->update(['status' => 'awaiting_payment']);

            Notice::send($boarding->pet->owner_id, "{$boarding->pet->name} siap dijemput",
                'Silakan selesaikan pembayaran di kasir. Total ' . $boarding->billedDays() . ' hari menginap.', 'boarding', $boarding->visit_id);
            ActivityLog::record('boarding_out', "{$boarding->pet->name} check-out ({$boarding->billedDays()} hari)", $by->id);

            return $boarding->fresh();
        });
    }
}
