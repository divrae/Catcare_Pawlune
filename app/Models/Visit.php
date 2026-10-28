<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Visit extends Model
{
    protected $fillable = [
        'visit_code', 'pet_id', 'service_type', 'source', 'queue_date', 'queue_number', 'complaint',
        'status', 'doctor_id', 'created_by', 'called_at', 'started_at', 'finished_at', 'payment_status',
    ];

    public const STATUS = [
        'waiting' => ['label' => 'Menunggu', 'color' => '#FEF08A'],
        'called' => ['label' => 'Dipanggil', 'color' => '#FFC8DD'],
        'in_treatment' => ['label' => 'Sedang Diperiksa', 'color' => '#A2D2FF'],
        'in_boarding' => ['label' => 'Menginap', 'color' => '#CDB4DB'],
        'awaiting_payment' => ['label' => 'Menunggu Bayar', 'color' => '#FFD6A5'],
        'completed' => ['label' => 'Selesai', 'color' => '#B9F6CA'],
        'cancelled' => ['label' => 'Dibatalkan', 'color' => '#E5E5E5'],
    ];

    /** Status "masih aktif" untuk owner (belum selesai/batal) */
    public const ACTIVE = ['waiting', 'called', 'in_treatment', 'in_boarding', 'awaiting_payment'];

    protected function casts(): array
    {
        return [
            'queue_date' => 'date',
            'called_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function medicalRecord(): HasOne
    {
        return $this->hasOne(MedicalRecord::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(VisitService::class);
    }

    public function boarding(): HasOne
    {
        return $this->hasOne(Boarding::class)->latestOfMany();
    }

    public function groomingOrders(): HasMany
    {
        return $this->hasMany(GroomingOrder::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function scopeToday($q)
    {
        return $q->whereDate('queue_date', today());
    }

    public function scopeActive($q)
    {
        return $q->whereIn('status', self::ACTIVE);
    }

    public function statusLabel(): string
    {
        if ($this->service_type === 'grooming' && $this->status === 'in_treatment') {
            return 'Sedang Digrooming';
        }
        return self::STATUS[$this->status]['label'] ?? $this->status;
    }

    public function statusColor(): string
    {
        return self::STATUS[$this->status]['color'] ?? '#fff';
    }

    public function queueLabel(): string
    {
        return (['boarding' => 'B', 'grooming' => 'G'][$this->service_type] ?? 'K') . '-' . str_pad((string) $this->queue_number, 3, '0', STR_PAD_LEFT);
    }

    public function serviceLabel(): string
    {
        return ['boarding' => 'Boarding / Titip', 'grooming' => 'Grooming / Spa'][$this->service_type] ?? 'Periksa Dokter';
    }

    /** Berapa antrean menunggu di depan antrean ini (hari & layanan yang sama) */
    public function aheadCount(): int
    {
        if ($this->status !== 'waiting') {
            return 0;
        }
        return static::whereDate('queue_date', $this->queue_date)
            ->where('service_type', $this->service_type)
            ->where('status', 'waiting')
            ->where('queue_number', '<', $this->queue_number)
            ->count();
    }
}
