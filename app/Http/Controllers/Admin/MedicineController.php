<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Medicine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MedicineController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $filter = $request->query('filter');

        $medicines = Medicine::query()
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('name', 'like', "%$q%")->orWhere('code', 'like', "%$q%")->orWhere('category', 'like', "%$q%")))
            ->when($filter === 'low', fn ($w) => $w->whereColumn('stock', '<=', 'min_stock'))
            ->when($filter === 'expiring', fn ($w) => $w->whereNotNull('expired_date')->whereDate('expired_date', '<=', today()->addDays(30)))
            ->orderBy('category')->orderBy('name')->get();

        return view('admin.medicines', [
            'medicines' => $medicines, 'q' => $q, 'filter' => $filter,
            'categories' => config('catcare.medicine_categories'),
            'nextCode' => Medicine::nextCode(),
            'movements' => \App\Models\StockMovement::with('medicine', 'user')->latest()->take(12)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'category' => 'required|string|max:40',
            'unit' => 'required|string|max:20',
            'price' => 'required|numeric|min:0|max:999999999',
            'stock' => 'required|integer|min:0|max:100000',
            'min_stock' => 'required|integer|min:0|max:100000',
            'expired_date' => 'nullable|date',
        ]);

        DB::transaction(function () use ($data, $request, &$med) {
            $stock = $data['stock'];
            $med = Medicine::create(['code' => Medicine::nextCode(), 'is_active' => true] + array_merge($data, ['stock' => 0]));
            if ($stock > 0) {
                $med->moveStock('in', $stock, null, 'Stok awal', $request->user()->id);
            }
        });
        ActivityLog::record('medicine', "Obat baru: {$med->code} {$med->name}");

        return back()->with('success', "Obat {$med->name} ditambahkan dengan kode {$med->code}.");
    }

    public function update(Request $request, Medicine $medicine)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'category' => 'required|string|max:40',
            'unit' => 'required|string|max:20',
            'price' => 'required|numeric|min:0|max:999999999',
            'min_stock' => 'required|integer|min:0|max:100000',
            'expired_date' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $medicine->update($data);
        ActivityLog::record('medicine', "Obat {$medicine->code} {$medicine->name} diperbarui");

        return back()->with('success', "Obat {$medicine->name} diperbarui.");
    }

    /** Restock (tambah) atau opname (set stok sebenarnya). */
    public function stock(Request $request, Medicine $medicine)
    {
        $data = $request->validate([
            'mode' => 'required|in:in,adjust',
            'qty' => 'required|integer|min:0|max:100000',
            'expired_date' => 'nullable|date',
            'note' => 'nullable|string|max:150',
        ]);

        DB::transaction(function () use ($medicine, $data, $request) {
            $medicine = Medicine::lockForUpdate()->find($medicine->id);
            if (! empty($data['expired_date'])) {
                $medicine->expired_date = $data['expired_date'];
            }
            $medicine->moveStock($data['mode'], (int) $data['qty'], null, $data['note'] ?? ($data['mode'] === 'in' ? 'Restock' : 'Stok opname'), $request->user()->id);
        });
        ActivityLog::record('stock', ($data['mode'] === 'in' ? 'Restock +' : 'Opname → ') . "{$data['qty']} {$medicine->name}");

        return back()->with('success', "Stok {$medicine->name} sekarang " . $medicine->fresh()->stock . " {$medicine->unit}.");
    }

    public function destroy(Medicine $medicine)
    {
        if ($medicine->prescriptions()->exists()) {
            $medicine->update(['is_active' => false]);
            return back()->with('success', "{$medicine->name} sudah pernah diresepkan, jadi dinonaktifkan (bukan dihapus).");
        }
        $medicine->delete();
        ActivityLog::record('medicine', "Obat {$medicine->name} dihapus");

        return back()->with('success', 'Obat dihapus.');
    }
}
