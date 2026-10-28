<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitService extends Model
{
    protected $fillable = ['visit_id', 'tariff_id', 'name', 'category', 'qty', 'unit_price', 'subtotal', 'added_by_role', 'grooming_order_id'];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function tariff(): BelongsTo
    {
        return $this->belongsTo(Tariff::class);
    }
}
