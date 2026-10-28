<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Tariff;
use App\Models\Visit;
use App\Models\VisitService;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PosController extends Controller
{
    public function index(Request $request, BillingService $billing)
    {
        $queue = Visit::with('pet.owner', 'boarding')->whereIn('status', ['awaiting_payment', 'in_boarding'])
            ->where('payment_status', 'unpaid')->orderByRaw("CASE status WHEN 'awaiting_payment' THEN 0 ELSE 1 END")->orderBy('updated_at')->get();

        $selected = null;
        $summary = null;
        if ($request->filled('visit')) {
            $selected = Visit::with('pet.owner', 'medicalRecord.doctor', 'boarding.room')->find($request->query('visit'));
        } elseif ($first = $queue->firstWhere('status', 'awaiting_payment')) {
            $selected = Visit::with('pet.owner', 'medicalRecord.doctor', 'boarding.room')->find($first->id);
        }
        if ($selected && $selected->payment_status === 'unpaid') {
            $summary = $billing->summary($selected);
        } else {
            $selected = null;
        }

        $todayInvoices = Invoice::whereDate('paid_at', today())->get();

        return view('pos.index', [
            'queue' => $queue,
            'selected' => $selected,
            'summary' => $summary,
            'extraTariffs' => Tariff::active()->where('category', '!=', 'boarding')->orderBy('category')->orderBy('name')->get(),
            'today' => [
                'count' => $todayInvoices->count(),
                'total' => (float) $todayInvoices->sum('grand_total'),
                'cash' => (float) $todayInvoices->where('payment_method', 'cash')->sum('grand_total'),
                'qris' => (float) $todayInvoices->where('payment_method', 'qris')->sum('grand_total'),
                'transfer' => (float) $todayInvoices->where('payment_method', 'transfer')->sum('grand_total'),
            ],
            'banks' => config('catcare.payment.banks'),
            'qrisImage' => file_exists(public_path('img/qris.png')) ? asset('img/qris.png') : null,
        ]);
    }

    public function addItem(Request $request, Visit $visit)
    {
        abort_unless($visit->status === 'awaiting_payment' && $visit->payment_status === 'unpaid', 403, 'Tagihan sudah tidak bisa diubah.');
        $data = $request->validate([
            'tariff_id' => ['required', Rule::exists('tariffs', 'id')->where('is_active', true)],
            'qty' => 'required|integer|min:1|max:99',
        ]);
        $tariff = Tariff::findOrFail($data['tariff_id']);
        $visit->services()->create([
            'tariff_id' => $tariff->id, 'name' => $tariff->name, 'category' => $tariff->category,
            'qty' => $data['qty'], 'unit_price' => $tariff->price, 'subtotal' => $tariff->price * $data['qty'],
            'added_by_role' => 'cashier',
        ]);

        return redirect()->route('pos.index', ['visit' => $visit->id])->with('success', "{$tariff->name} ditambahkan ke tagihan.");
    }

    public function removeItem(Visit $visit, VisitService $service)
    {
        abort_unless($service->visit_id === $visit->id && $service->added_by_role === 'cashier', 403, 'Hanya item tambahan kasir yang bisa dihapus.');
        abort_unless($visit->payment_status === 'unpaid', 403);
        $service->delete();

        return redirect()->route('pos.index', ['visit' => $visit->id])->with('success', 'Item dihapus dari tagihan.');
    }

    public function pay(Request $request, Visit $visit, BillingService $billing)
    {
        $data = $request->validate([
            'payment_method' => 'required|in:cash,qris,transfer',
            'discount' => 'nullable|numeric|min:0',
            'amount_paid' => 'nullable|numeric|min:0',
            'bank_name' => 'nullable|string|max:40',
            'reference_no' => 'nullable|string|max:60',
        ]);

        $invoice = $billing->pay($visit, $request->user(), $data);

        return redirect()->route('receipt.show', ['invoice' => $invoice, 'print' => 1])->with('success', 'Pembayaran berhasil dicatat.');
    }

    public function history(Request $request)
    {
        $from = $request->date('from') ?? today();
        $to = $request->date('to') ?? today();

        $invoices = Invoice::with('visit.pet.owner', 'cashier')
            ->whereDate('paid_at', '>=', $from)->whereDate('paid_at', '<=', $to)->latest('paid_at')->get();

        return view('pos.history', [
            'invoices' => $invoices, 'from' => $from, 'to' => $to,
            'sum' => [
                'total' => (float) $invoices->sum('grand_total'),
                'cash' => (float) $invoices->where('payment_method', 'cash')->sum('grand_total'),
                'qris' => (float) $invoices->where('payment_method', 'qris')->sum('grand_total'),
                'transfer' => (float) $invoices->where('payment_method', 'transfer')->sum('grand_total'),
            ],
        ]);
    }
}
