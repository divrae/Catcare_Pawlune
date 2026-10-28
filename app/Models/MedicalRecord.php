<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicalRecord extends Model
{
    protected $fillable = [
        'visit_id', 'doctor_id', 'anamnesis', 'diagnosis', 'treatment_note',
        'weight_kg', 'temperature_c', 'follow_up_date', 'referred_to_boarding',
    ];

    protected function casts(): array
    {
        return ['follow_up_date' => 'date', 'referred_to_boarding' => 'boolean'];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }
}
