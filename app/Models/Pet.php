<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Pet extends Model
{
    protected $fillable = [
        'owner_id', 'pass_code', 'name', 'species', 'breed', 'gender',
        'birth_date', 'temperament', 'photo', 'allergy_note',
    ];

    protected function casts(): array
    {
        return ['birth_date' => 'date'];
    }

    public static function generatePassCode(string $name = ''): string
    {
        do {
            $code = 'PWL-' . strtoupper(Str::random(6));
        } while (static::where('pass_code', $code)->exists());

        return $code;
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function boardings(): HasMany
    {
        return $this->hasMany(Boarding::class);
    }

    public function speciesLabel(): string
    {
        return config('catcare.species.' . $this->species, ucfirst($this->species));
    }

    public function emoji(): string
    {
        return match ($this->species) {
            'kucing' => '🐱', 'anjing' => '🐶', 'kelinci' => '🐰', 'burung' => '🐦', 'hamster' => '🐹', default => '🐾',
        };
    }

    public function photoUrl(): ?string
    {
        return $this->photo ? asset('storage/' . $this->photo) : null;
    }

    public function ageLabel(): string
    {
        if (! $this->birth_date) {
            return 'Umur belum diisi';
        }
        $d = $this->birth_date->diff(now());
        if ($d->y > 0) {
            return $d->y . ' th ' . $d->m . ' bln';
        }
        return $d->m . ' bln';
    }

    /** SVG QR untuk Soulmate Pass */
    public function qrSvg(int $size = 150): string
    {
        return (string) \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
            ->size($size)->margin(0)->errorCorrection('M')->generate($this->pass_code);
    }
}
