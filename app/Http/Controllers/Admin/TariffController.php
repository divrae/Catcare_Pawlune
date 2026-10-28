<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Tariff;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TariffController extends Controller
{
    public function index(Request $request)
    {
        $cat = $request->query('category');
        $tariffs = Tariff::when($cat, fn ($q) => $q->where('category', $cat))->orderBy('category')->orderBy('name')->get();

        return view('admin.tariffs', ['tariffs' => $tariffs, 'cat' => $cat, 'categories' => config('catcare.tariff_categories')]);
    }

    public function store(Request $request)
    {
        $data = $this->rules($request);
        $data['code'] = Tariff::nextCode();
        $t = Tariff::create($data);
        ActivityLog::record('tariff', "Tarif baru: {$t->name} " . rupiah($t->price));

        return back()->with('success', "Tarif {$t->name} ditambahkan.");
    }

    public function update(Request $request, Tariff $tariff)
    {
        $old = $tariff->price;
        $tariff->update($this->rules($request));
        ActivityLog::record('tariff', "Tarif {$tariff->name}: " . rupiah($old) . ' → ' . rupiah($tariff->price));

        return back()->with('success', "Tarif {$tariff->name} diperbarui.");
    }

    public function destroy(Tariff $tariff)
    {
        // Tarif yang sudah dipakai di transaksi cukup dinonaktifkan agar riwayat tetap utuh.
        if ($tariff->rooms()->exists() || \App\Models\VisitService::where('tariff_id', $tariff->id)->exists()) {
            $tariff->update(['is_active' => false]);
            return back()->with('success', "Tarif {$tariff->name} sudah dipakai, jadi dinonaktifkan (bukan dihapus).");
        }
        $tariff->delete();
        ActivityLog::record('tariff', "Tarif {$tariff->name} dihapus");

        return back()->with('success', 'Tarif dihapus.');
    }

    private function rules(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'category' => ['required', Rule::in(array_keys(config('catcare.tariff_categories')))],
            'price' => 'required|numeric|min:0|max:999999999',
            'is_active' => 'nullable|boolean',
        ]);
        $data['unit'] = $data['category'] === 'boarding' ? 'hari' : 'kali';
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
