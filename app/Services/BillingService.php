<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Medicine;
use App\Models\Notice;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * POS: menggabungkan tarif dokter + obat (resep) + boarding (hari x tarif) menjadi satu tagihan,
 * lalu memproses pembayaran (tunai / QRIS / transfer) dan memotong stok obat.
 */
class BillingService
{
    /** @return Collection<int, array{department:string,description:string,qty:int,unit_price:float,subtotal:float,source:string,id:?int}> */
    public function lines(Visit $visit): Collection
    {
        $lines = collect();

        foreach ($visit->services()->get() as $s) {
            $lines->push([
                'department' => $s->category === 'grooming' ? 'grooming' : ($s->category === 'lainnya' ? 'other' : 'medical'),
                'description' => $s->name, 'qty' => (int) $s->qty,
                'unit_price' => (float) $s->unit_price, 'subtotal' => (float) $s->subtotal,
                'source' => 'service', 'id' => $s->id, 'removable' => $s->added_by_role === 'cashier',
            ]);
        }

        foreach ($visit->prescriptions()->get() as $p) {
            $desc = $p->medicine_name . ($p->category ? " ({$p->category})" : '');
            $lines->push([
                'department' => 'pharmacy', 'description' => $desc, 'qty' => (int) $p->qty,
                'unit_price' => (float) $p->unit_price, 'subtotal' => (float) $p->subtotal,
                'source' => 'prescription', 'id' => $p->id, 'removable' => false,
            ]);
        }

        $b = $visit->boarding()->with('room')->first();
        if ($b && in_array($b->status, ['finished', 'active'], true)) {
            $days = $b->billedDays();
            $lines->push([
                'department' => 'boarding',
                'description' => "Boarding kamar {$b->room?->room_number} • {$days} hari" . ($b->status === 'active' ? ' (estimasi)' : ''),
                'qty' => $days, 'unit_price' => (float) $b->rate_per_day, 'subtotal' => $days * (float) $b->rate_per_day,
                'source' => 'boarding', 'id' => $b->id, 'removable' => false,
            ]);
        }

        return $lines;
    }

    public function summary(Visit $visit): array
    {
        $lines = $this->lines($visit);
        $by = fn (string $d) => (float) $lines->where('department', $d)->sum('subtotal');

        $blocked = null;
        if ($visit->status === 'in_boarding') {
            $blocked = 'Hewan masih dalam boarding. Lakukan check-out di menu Boarding dulu agar lama menginap terhitung.';
        } elseif ($visit->groomingOrders()->whereIn('status', ['waiting', 'called', 'in_progress'])->exists()) {
            $blocked = 'Grooming belum selesai. Tunggu groomer menyelesaikan order dulu.';
        } elseif ($visit->status !== 'awaiting_payment') {
            $blocked = 'Kunjungan ini belum/ tidak dalam tahap pembayaran (' . $visit->statusLabel() . ').';
        }

        return [
            'lines' => $lines,
            'medical' => $by('medical'), 'pharmacy' => $by('pharmacy'),
            'boarding' => $by('boarding'), 'grooming' => $by('grooming'), 'other' => $by('other'),
            'subtotal' => (float) $lines->sum('subtotal'),
            'blocked' => $blocked,
        ];
    }

    public function pay(Visit $visit, User $cashier, array $data): Invoice
    {
        return DB::transaction(function () use ($visit, $cashier, $data) {
            $visit = Visit::lockForUpdate()->with('pet.owner')->findOrFail($visit->id);
            $sum = $this->summary($visit);

            if ($sum['blocked'] || $visit->payment_status === 'paid') {
                throw ValidationException::withMessages(['pay' => $sum['blocked'] ?? 'Kunjungan ini sudah dibayar.']);
            }
            if ($sum['lines']->isEmpty() || $sum['subtotal'] <= 0) {
                throw ValidationException::withMessages(['pay' => 'Tagihan kosong. Tambahkan layanan/tarif dulu.']);
            }

            $discount = min((float) ($data['discount'] ?? 0), $sum['subtotal']);
            $total = $sum['subtotal'] - $discount;
            $method = $data['payment_method'];

            $paid = $total;
            $change = 0.0;
            if ($method === 'cash') {
                $paid = (float) ($data['amount_paid'] ?? 0);
                if ($paid < $total) {
                    throw ValidationException::withMessages(['amount_paid' => 'Uang diterima kurang dari total tagihan.']);
                }
                $change = $paid - $total;
            } elseif ($method === 'transfer' && empty($data['reference_no'])) {
                throw ValidationException::withMessages(['reference_no' => 'Isi nomor referensi / 4 digit terakhir bukti transfer.']);
            }

            $number = $this->nextInvoiceNumber();

            // --- potong stok obat (kunci baris supaya aman dari kasir ganda) ---
            $perMedicine = $visit->prescriptions->groupBy('medicine_id')->map(fn ($g) => (int) $g->sum('qty'));
            foreach ($perMedicine as $medicineId => $qty) {
                $med = Medicine::lockForUpdate()->find($medicineId);
                if (! $med || $med->stock < $qty) {
                    $name = $med?->name ?? 'obat';
                    $left = $med?->stock ?? 0;
                    throw ValidationException::withMessages(['pay' => "Stok {$name} tidak cukup (butuh {$qty}, sisa {$left}). Minta dokter/admin menyesuaikan."]);
                }
                $med->moveStock('out', $qty, $number, "Resep {$visit->queueLabel()} - {$visit->pet->name}", $cashier->id);
            }

            $invoice = Invoice::create([
                'invoice_number' => $number,
                'visit_id' => $visit->id,
                'cashier_id' => $cashier->id,
                'total_medical' => $sum['medical'], 'total_pharmacy' => $sum['pharmacy'],
                'total_boarding' => $sum['boarding'], 'total_grooming' => $sum['grooming'], 'total_other' => $sum['other'],
                'discount' => $discount, 'grand_total' => $total,
                'payment_method' => $method, 'amount_paid' => $paid, 'change_amount' => $change,
                'bank_name' => $method === 'transfer' ? ($data['bank_name'] ?? null) : null,
                'reference_no' => $data['reference_no'] ?? null,
                'paid_at' => now(),
            ]);

            foreach ($sum['lines'] as $l) {
                $invoice->items()->create([
                    'department' => $l['department'], 'description' => $l['description'],
                    'qty' => $l['qty'], 'unit_price' => $l['unit_price'], 'subtotal' => $l['subtotal'],
                ]);
            }

            $visit->update(['status' => 'completed', 'payment_status' => 'paid', 'finished_at' => now()]);

            Notice::send($visit->pet->owner_id, 'Pembayaran berhasil ✔',
                "Terima kasih! {$number} — " . rupiah($total) . '. Kamu bisa beri testimoni di dashboard.', 'paid', $visit->id,
                route('receipt.show', $invoice));
            ActivityLog::record('payment', "Pembayaran {$number} ({$invoice->methodLabel()}) " . rupiah($total), $cashier->id);

            return $invoice;
        });
    }

    private function nextInvoiceNumber(): string
    {
        $prefix = 'INV-' . now()->format('Ymd') . '-';
        $n = Invoice::where('invoice_number', 'like', $prefix . '%')->count() + 1;
        do {
            $number = $prefix . str_pad((string) $n++, 4, '0', STR_PAD_LEFT);
        } while (Invoice::where('invoice_number', $number)->exists());

        return $number;
    }
}
