<?php

namespace App\Services;

use App\Models\Boarding;
use App\Models\Pet;
use App\Models\User;
use App\Models\Visit;
use App\Support\Fmt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Semua pendaftaran antrean (online oleh owner maupun walk-in oleh dokter/staff)
 * lewat satu pintu ini supaya nomor antrean & aturannya konsisten.
 */
class AdmissionService
{
    /** Cari owner berdasarkan nomor HP; kalau belum ada buat akun walk-in (tanpa password). */
    public function findOrCreateOwner(string $name, string $phone, ?string $address = null): User
    {
        $phone = Fmt::phone($phone);
        $owner = User::where('phone', $phone)->first();

        if ($owner) {
            if ($owner->role !== 'owner') {
                throw ValidationException::withMessages(['owner_phone' => 'Nomor HP ini dipakai akun staff, gunakan nomor lain.']);
            }
            return $owner;
        }

        return User::create([
            'name' => $name,
            'phone' => $phone,
            'address' => $address,
            'role' => 'owner',
            'password' => null,
            'is_walk_in' => true,
        ]);
    }

    public function findOrCreatePet(User $owner, array $data): Pet
    {
        $pet = $owner->pets()->whereRaw('LOWER(name) = ?', [mb_strtolower($data['pet_name'])])->first();
        if ($pet) {
            return $pet;
        }

        return Pet::create([
            'owner_id' => $owner->id,
            'pass_code' => Pet::generatePassCode(),
            'name' => $data['pet_name'],
            'species' => $data['species'] ?? 'kucing',
            'breed' => ($data['breed'] ?? null) ?: 'Domestik',
            'gender' => $data['gender'] ?? 'jantan',
            'temperament' => $data['temperament'] ?? 'tenang',
        ]);
    }

    /**
     * @param array{check_in_date?:string,check_out_date?:string,diet_note?:?string,belongings?:?string} $boarding
     */
    public function createVisit(Pet $pet, string $service, ?string $complaint, string $source, ?int $createdBy = null, array $boarding = []): Visit
    {
        $this->guardDuplicate($pet, $service);

        return DB::transaction(function () use ($pet, $service, $complaint, $source, $createdBy, $boarding) {
            $number = (int) Visit::whereDate('queue_date', today())
                ->where('service_type', $service)->lockForUpdate()->max('queue_number') + 1;

            $visit = Visit::create([
                'visit_code' => 'VST-' . now()->format('Ymd') . '-' . ($service === 'boarding' ? 'B' : 'K') . str_pad((string) $number, 3, '0', STR_PAD_LEFT),
                'pet_id' => $pet->id,
                'service_type' => $service,
                'source' => $source,
                'queue_date' => today(),
                'queue_number' => $number,
                'complaint' => $complaint,
                'status' => 'waiting',
                'created_by' => $createdBy,
            ]);

            if ($service === 'boarding') {
                $in = $boarding['check_in_date'] ?? today()->toDateString();
                $out = $boarding['check_out_date'] ?? today()->addDay()->toDateString();
                Boarding::create([
                    'visit_id' => $visit->id,
                    'pet_id' => $pet->id,
                    'status' => 'reserved',
                    'check_in_date' => $in,
                    'check_out_date' => $out,
                    'diet_note' => $boarding['diet_note'] ?? null,
                    'belongings' => $boarding['belongings'] ?? null,
                ]);
            }

            return $visit;
        });
    }

    private function guardDuplicate(Pet $pet, string $service): void
    {
        $busy = Visit::where('pet_id', $pet->id)->whereIn('status', ['waiting', 'called', 'in_treatment'])->first();
        if ($busy) {
            throw ValidationException::withMessages([
                'pet_id' => "{$pet->name} masih punya antrean aktif ({$busy->queueLabel()} – {$busy->statusLabel()}).",
            ]);
        }

        $dupe = Visit::where('pet_id', $pet->id)->where('service_type', $service)
            ->whereIn('status', ['in_boarding', 'awaiting_payment'])->first();
        if ($dupe) {
            throw ValidationException::withMessages([
                'pet_id' => "{$pet->name} masih punya layanan yang belum selesai ({$dupe->queueLabel()} – {$dupe->statusLabel()}).",
            ]);
        }
    }
}
