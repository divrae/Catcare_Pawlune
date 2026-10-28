<?php

namespace App\Services;

use App\Models\Boarding;
use App\Models\GroomingOrder;
use App\Models\Invoice;
use App\Models\Medicine;
use App\Models\Room;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;

/** Data untuk dashboard admin (KPI + grafik). Pengelompokan dilakukan di PHP agar jalan di MySQL maupun SQLite. */
class DashboardService
{
    public function build(int $days = 14): array
    {
        $start = today()->subDays($days - 1);
        $labels = collect(range(0, $days - 1))->map(fn ($i) => $start->copy()->addDays($i));
        $keys = $labels->map(fn (Carbon $d) => $d->toDateString());
        $fill = fn (array $by) => $keys->map(fn ($k) => $by[$k] ?? 0)->values();

        // Pemasukan per hari
        $invoices = Invoice::where('paid_at', '>=', $start->copy()->startOfDay())->get();
        $incomeBy = $invoices->groupBy(fn ($i) => $i->paid_at->toDateString())->map(fn ($g) => (float) $g->sum('grand_total'))->all();

        // Kunjungan per hari
        $visits = Visit::where('queue_date', '>=', $start->toDateString())->where('status', '!=', 'cancelled')->get();
        $clinicBy = $visits->where('service_type', 'clinic')->groupBy(fn ($v) => $v->queue_date->toDateString())->map->count()->all();
        $boardBy = $visits->where('service_type', 'boarding')->groupBy(fn ($v) => $v->queue_date->toDateString())->map->count()->all();

        // Grooming per hari (antrean sendiri + tamu boarding), tidak termasuk yang dibatalkan
        $groomOrders = GroomingOrder::where('created_at', '>=', $start->copy()->startOfDay())->where('status', '!=', 'cancelled')->get();
        $groomBy = $groomOrders->groupBy(fn ($o) => $o->created_at->toDateString())->map->count()->all();
        $groomToday = $groomOrders->filter(fn ($o) => $o->created_at->isToday());

        // Obat keluar per hari
        $outs = StockMovement::with('medicine')->where('type', 'out')->where('created_at', '>=', $start->copy()->startOfDay())->get();
        $outBy = $outs->groupBy(fn ($m) => $m->created_at->toDateString())->map(fn ($g) => (int) $g->sum('qty'))->all();
        $topMeds = $outs->groupBy('medicine_id')->map(fn ($g) => ['name' => $g->first()->medicine?->name ?? '-', 'qty' => (int) $g->sum('qty')])
            ->sortByDesc('qty')->take(5)->values();

        // Pelanggan baru per hari
        $newOwners = User::where('role', 'owner')->where('created_at', '>=', $start->copy()->startOfDay())->get();
        $newBy = $newOwners->groupBy(fn ($u) => $u->created_at->toDateString())->map->count()->all();

        // Komposisi pemasukan 30 hari & metode bayar
        $month = Invoice::where('paid_at', '>=', today()->subDays(29)->startOfDay())->get();
        $todayInv = $invoices->filter(fn ($i) => $i->paid_at->isToday());

        $roomsTotal = Room::where('is_maintenance', false)->count();
        $occupied = Boarding::where('status', 'active')->count();

        $todayVisits = $visits->filter(fn ($v) => $v->queue_date->isToday());

        return [
            'kpi' => [
                'income_today' => (float) $todayInv->sum('grand_total'),
                'income_month' => (float) Invoice::whereYear('paid_at', now()->year)->whereMonth('paid_at', now()->month)->sum('grand_total'),
                'tx_today' => $todayInv->count(),
                'clinic_today' => $todayVisits->where('service_type', 'clinic')->count(),
                'boarding_today' => $todayVisits->where('service_type', 'boarding')->count(),
                'pets_served' => $todayVisits->whereIn('status', ['in_treatment', 'in_boarding', 'awaiting_payment', 'completed'])->pluck('pet_id')->unique()->count(),
                'waiting_clinic' => $todayVisits->where('service_type', 'clinic')->where('status', 'waiting')->count(),
                'grooming_today' => $groomToday->count(),
                'grooming_guest_today' => $groomToday->whereNotNull('boarding_id')->count(),
                'grooming_open' => GroomingOrder::whereIn('status', GroomingOrder::OPEN)->count(),
                'grooming_income_today' => (float) $todayInv->sum('total_grooming'),
                'waiting_boarding' => $todayVisits->where('service_type', 'boarding')->where('status', 'waiting')->count(),
                'called' => $todayVisits->where('status', 'called')->count(),
                'customers' => User::where('role', 'owner')->count(),
                'customers_new_today' => $newOwners->filter(fn ($u) => $u->created_at->isToday())->count(),
                'med_out_today' => (int) ($outBy[today()->toDateString()] ?? 0),
                'med_low' => Medicine::where('is_active', true)->whereColumn('stock', '<=', 'min_stock')->count(),
                'med_expired' => Medicine::where('is_active', true)->whereNotNull('expired_date')->whereDate('expired_date', '<=', today()->addDays(30))->count(),
                'rooms_total' => $roomsTotal, 'rooms_occupied' => $occupied,
                'occupancy' => $roomsTotal ? round($occupied / $roomsTotal * 100) : 0,
                'staff' => User::whereIn('role', User::STAFF_ROLES)->where('is_active', true)->count(),
            ],
            'charts' => [
                'labels' => $labels->map(fn ($d) => $d->locale('id')->translatedFormat('d M'))->values(),
                'income' => $fill($incomeBy),
                'clinic' => $fill($clinicBy),
                'boarding' => $fill($boardBy),
                'grooming' => $fill($groomBy),
                'medOut' => $fill($outBy),
                'newCustomers' => $fill($newBy),
                'topMeds' => $topMeds,
                'revenueMix' => [
                    'Klinik / Tindakan' => (float) $month->sum('total_medical'),
                    'Obat' => (float) $month->sum('total_pharmacy'),
                    'Boarding' => (float) $month->sum('total_boarding'),
                    'Grooming' => (float) $month->sum('total_grooming'),
                    'Lainnya' => (float) $month->sum('total_other'),
                ],
                'payMix' => [
                    'Tunai' => (float) $month->where('payment_method', 'cash')->sum('grand_total'),
                    'QRIS' => (float) $month->where('payment_method', 'qris')->sum('grand_total'),
                    'Transfer' => (float) $month->where('payment_method', 'transfer')->sum('grand_total'),
                ],
            ],
            'queueNow' => Visit::with('pet.owner')->today()->whereIn('status', ['waiting', 'called', 'in_treatment'])->orderBy('service_type')->orderBy('queue_number')->get(),
            'lowMedicines' => Medicine::where('is_active', true)->where(fn ($q) => $q->whereColumn('stock', '<=', 'min_stock')
                ->orWhere(fn ($w) => $w->whereNotNull('expired_date')->whereDate('expired_date', '<=', today()->addDays(30))))
                ->orderBy('stock')->take(8)->get(),
        ];
    }
}
