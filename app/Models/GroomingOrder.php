<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroomingOrder extends Model
{
    protected $fillable = [
        'visit_id', 'pet_id', 'boarding_id', 'groomer_id', 'status', 'request_note',
        'coat_condition', 'result_note', 'started_at', 'finished_at',
    ];

    public const STATUS = [
        'waiting' => ['label' => 'Menunggu', 'color' => '#FEF08A'],
        'called' => ['label' => 'Dipanggil', 'color' => '#FFC8DD'],
        'in_progress' => ['label' => 'Sedang Digrooming', 'color' => '#A2D2FF'],
        'done' => ['label' => 'Selesai', 'color' => '#B9F6CA'],
        'cancelled' => ['label' => 'Dibatalkan', 'color' => '#E5E5E5'],
    ];

    public const OPEN = ['waiting', 'called', 'in_progress'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'finished_at' => 'datetime'];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function boarding(): BelongsTo
    {
        return $this->belongsTo(Boarding::class);
    }

    public function groomer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'groomer_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(VisitService::class, 'grooming_order_id');
    }

    public function isGuest(): bool
    {
        return $this->boarding_id !== null;
    }

    public function statusLabel(): string
    {
        return self::STATUS[$this->status]['label'] ?? $this->status;
    }

    public function statusColor(): string
    {
        return self::STATUS[$this->status]['color'] ?? '#fff';
    }

    public function total(): float
    {
        return (float) $this->services->sum('subtotal');
    }

    /** "G-003" untuk antrean standalone, "Tamu boarding" untuk tamu */
    public function label(): string
    {
        return $this->isGuest() ? 'Suite ' . ($this->boarding?->room?->room_number ?? '-') : $this->visit->queueLabel();
    }
}
