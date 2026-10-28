<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $customers = User::where('role', 'owner')->withCount('pets')
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('name', 'like', "%$q%")->orWhere('phone', 'like', "%$q%")))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.customers', compact('customers', 'q'));
    }
}
