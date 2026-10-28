<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardService $service)
    {
        $days = in_array((int) $request->query('days'), [7, 14, 30], true) ? (int) $request->query('days') : 14;

        return view('admin.dashboard', $service->build($days) + ['days' => $days]);
    }
}
