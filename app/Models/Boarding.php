<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Boarding extends Model
{
    protected $fillable = [
        'visit_id', 'pet_id', 'room_id', 'status', 'is_referral', 'referral_note',
        'check_in_date', 'check_out_date', 'checked_in_at', 'checked_out_at',
        'rate_per_day', 'diet_note', 'belongings', 'admitted_by',
    ];

    public const STATUS = [
        'reserved' => ['label' => 'Menunggu Check-in', 'color' => '#FEF08A'],
        'active' => ['label' => 'Sedang Menginap', 'color' => '#A2D2FF'],
        'finished' => ['label' => 'Selesai', 'color' => '#B9F6CA'],
        'cancelled' => ['label' => 'Dibatalkan', 'color' => '#E5E5E5'],
    ];

    protected function casts(): array
    {
        return [
            'check_in_date' => 'date',
            'check_out_date' => 'date',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'is_referral' => 'boolean',
        ];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function admitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admitted_by');
    }

    public function groomingOrders(): HasMany
    {
        return $this->hasMany(GroomingOrder::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(BoardingLog::class)->orderByDesc('logged_at');
    }

    public function statusLabel(): string
    {
        return self::STATUS[$this->status]['label'] ?? $this->status;
    }

    public function statusColor(): string
    {
        return self::STATUS[$this->status]['color'] ?? '#fff';
    }

    /**
     * Jumlah hari menginap yang ditagih (minimal 1 hari).
     * Selesai: dari check-in sampai tanggal check-out realisasi.
     * Belum selesai: estimasi sampai tanggal rencana keluar.
     */
    public function billedDays(): int
    {
        $start = $this->check_in_date->copy()->startOfDay();
        $end = $this->check_out_date->copy()->startOfDay();
        return max(1, (int) $start->diffInDays($end));
    }

    public function total(): float
    {
        return $this->billedDays() * (float) $this->rate_per_day;
    }

    /** Hari ke-berapa sekarang (untuk tampilan) */
    public function dayNumber(): int
    {
        return max(1, (int) $this->check_in_date->copy()->startOfDay()->diffInDays(today()) + 1);
    }

    public function isOverdue(): bool
    {
        return $this->status === 'active' && $this->check_out_date->lt(today());
    }
}
