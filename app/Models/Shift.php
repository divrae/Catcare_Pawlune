<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shift extends Model
{
    protected $fillable = ['user_id', 'shift_date', 'shift_type', 'note'];

    protected function casts(): array
    {
        return ['shift_date' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function label(): string
    {
        return config("catcare.shifts.{$this->shift_type}.label", $this->shift_type);
    }

    public function time(): string
    {
        return config("catcare.shifts.{$this->shift_type}.time", '');
    }

    public function color(): string
    {
        return config("catcare.shifts.{$this->shift_type}.color", '#fff');
    }
}
