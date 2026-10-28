<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tariff extends Model
{
    protected $fillable = ['code', 'name', 'category', 'unit', 'price', 'is_active'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function rooms(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function categoryLabel(): string
    {
        return config('catcare.tariff_categories.' . $this->category, $this->category);
    }

    public static function nextCode(): string
    {
        $n = (static::max('id') ?? 0) + 1;
        do {
            $code = 'TRF-' . str_pad((string) $n++, 3, '0', STR_PAD_LEFT);
        } while (static::where('code', $code)->exists());

        return $code;
    }
}
