<?php

namespace App\Http\Controllers;

use App\Models\Invoice;

class ReceiptController extends Controller
{
    public function show(Invoice $invoice)
    {
        $user = auth()->user();
        $invoice->load('items', 'cashier', 'visit.pet.owner', 'visit.medicalRecord.doctor', 'visit.boarding.room');

        $allowed = $user->hasRole('cashier', 'admin')
            || ($user->role === 'owner' && $invoice->visit->pet->owner_id === $user->id);
        abort_unless($allowed, 403, 'Struk ini bukan milikmu.');

        return view('pos.receipt', compact('invoice'));
    }
}
