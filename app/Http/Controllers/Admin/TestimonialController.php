<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;

class TestimonialController extends Controller
{
    public function index()
    {
        return view('admin.testimonials', ['testimonials' => Testimonial::with('user')->latest()->paginate(20)]);
    }

    public function toggle(Testimonial $testimonial)
    {
        $testimonial->update(['is_published' => ! $testimonial->is_published]);

        return back()->with('success', $testimonial->is_published ? 'Testimoni ditampilkan di beranda.' : 'Testimoni disembunyikan dari beranda.');
    }

    public function destroy(Testimonial $testimonial)
    {
        $testimonial->delete();

        return back()->with('success', 'Testimoni dihapus.');
    }
}
