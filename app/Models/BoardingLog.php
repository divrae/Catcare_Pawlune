<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardingLog extends Model
{
    protected $fillable = [
        'boarding_id', 'logged_at', 'meal', 'food_note', 'temperature_c', 'health_status',
        'stool', 'medicine_given', 'note', 'recorded_by',
    ];

    public const MEAL = ['habis' => 'Habis', 'sebagian' => 'Sebagian', 'tidak_makan' => 'Tidak makan'];
    public const HEALTH = ['sehat' => 'Sehat', 'perhatian' => 'Perlu perhatian', 'sakit' => 'Sakit'];
    public const STOOL = ['normal' => 'Normal', 'lembek' => 'Lembek', 'diare' => 'Diare', 'belum' => 'Belum BAB'];

    protected function casts(): array
    {
        return ['logged_at' => 'datetime', 'medicine_given' => 'boolean'];
    }

    public function boarding(): BelongsTo
    {
        return $this->belongsTo(Boarding::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function healthColor(): string
    {
        return match ($this->health_status) {
            'sehat' => '#B9F6CA', 'perhatian' => '#FEF08A', default => '#FF8FA3',
        };
    }
}
