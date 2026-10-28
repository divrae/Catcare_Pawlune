<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        $start = $this->weekStart($request->query('week'));
        $days = collect(range(0, 6))->map(fn ($i) => $start->copy()->addDays($i));

        $staff = User::whereIn('role', ['doctor', 'staff', 'cashier', 'groomer'])->where('is_active', true)->orderBy('role')->orderBy('name')->get();
        $shifts = Shift::whereBetween('shift_date', [$start->toDateString(), $start->copy()->addDays(6)->toDateString()])->get()
            ->mapWithKeys(fn ($s) => [$s->user_id . '|' . $s->shift_date->toDateString() => $s->shift_type]);

        return view('admin.shifts', [
            'start' => $start, 'days' => $days, 'staff' => $staff, 'shifts' => $shifts,
            'prev' => $start->copy()->subWeek()->toDateString(), 'next' => $start->copy()->addWeek()->toDateString(),
        ]);
    }

    public function save(Request $request)
    {
        $start = $this->weekStart($request->input('week'));
        $request->validate(['shift' => 'nullable|array', 'shift.*.*' => 'nullable|in:pagi,siang,malam']);
        $valid = User::whereIn('role', ['doctor', 'staff', 'cashier', 'groomer'])->pluck('id')->all();
        $end = $start->copy()->addDays(6);

        foreach ((array) $request->input('shift', []) as $userId => $byDate) {
            if (! in_array((int) $userId, $valid, true)) {
                continue;
            }
            foreach ((array) $byDate as $date => $type) {
                $d = Carbon::parse($date);
                if ($d->lt($start) || $d->gt($end)) {
                    continue;
                }
                if ($type) {
                    $this->put((int) $userId, $d, $type);
                } else {
                    Shift::where('user_id', $userId)->whereDate('shift_date', $d)->delete();
                }
            }
        }
        ActivityLog::record('shift', 'Admin memperbarui jadwal shift minggu ' . $start->format('d/m/Y'));

        return redirect()->route('admin.shifts.index', ['week' => $start->toDateString()])->with('success', 'Jadwal shift tersimpan.');
    }

    public function copyPrevious(Request $request)
    {
        $start = $this->weekStart($request->input('week'));
        $prev = $start->copy()->subWeek();
        $count = 0;

        foreach (Shift::whereBetween('shift_date', [$prev->toDateString(), $prev->copy()->addDays(6)->toDateString()])->get() as $s) {
            $target = $s->shift_date->copy()->addWeek();
            $this->put($s->user_id, $target, $s->shift_type);
            $count++;
        }
        ActivityLog::record('shift', "Admin menyalin shift minggu lalu ({$count} jadwal)");

        return redirect()->route('admin.shifts.index', ['week' => $start->toDateString()])->with('success', "{$count} jadwal disalin dari minggu sebelumnya.");
    }

    /** Simpan 1 shift (1 staff = 1 shift per hari); pencarian pakai whereDate agar aman di MySQL & SQLite. */
    private function put(int $userId, Carbon $date, string $type): void
    {
        $row = Shift::where('user_id', $userId)->whereDate('shift_date', $date)->first();
        $row ? $row->update(['shift_type' => $type]) : Shift::create(['user_id' => $userId, 'shift_date' => $date->toDateString(), 'shift_type' => $type]);
    }

    private function weekStart($value): Carbon
    {
        try {
            $d = $value ? Carbon::parse($value) : today();
        } catch (\Throwable) {
            $d = today();
        }

        return $d->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
    }
}
