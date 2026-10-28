<?php

namespace App\Http\Controllers;

use App\Models\Pet;
use App\Models\Room;
use App\Models\Shift;
use App\Models\Tariff;
use App\Models\Testimonial;
use App\Models\Visit;

class HomeController extends Controller
{
    public function index()
    {
        return view('home.index', [
            'testimonials' => Testimonial::with('user')->where('is_published', true)->latest()->take(6)->get(),
            'doctorsToday' => Shift::with('user')->whereDate('shift_date', today())
                ->whereHas('user', fn ($q) => $q->where('role', 'doctor')->where('is_active', true))->get(),
            'tariffs' => Tariff::active()->whereIn('category', ['pemeriksaan', 'tindakan', 'grooming'])->orderBy('price')->take(6)->get(),
            'boardingTariffs' => Tariff::active()->where('category', 'boarding')->orderBy('price')->get(),
            'stats' => [
                'pets' => Pet::count(),
                'visits' => Visit::where('status', 'completed')->count(),
                'rooms' => Room::where('is_maintenance', false)->count(),
            ],
        ]);
    }
}
