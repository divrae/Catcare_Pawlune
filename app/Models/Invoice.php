<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number', 'visit_id', 'cashier_id', 'total_medical', 'total_pharmacy', 'total_boarding',
        'total_grooming', 'total_other', 'discount', 'grand_total', 'payment_method', 'amount_paid', 'change_amount',
        'bank_name', 'reference_no', 'paid_at',
    ];

    public const METHODS = ['cash' => 'Tunai', 'qris' => 'QRIS', 'transfer' => 'Transfer'];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime'];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function methodLabel(): string
    {
        return self::METHODS[$this->payment_method] ?? $this->payment_method;
    }
}
