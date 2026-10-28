<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Boarding;
use App\Models\Medicine;
use App\Models\Notice;
use App\Models\Tariff;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Simpan hasil pemeriksaan dokter: rekam medis + tindakan (tarif) + resep (obat) + rujukan boarding.
 * Resep & tindakan otomatis menjadi tagihan di kasir.
 */
class ExamService
{
    public function save(Visit $visit, User $doctor, array $data): Visit
    {
        return DB::transaction(function () use ($visit, $doctor, $data) {
            $visit = Visit::lockForUpdate()->findOrFail($visit->id);

            if (! in_array($visit->status, ['in_treatment', 'awaiting_payment', 'in_boarding'], true) || $visit->payment_status === 'paid') {
                throw ValidationException::withMessages(['diagnosis' => 'Kunjungan ini tidak bisa diperiksa/diubah lagi (status: ' . $visit->statusLabel() . ').']);
            }

            $record = $visit->medicalRecord()->updateOrCreate(['visit_id' => $visit->id], [
                'doctor_id' => $doctor->id,
                'anamnesis' => $data['anamnesis'] ?? null,
                'diagnosis' => $data['diagnosis'],
                'treatment_note' => $data['treatment_note'] ?? null,
                'weight_kg' => $data['weight_kg'] ?? null,
                'temperature_c' => $data['temperature_c'] ?? null,
                'follow_up_date' => $data['follow_up_date'] ?? null,
                'referred_to_boarding' => ! empty($data['refer_boarding']),
            ]);

            // --- Tindakan / layanan dari tarif ---
            $visit->services()->where('added_by_role', 'doctor')->delete();
            foreach ($data['services'] ?? [] as $row) {
                if (empty($row['tariff_id'])) {
                    continue;
                }
                $tariff = Tariff::active()->find($row['tariff_id']);
                if (! $tariff) {
                    throw ValidationException::withMessages(['services' => 'Ada tarif yang tidak valid / nonaktif.']);
                }
                $qty = max(1, (int) ($row['qty'] ?? 1));
                $visit->services()->create([
                    'tariff_id' => $tariff->id, 'name' => $tariff->name, 'category' => $tariff->category,
                    'qty' => $qty, 'unit_price' => $tariff->price, 'subtotal' => $tariff->price * $qty,
                    'added_by_role' => 'doctor',
                ]);
            }

            // --- Resep obat (harga & kategori dari master obat) ---
            $visit->prescriptions()->delete();
            $needed = [];
            foreach ($data['prescriptions'] ?? [] as $row) {
                if (empty($row['medicine_id'])) {
                    continue;
                }
                $med = Medicine::find($row['medicine_id']);
                $qty = max(1, (int) ($row['qty'] ?? 1));
                if (! $med || ! $med->is_active) {
                    throw ValidationException::withMessages(['prescriptions' => 'Ada obat yang tidak valid / nonaktif.']);
                }
                if ($med->isExpired()) {
                    throw ValidationException::withMessages(['prescriptions' => "Obat {$med->name} sudah kedaluwarsa dan tidak bisa diresepkan."]);
                }
                $needed[$med->id] = ($needed[$med->id] ?? 0) + $qty;
                if ($needed[$med->id] > $med->stock) {
                    throw ValidationException::withMessages(['prescriptions' => "Stok {$med->name} tidak cukup (sisa {$med->stock} {$med->unit})."]);
                }
                $visit->prescriptions()->create([
                    'medical_record_id' => $record->id,
                    'medicine_id' => $med->id, 'medicine_name' => $med->name, 'category' => $med->category,
                    'qty' => $qty, 'unit_price' => $med->price, 'subtotal' => $med->price * $qty,
                    'dosage' => $row['dosage'] ?? null, 'instruction' => $row['instruction'] ?? null,
                ]);
            }

            // --- Rujukan boarding (rawat inap) ---
            $boarding = Boarding::where('visit_id', $visit->id)->latest('id')->first();
            if (! empty($data['refer_boarding'])) {
                if (! $boarding) {
                    Boarding::create([
                        'visit_id' => $visit->id, 'pet_id' => $visit->pet_id, 'status' => 'reserved',
                        'is_referral' => true, 'referral_note' => $data['referral_note'] ?? $data['diagnosis'],
                        'check_in_date' => today(),
                        'check_out_date' => $data['boarding_until'] ?? today()->addDay(),
                    ]);
                    Notice::send($visit->pet->owner_id, "{$visit->pet->name} dirujuk menginap",
                        'Dokter merekomendasikan rawat inap di Boarding. Tim kami akan menyiapkan kamar.', 'boarding', $visit->id);
                } elseif ($boarding->status === 'reserved') {
                    $boarding->update(['referral_note' => $data['referral_note'] ?? $boarding->referral_note, 'check_out_date' => $data['boarding_until'] ?? $boarding->check_out_date]);
                }
            } elseif ($boarding && $boarding->is_referral && $boarding->status === 'reserved') {
                $boarding->update(['status' => 'cancelled']);
                $boarding = null;
            }

            $boarding = Boarding::where('visit_id', $visit->id)->whereIn('status', ['reserved', 'active'])->exists();
            $visit->update(['status' => $boarding ? 'in_boarding' : 'awaiting_payment']);

            ActivityLog::record('exam', "Pemeriksaan {$visit->pet->name} ({$visit->queueLabel()}) disimpan oleh {$doctor->name}", $doctor->id);

            return $visit->fresh();
        });
    }
}
