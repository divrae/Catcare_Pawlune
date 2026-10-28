<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = ['invoice_id', 'department', 'description', 'qty', 'unit_price', 'subtotal'];
}
