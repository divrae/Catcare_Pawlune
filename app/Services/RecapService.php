<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Boarding;
use App\Models\BoardingLog;
use App\Models\GroomingOrder;
use App\Models\Invoice;
use App\Models\MedicalRecord;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;

/** Rekap admin / dokter / boarding / POS untuk rentang tanggal. */
class RecapService
{
    public function build(Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        return [
            'range' => ['from' => $from, 'to' => $to],
            'admin' => $this->admin($from, $to),
            'doctor' => $this->doctor($from, $to),
            'boarding' => $this->boarding($from, $to),
            'grooming' => $this->grooming($from, $to),
            'pos' => $this->pos($from, $to),
        ];
    }

    private function admin(Carbon $from, Carbon $to): array
    {
        $logs = ActivityLog::with('user')->whereBetween('created_at', [$from, $to])->latest()->take(100)->get();

        return [
            'staff_by_role' => User::whereIn('role', User::STAFF_ROLES)->where('is_active', true)->get()->groupBy('role')->map->count(),
            'new_staff' => User::whereIn('role', User::STAFF_ROLES)->whereBetween('created_at', [$from, $to])->count(),
            'new_owners' => User::where('role', 'owner')->whereBetween('created_at', [$from, $to])->count(),
            'walk_in' => Visit::whereBetween('queue_date', [$from->toDateString(), $to->toDateString()])->where('source', 'walk_in')->count(),
            'online' => Visit::whereBetween('queue_date', [$from->toDateString(), $to->toDateString()])->where('source', 'online')->count(),
            'logs' => $logs,
        ];
    }

    private function doctor(Carbon $from, Carbon $to): array
    {
        $records = MedicalRecord::with('doctor', 'visit.pet.owner', 'prescriptions')->whereBetween('created_at', [$from, $to])->latest()->get();

        return [
            'total' => $records->count(),
            'referrals' => $records->where('referred_to_boarding', true)->count(),
            'prescriptions' => $records->sum(fn ($r) => $r->prescriptions->count()),
            'per_doctor' => $records->groupBy('doctor_id')->map(fn ($g) => [
                'name' => $g->first()->doctor?->name ?? '-', 'exams' => $g->count(),
                'referrals' => $g->where('referred_to_boarding', true)->count(),
                'rx' => $g->sum(fn ($r) => $r->prescriptions->count()),
            ])->values(),
            'top_diagnosis' => $records->groupBy(fn ($r) => mb_strtolower(trim($r->diagnosis)))->map->count()->sortDesc()->take(8),
            'records' => $records->take(60),
        ];
    }

    private function boarding(Carbon $from, Carbon $to): array
    {
        $stays = Boarding::with('pet.owner', 'room', 'logs')
            ->whereIn('status', ['active', 'finished'])
            ->whereDate('check_in_date', '<=', $to)->whereDate('check_out_date', '>=', $from)->get();

        $logs = BoardingLog::whereBetween('logged_at', [$from, $to])->get();
        $invoices = Invoice::whereBetween('paid_at', [$from, $to])->get();

        return [
            'stays' => $stays,
            'started' => Boarding::whereBetween('checked_in_at', [$from, $to])->count(),
            'finished' => Boarding::whereBetween('checked_out_at', [$from, $to])->count(),
            'referrals' => $stays->where('is_referral', true)->count(),
            'active_now' => Boarding::where('status', 'active')->count(),
            'avg_days' => $stays->where('status', 'finished')->count() ? round($stays->where('status', 'finished')->avg(fn ($b) => $b->billedDays()), 1) : 0,
            'revenue' => (float) $invoices->sum('total_boarding'),
            'log_count' => $logs->count(),
            'health_alerts' => $logs->whereIn('health_status', ['perhatian', 'sakit'])->count(),
            'no_meal' => $logs->where('meal', 'tidak_makan')->count(),
        ];
    }

    private function grooming(Carbon $from, Carbon $to): array
    {
        $orders = GroomingOrder::with('pet.owner', 'groomer', 'services', 'boarding.room')
            ->where('status', 'done')->whereBetween('finished_at', [$from, $to])->latest('finished_at')->get();
        $invoices = Invoice::whereBetween('paid_at', [$from, $to])->get();

        return [
            'total' => $orders->count(),
            'guest' => $orders->whereNotNull('boarding_id')->count(),
            'standalone' => $orders->whereNull('boarding_id')->count(),
            'cancelled' => GroomingOrder::where('status', 'cancelled')->whereBetween('updated_at', [$from, $to])->count(),
            'open_now' => GroomingOrder::whereIn('status', GroomingOrder::OPEN)->count(),
            'worked' => (float) $orders->sum(fn ($o) => $o->total()),
            'revenue' => (float) $invoices->sum('total_grooming'),
            'per_groomer' => $orders->groupBy('groomer_id')->map(fn ($g) => [
                'name' => $g->first()->groomer?->name ?? '-', 'count' => $g->count(),
                'total' => (float) $g->sum(fn ($o) => $o->total()),
            ])->values(),
            'top_services' => $orders->flatMap->services->groupBy('name')->map(fn ($g, $k) => ['name' => $k, 'qty' => (int) $g->sum('qty'), 'total' => (float) $g->sum('subtotal')])
                ->sortByDesc('qty')->take(6)->values(),
            'orders' => $orders->take(60),
        ];
    }

    private function pos(Carbon $from, Carbon $to): array
    {
        $invoices = Invoice::with('cashier', 'visit.pet.owner', 'items')->whereBetween('paid_at', [$from, $to])->latest('paid_at')->get();
        $items = $invoices->flatMap->items;
        $meds = StockMovement::with('medicine')->where('type', 'out')->whereBetween('created_at', [$from, $to])->get()
            ->groupBy('medicine_id')->map(fn ($g) => [
                'code' => $g->first()->medicine?->code, 'name' => $g->first()->medicine?->name ?? '-',
                'qty' => (int) $g->sum('qty'), 'unit' => $g->first()->medicine?->unit,
            ])->sortByDesc('qty')->values();

        return [
            'count' => $invoices->count(),
            'total' => (float) $invoices->sum('grand_total'),
            'discount' => (float) $invoices->sum('discount'),
            'avg' => $invoices->count() ? (float) $invoices->avg('grand_total') : 0,
            'by_method' => collect(Invoice::METHODS)->map(fn ($label, $k) => [
                'label' => $label, 'count' => $invoices->where('payment_method', $k)->count(),
                'total' => (float) $invoices->where('payment_method', $k)->sum('grand_total'),
            ]),
            'by_dept' => [
                'Klinik / Tindakan' => (float) $invoices->sum('total_medical'),
                'Obat' => (float) $invoices->sum('total_pharmacy'),
                'Boarding' => (float) $invoices->sum('total_boarding'),
                'Grooming' => (float) $invoices->sum('total_grooming'),
                'Lainnya' => (float) $invoices->sum('total_other'),
            ],
            'by_cashier' => $invoices->groupBy('cashier_id')->map(fn ($g) => [
                'name' => $g->first()->cashier?->name ?? '-', 'count' => $g->count(), 'total' => (float) $g->sum('grand_total'),
            ])->values(),
            'top_items' => $items->groupBy('description')->map(fn ($g, $k) => ['name' => $k, 'qty' => (int) $g->sum('qty'), 'total' => (float) $g->sum('subtotal')])
                ->sortByDesc('total')->take(8)->values(),
            'medicines_out' => $meds,
            'invoices' => $invoices->take(80),
        ];
    }
}
