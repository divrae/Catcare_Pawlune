<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medicine extends Model
{
    protected $fillable = ['code', 'name', 'category', 'unit', 'price', 'stock', 'min_stock', 'expired_date', 'is_active'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'expired_date' => 'date', 'is_active' => 'boolean'];
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /** Obat yang boleh diresepkan: aktif, ada stok, belum kedaluwarsa */
    public function scopePrescribable($q)
    {
        return $q->where('is_active', true)->where('stock', '>', 0)
            ->where(fn ($w) => $w->whereNull('expired_date')->orWhereDate('expired_date', '>=', today()));
    }

    public function isExpired(): bool
    {
        return $this->expired_date && $this->expired_date->lt(today());
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expired_date && ! $this->isExpired() && $this->expired_date->lte(today()->addDays($days));
    }

    public function isLow(): bool
    {
        return $this->stock <= $this->min_stock;
    }

    /** Kode tunggal obat+batch, contoh: OBT-0007 */
    public static function nextCode(): string
    {
        $n = (static::max('id') ?? 0) + 1;
        do {
            $code = 'OBT-' . str_pad((string) $n++, 4, '0', STR_PAD_LEFT);
        } while (static::where('code', $code)->exists());

        return $code;
    }

    /** Catat mutasi stok dan perbarui saldo. Panggil di dalam transaksi. */
    public function moveStock(string $type, int $qty, ?string $reference = null, ?string $note = null, ?int $userId = null): StockMovement
    {
        $qty = abs($qty);
        $this->stock = match ($type) {
            'in' => $this->stock + $qty,
            'out' => $this->stock - $qty,
            default => $qty, // adjust: qty = stok baru hasil opname
        };
        $this->save();

        return $this->movements()->create([
            'type' => $type, 'qty' => $qty, 'stock_after' => $this->stock,
            'reference' => $reference, 'note' => $note, 'user_id' => $userId,
        ]);
    }
}
