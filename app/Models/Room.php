<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Room extends Model
{
    protected $fillable = ['room_number', 'name', 'room_type', 'tariff_id', 'qr_hash', 'is_maintenance'];

    protected function casts(): array
    {
        return ['is_maintenance' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (Room $room) {
            $room->qr_hash = $room->qr_hash ?: 'RM-' . strtoupper(Str::random(8));
        });
    }

    public function tariff(): BelongsTo
    {
        return $this->belongsTo(Tariff::class);
    }

    public function boardings(): HasMany
    {
        return $this->hasMany(Boarding::class);
    }

    public function currentStay(): HasOne
    {
        return $this->hasOne(Boarding::class)->where('status', 'active')->with('pet.owner');
    }

    public function pricePerDay(): float
    {
        return (float) ($this->tariff->price ?? 0);
    }

    /** available|occupied|maintenance */
    public function state(): string
    {
        if ($this->is_maintenance) {
            return 'maintenance';
        }
        return $this->currentStay ? 'occupied' : 'available';
    }
}
