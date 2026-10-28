<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\RecapService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class RecapController extends Controller
{
    public function index(Request $request, RecapService $recap)
    {
        [$from, $to] = $this->range($request);

        return view('admin.recap', [
            'data' => $recap->build($from, $to), 'from' => $from, 'to' => $to,
            'section' => $request->query('section', 'all'),
        ]);
    }

    public function print(Request $request, RecapService $recap)
    {
        [$from, $to] = $this->range($request);

        return view('admin.recap-print', ['data' => $recap->build($from, $to), 'from' => $from, 'to' => $to, 'section' => $request->query('section', 'all'), 'pdf' => false]);
    }

    public function pdf(Request $request, RecapService $recap)
    {
        [$from, $to] = $this->range($request);
        $pdf = Pdf::loadView('admin.recap-print', ['data' => $recap->build($from, $to), 'from' => $from, 'to' => $to, 'section' => $request->query('section', 'all'), 'pdf' => true])
            ->setPaper('a4', 'portrait');

        return $pdf->download('rekap-pawlune-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.pdf');
    }

    private function range(Request $request): array
    {
        $from = $request->date('from') ?? today()->startOfMonth();
        $to = $request->date('to') ?? today();
        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }
}
